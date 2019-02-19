<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\User::class, function (Faker $faker) {
    static $password;
    $role_ids = App\Role::pluck('id');

    if ($role_ids->count() == 0)
    {
        factory(App\Role::class)->create();
        $role_ids = App\Role::pluck('id');
    }

    return [
        'username'      => $faker->unique()->userName,
        'first_name'    => $faker->firstName,
        'last_name'     => $faker->lastName,
        'permission'	=> $faker->numberBetween($min = 0, $max = 1),
        'expertise'		=> $faker->catchPhrase,
        'password'      => $password ?: $password = bcrypt('secret'),
        'role_id'       => $role_ids->random(),
        'picture'		=> ' ',
        'remember_token' => str_random(10),
    ];
});
