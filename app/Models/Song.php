<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Song extends Model
{
    protected $primaryKey = 'filename';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'filename',
        'title',
        'duration',
        'album_id',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'artist_song', 'song_filename', 'artist_id')
                    ->withTimestamps();
    }
}
