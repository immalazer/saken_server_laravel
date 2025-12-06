<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Artist extends Model
{
    protected $fillable = [
        'name',
    ];

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_artist')
            ->withTimestamps();
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'artist_song', 'artist_id', 'song_filename')
            ->withTimestamps();
    }
}
