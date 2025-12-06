<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Album extends Model
{
    protected $fillable = [
        'title',
    ];

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'album_artist')
                    ->withTimestamps();
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }
}