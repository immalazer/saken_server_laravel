<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->timestamps();
        });

        Schema::create('songs', function (Blueprint $table) {
            $table->string('filename')->primary();
            $table->string('title');
            $table->string('duration');
            $table->foreignId('album_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('artist_song', function (Blueprint $table) {
            $table->id();
            $table->string('song_filename');
            $table->foreign('song_filename')
                ->references('filename')
                ->on('songs')
                ->cascadeOnDelete();
            $table->foreignId('artist_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unique(['song_filename', 'artist_id']);
            $table->timestamps();
        });

        Schema::create('album_artist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('artist_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unique(['album_id', 'artist_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_song');
        Schema::dropIfExists('album_artist');
        Schema::dropIfExists('songs');
        Schema::dropIfExists('albums');
        Schema::dropIfExists('artists');
    }
};
