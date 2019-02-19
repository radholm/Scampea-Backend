<?php

use App\ProjectUser;
use Illuminate\Database\Seeder;

class ProjectUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user_ids = App\User::pluck('id');
        $project_ids = App\Project::pluck('id');

        for($i = 0; $i < 10; $i++) {
            $rnd_user_id = $user_ids->random();
            $rnd_project_id = $project_ids->random();
        
            while (ProjectUser::where('user_id', $rnd_user_id)->where('project_id', $rnd_project_id)->count() == 1)
            {
                $rnd_user_id = $user_ids->random();
                $rnd_project_id = $project_ids->random();
            }

            App\ProjectUser::create([
                'user_id'       => $rnd_user_id,
                'project_id'    => $rnd_project_id,
            ]);
        }

        // factory(App\ProjectUser::class, 10)->create();
    }
}
