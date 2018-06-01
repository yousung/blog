<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        factory(\App\User::class, 1)->create(['email' => 'test@test.com']);
        factory(\App\Tag::class, 10)->create();
        factory(\App\Series::class, 10)->create()->each(function ($s) {
            factory(\App\Post::class, 5)->create([
                'series_id' => rand(0, 1) ? $s->id : null,
            ])->each(function ($p) {
                $p->tags()->sync(\App\Tag::all()->random(rand(1, 6)));
            });
        });
    }
}
