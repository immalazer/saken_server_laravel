<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Songs extends Model
{
    use HasFactory;

    protected $primaryKey = 'filename';
    public $incrementing = false;
    
    protected $table = 'songs';
    protected $fillable = ['title', 'artist', 'album', 'duration', 'filename'];
}
