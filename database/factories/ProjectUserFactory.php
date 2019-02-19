<?php

use App\ProjectUser;
use Faker\Generator as Faker;

$factory->define(App\ProjectUser::class, function (Faker $faker) {
    $user_ids = App\User::pluck('id');
    $project_ids = App\Project::pluck('id');

    $rnd_user_id = $user_ids->random();
    $rnd_project_id = $project_ids->random();

    while (ProjectUser::where('user_id', $rnd_user_id)->where('project_id', $rnd_project_id)->count() == 1)
    {
        $rnd_user_id = $user_ids->random();
        $rnd_project_id = $project_ids->random();
    }

    return [
        'user_id'       => $rnd_user_id,
        'project_id'    => $rnd_project_id,
    ];
});
