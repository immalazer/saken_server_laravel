<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Songs;

class SongsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        for ($i = 0; $i < 20; $i++) {
            Songs::create([
                'title' => $faker->company,
                'artist' => $faker->name,
                'album' => $faker->company,
                'duration' => $faker->time('i:s'),
                'filename' => $faker->uuid(), 
            ]);
        }
    }
}
