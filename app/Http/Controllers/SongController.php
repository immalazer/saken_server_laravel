<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Songs;
use App\Events\AddNotifierEvent;
use App\Events\UpdateNotifierEvent;
use App\Events\DestroyNotifierEvent;

use Owenoj\LaravelGetId3\GetId3;

class SongController extends Controller
{
    public function index()
    {
        $songs = Songs::all();
        return response()->json($songs);
    }

    public function play($filename)
    {
        $song = Songs::where('filename', '=', $filename)->get();

        if (!empty($song)) {
            $filePath = storage_path('app/public/songs/' . $filename);

            if (!file_exists($filePath)) {
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

    public function show($filename)
    {
        $song = Songs::where('filename', '=', $filename)->get();

        if (!empty($song)) {
            return response()->json($song);
        } else {
            return response()->json([
                "message" => "Song not found."
            ], 404);
        }
    }

    public function albumArt($filename)
    {
            $song = Songs::where('filename', '=', $filename)->get();

            if (!empty($song)) {
                $filePath = storage_path('app/public/art/' . $filename);

                if (!file_exists($filePath)) {
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
        $track = new GetId3(request()->file('file'));

        $song = new Songs;
        $song->title = $track->getTitle();
        $song->artist = $track->getArtist();
        $song->album = $track->getAlbum();
        $song->duration = $track->getPlaytime();
        $song->filename = Str::uuid()->toString();
        $song->save();

        $path = $request->file('file')->storeAs('songs', $song->filename, 'public');
        $artwork = $track->getArtwork(true);

        if ($artwork != null) {
            $album_path = $track->getArtwork(true)->storeAs('art', $song->filename, 'public');
        }

        event(new AddNotifierEvent($song));

        return response()->json([
            "message" => "Song added."
        ], 201);
    }

    public function update(Request $request, String $filename)
    {
        $filename = $request->filename;

        if (Songs::where('filename', '=', $filename)->exists()) {
            $song = Songs::where('filename', '=', $filename)->first();
            $song->title = is_null($request->title) ? $song->title : $request->title;
            $song->artist = is_null($request->artist) ? $song->artist : $request->artist;
            $song->album = is_null($request->album) ? $song->album : $request->album;
            $song->save();

            event(new UpdateNotifierEvent($song));
            
            return response()->json([
                "message" => "Song details edited."
            ], 201);
        } else {
            return response()->json([
                "message" => "$filename: Song can't be found using that filename."
            ], 404);
        }
    }

    public function destroy($filename)
    {
        if (Songs::where('filename', '=', $filename)->exists()) {
            $song = Songs::where('filename', '=', $filename)->first();

            // Delete the files related to the song.
            Storage::disk('public')->delete('songs/'.$filename);
            Storage::disk('public')->delete('art/'.$filename);

            $song->delete();

            event(new DestroyNotifierEvent($song));
            return response()->json([
                "message" => "Song deleted."
            ], 201);
        } else {
            return response()->json([
                "message" => "Song can't be found using that filename."
            ], 404);
        }
    }
}
