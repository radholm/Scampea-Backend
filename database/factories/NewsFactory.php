<?php

use Faker\Generator as Faker;

$factory->define(App\News::class, function (Faker $faker) {
    $user_ids = App\User::pluck('id');

    return [
        'user_id'   => $user_ids->random(),
        'title'     => $faker->catchPhrase,
        'text'      => $faker->text,
    ];
});
