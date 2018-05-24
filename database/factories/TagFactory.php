<?php

use Faker\Generator as Faker;

$factory->define(\App\Tag::class, function (Faker $faker) {
    $tag = $faker->colorName;

    return [
        'name' => $tag,
        'slug' => make_slug($tag)
    ];
});
