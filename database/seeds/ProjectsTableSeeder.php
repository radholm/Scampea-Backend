<?php

use Illuminate\Database\Seeder;

class ProjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (factory(App\Project::class, 10)->create() as $project) {
            factory(App\ProjectUser::class)->create([
                'user_id'       => $project->project_manager_id,
                'project_id'    => $project->id,
            ]);
        }
    }
}
