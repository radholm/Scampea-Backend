<?php

use Illuminate\Database\Seeder;
use App\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //factory(App\User::class, 10)->create();
    	$users = [
	    	[
	    		'username'   => 'andreassjoberg',
	    		'first_name' => 'Andreas',
					'last_name'  => 'Sjöberg',
					'permission' => 1,
	    		'role_id'    => Role::where('title', 'CEO')->first(['id']),
	    		'picture'	   => '/pictures/andreassjoberg.png'
	    	],
	    	[
	    		'username' 	 => 'moathorén',
	    		'first_name' => 'Moa',
	    		'last_name'  => 'Thorén',
	    		'role_id'    => Role::where('title', 'Business Manager')->first(['id']),
	    		'picture'	   => '/pictures/moathoren.png'
	    	],
	    	[
	    		'username' 	 => 'axelmyrberg',
	    		'first_name' => 'Axel',
	    		'last_name'  => 'Myrberg',
	    		'role_id'    => Role::where('title', 'Configuration Manager')->first(['id']),
	    		'picture'	   => '/pictures/axelmyrberg.png'
	    	],
	    	[
	    		'username'   => 'camillasvartsjo',
	    		'first_name' => 'Camilla',
	    		'last_name'  => 'Svartsjö',
	    		'role_id'    => Role::where('title', 'System Architect')->first(['id']),
	    		'picture'	   => '/pictures/camillasvartsjo.png'
	    	],
	    	[
	    		'username' 	 => 'erikmickols',
	    		'first_name' => 'Erik',
	    		'last_name'  => 'Mickols',
	    		'role_id'    => Role::where('title', 'Quality & Process Manager')->first(['id']),
	    		'picture'	   => '/pictures/erikmickols.png'
	    	],
	    	[
	    		'username' 	 => 'sonjadorrenboom',
	    		'first_name' => 'Sonja',
	    		'last_name'  => 'Dorrenboom',
	    		'role_id'    => Role::where('title', 'Head of Testing')->first(['id']),
	    		'picture'	   => '/pictures/sonjadorrenboom.png'
	    	],
	    	[
	    		'username' 	 => 'perlovén',
	    		'first_name' => 'Per',
	    		'last_name'  => 'Lovén',
	    		'role_id'    => Role::where('title', 'Project Manager')->first(['id']),
	    		'picture'	   => '/pictures/perloven.png'
	    	],
	    	[
	    		'username'	 => 'johanrosengren',
	    		'first_name' => 'Johan',
	    		'last_name'  => 'Rosengren',
	    		'role_id'    => Role::where('title', 'Marketing & Financial Manager')->first(['id']),
	    		'picture'	   => '/pictures/johanrosengren.png'
			],
			[
				'username'	 => 'admin',
				'first_name' => 'Admin',
				'last_name'  => 'Adminsson',
				'permission' => 1,
				'role_id'    => Role::where('title', 'CEO')->first(['id']),
				'picture'	   => '/pictures/sonjadorrenboom.png'
			],
			[
				'username'	 => 'test',
				'first_name' => 'Test',
				'last_name'  => 'Testsson',
				'permission' => 0,
				'role_id'    => Role::where('title', 'MARKETING & FINANCIAL MANAGER')->first(['id']),
				'picture'	 => '/pictures/sonjadorrenboom.png'
			]
		];

		foreach ($users as $user) {
			factory(App\User::class)->create($user);
	    }
    }
}
