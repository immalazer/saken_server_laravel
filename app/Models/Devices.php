<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Devices extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'key';
    public $incrementing = false;

    protected $table = 'devices';
    protected $fillable = ['nickname', 'key', 'device_type', 'current_song'];

    /**
     * Get the currently playing song.
     */
    public function currentSong(): HasOne
    {
        return $this->hasOne(Song::class, 'filename', 'current_song');
    }
}
