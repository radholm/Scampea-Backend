<?php

use Faker\Generator as Faker;

$factory->define(App\Project::class, function (Faker $faker) {
    $user_ids = App\User::pluck('id');
    $rnd_user_id = $user_ids->random();

    return [
        'name' => $faker->catchPhrase,
        'project_manager_id' => $rnd_user_id,
    ];
});
