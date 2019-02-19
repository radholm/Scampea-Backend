<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $titles = [
            'CEO',
            'Business Manager',
            'Configuration Manager',
            'System Architect',
            'Quality & Process Manager',
            'Head of Testing',
            'Project Manager',
            'Marketing & Financial Manager'
        ];

        // factory(App\Role::class, 100)->create();

        foreach ($titles as $title) {
            App\Role::create([ 'title' => $title ]);
        }
    }
}
