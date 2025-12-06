<?php

namespace App\Http\Controllers;

use App\Events\AddNotifierEvent;
use App\Events\DestroyNotifierEvent;
use App\Events\UpdateNotifierEvent;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Owenoj\LaravelGetId3\GetId3;

class SongController extends Controller
{
    public function index()
    {
        $songs = Song::query()
            ->leftJoin('albums', 'songs.album_id', '=', 'albums.id')
            ->leftJoin('artist_song', 'songs.filename', '=', 'artist_song.song_filename')
            ->leftJoin('artists', 'artist_song.artist_id', '=', 'artists.id')
            ->selectRaw('
                songs.filename,
                songs.title,
                songs.duration,
                albums.title as album,
                GROUP_CONCAT(artists.name ORDER BY artists.name SEPARATOR ", ") as artists
            ')
            ->groupBy(
                'songs.filename',
                'songs.title',
                'songs.duration',
                'albums.title'
            )
            ->get();

        return response()->json($songs);
    }

    public function play($filename)
    {
        $song = Song::where('filename', '=', $filename)->get();

        if (! empty($song)) {
            $filePath = storage_path('app/public/songs/'.$filename);

            if (! file_exists($filePath)) {
                abort(404, 'File not found.');
            }

            $mimeType = mime_content_type($filePath);

            $headers = [
                'Content-Type' => $mimeType,
                'Access-Control-Allow-Origin' => '*',
            ];

            return Response::file($filePath, $headers);
        } else {
            abort(404, 'Song not found.');
        }
    }

    public function show($filename)
    {
        $song = Song::query()
            ->leftJoin('albums', 'songs.album_id', '=', 'albums.id')
            ->leftJoin('artist_song', 'songs.filename', '=', 'artist_song.song_filename')
            ->leftJoin('artists', 'artist_song.artist_id', '=', 'artists.id')
            ->selectRaw('
                songs.filename,
                songs.title,
                songs.duration,
                albums.title as album,
                GROUP_CONCAT(artists.name ORDER BY artists.name SEPARATOR ", ") as artists
            ')
            ->where('songs.filename', $filename)
            ->groupBy(
                'songs.filename',
                'songs.title',
                'songs.duration',
                'albums.title'
            )
            ->first();

        if (! $song) {
            return response()->json([
                'message' => 'Song not found.',
            ], 404);
        }

        return response()->json($song);
    }

    public function albumArt($filename)
    {
        $song = Song::where('filename', '=', $filename)->get();

        if (! empty($song)) {
            $filePath = storage_path('app/public/art/'.$filename);

            if (! file_exists($filePath)) {
                abort(404, 'File not found.');
            }

            $mimeType = mime_content_type($filePath);

            $headers = [
                'Content-Type' => $mimeType,
                'Access-Control-Allow-Origin' => '*', // or specific origin
            ];

            return Response::file($filePath, $headers);
        } else {
            abort(404, 'Song not found.');
        }
    }

    public function store(Request $request)
    {
        $track = new GetId3($request->file('file'));

        return DB::transaction(function () use ($request, $track) {
            // Manually extract the artist field because GetId3 only
            // fetches the first artist from the track (sane, but not what we want.)
            $artistNames = $track->extractInfo()['comments']['artist'] ?? [];

            // Don't bother if there's only one artist.
            if (! is_array($artistNames)) {
                $artistNames = [$artistNames];
            }

            $artistNames = array_filter(array_unique($artistNames));

            $artists = [];
            foreach ($artistNames as $name) {
                $artists[] = Artist::firstOrCreate(['name' => $name]);
            }

            $artistIds = array_map(fn ($x) => $x->id, $artists);

            $album = Album::firstOrCreate([
                'title' => $track->getAlbum(),
            ]);

            $album->artists()->syncWithoutDetaching($artistIds);

            $song = Song::create([
                'filename' => Str::uuid()->toString(),
                'title' => $track->getTitle(),
                'duration' => $track->getPlaytime(),
                'album_id' => $album->id,
            ]);

            $song->artists()->sync($artistIds);

            $request->file('file')->storeAs(
                'songs',
                $song->filename,
                'public'
            );

            $artwork = $track->getArtwork(true);
            if ($artwork) {
                $artwork->storeAs('art', $song->filename, 'public');
            }

            event(new AddNotifierEvent($song));

            return response()->json([
                'message' => 'Song added.',
            ], 201);
        });
    }

    public function update(Request $request, string $filename)
    {
        return DB::transaction(function () use ($request, $filename) {
            $song = Song::where('filename', $filename)->first();

            if (! $song) {
                return response()->json([
                    'message' => "$filename: Song not found.",
                ], 404);
            }

            if ($request->filled('artist')) {
                $artist = Artist::updateOrCreate(
                    ['id' => $song->artists()->first()->id],
                    ['name' => $request->artist]
                );
                $song->artists()->sync([$artist->id]);
            }

            if ($request->filled('album')) {
                $albumArtist = $song->artists()->first() ?: null;

                // When updating, we want to make sure that no album with
                // this ID exists. If it does, then we need to update it.
                $album = Album::updateOrCreate(
                    [
                        'id' => $song->album_id,
                    ],
                    [
                        'title' => $request->album,
                        'artist_id' => $albumArtist?->id,
                    ]);

                $song->album()->associate($album);
            }

            if ($request->filled('title')) {
                $song->title = $request->title;
            }

            if ($request->filled('duration')) {
                $song->duration = $request->duration;
            }

            $song->save();

            event(new UpdateNotifierEvent($song));

            return response()->json([
                'message' => 'Song details updated.',
            ], 200);
        });
    }

    public function destroy($filename)
    {
        return DB::transaction(function () use ($filename) {
            $song = Song::where('filename', $filename)->first();

            if (! $song) {
                return response()->json([
                    'message' => "Song can't be found using that filename.",
                ], 404);
            }

            Storage::disk('public')->delete('songs/'.$filename);
            Storage::disk('public')->delete('art/'.$filename);

            // Detach and delete
            $song->artists()->detach();
            $song->delete();

            event(new DestroyNotifierEvent($song));

            return response()->json([
                'message' => 'Song deleted.',
            ], 201);
        });
    }
}
