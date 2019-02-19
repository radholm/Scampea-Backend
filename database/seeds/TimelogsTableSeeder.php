<?php

use Illuminate\Database\Seeder;

class TimelogsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Timelog::class, 10)->create();
    }
}
