<?php

use Faker\Generator as Faker;

$factory->define(\App\Post::class, function (Faker $faker) {

    return [
        'user_id' => \App\User::all()->random()->id,
        'title' => $faker->realText(30),
        'subTitle' => $faker->realText(50),
        'context' => $faker->realText(3000),
        'created_at' => $faker->dateTimeBetween('-3 days', 'now')
    ];
});
