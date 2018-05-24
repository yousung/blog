<?php

use Faker\Generator as Faker;

$factory->define(\App\Series::class, function (Faker $faker) {
    $name = $faker->streetName;

    return [
        'user_id' => \App\User::all()->random()->id,
        'title' => $name,
        'subTitle' => $faker->realText(30),
        'slug' => make_slug($name),

    ];
});
