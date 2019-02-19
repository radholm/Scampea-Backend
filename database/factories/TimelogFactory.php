<?php

use Faker\Generator as Faker;

$factory->define(App\Timelog::class, function (Faker $faker) {
    $proj_user = App\ProjectUser::inRandomOrder()->first(['user_id', 'project_id']);

    return [
        'user_id'       => $proj_user->user_id,
        'project_id'	=> $proj_user->project_id,
        'date'          => $faker->dateTime(),
        'time'          => $faker->time(),
        'contribution'  => $faker->text,
    ];
});
