<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use Faker;
use App\Role;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected $users = [];
    protected $faker;

    protected function setUp()
    {
        parent::setUp();

        array_push($this->users,
            factory(User::class)->create([
                'permission' => 0
            ]),
            factory(User::class)->create([
                'permission' => 1
            ])
        );

        $this->faker = Faker\Factory::create();

        factory(Role::class)->create();
    }

    /**
     * Test getting all users as admin
     *
     * @return void
     */
    public function testGetRoles() {
        $admin = $this->users[1];
        $user = $this->users[0];

        $respone = $this
            ->actingAs($user, 'api')
            ->json('GET', '/api/roles');

        $respone->assertStatus(403);

        $respone = $this
            ->actingAs($admin, 'api')
            ->json('GET', '/api/roles');
        
        $respone->assertSuccessful()->assertJson(Role::All()->toArray());
    }

}
