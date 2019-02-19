<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\Timelog;
use App\Project;
use App\ProjectUser;

class TimelogTest extends TestCase
{
    use RefreshDatabase;

    protected $users = [];
    protected $project;

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

        $this->project = factory(Project::class)->create();

        factory(ProjectUser::class)->create(['user_id' => $this->users[0], 'project_id' => $this->project->id]);
        factory(ProjectUser::class)->create(['user_id' => $this->users[1], 'project_id' => $this->project->id]);

        factory(Timelog::class, 20)->create();
    }

    /**
     * Test getting user timelogs when not logged in
     *
     * @return void
     */
    public function testGetUserTimelogsNotLoggedIn()
    {
        $response = $this->json('GET', '/api/timelogs');

        $response->assertStatus(401);
    }

    /**
     * Test getting all user timelogs as admin
     *
     * @return void
     */
    public function testGetAllUserTimelogs() {
        $admin = $this->users[1];
        $user = $this->users[0];

        $response = $this
            ->actingAs($admin, 'api')
            ->json('GET', '/api/timelogs/all');

        $response
            ->assertJson(Timelog::All()->toArray())
            ->assertStatus(200);

        $response = $this
            ->actingAs($user, 'api')
            ->json('GET', '/api/timelogs/all');
        
        $response->assertStatus(403);
    }

    /**
     * Test getting specific user timelogs as admin
     *
     * @return void
     */
    public function testGetUserSpecificTimelogs() {
        $admin = $this->users[1];
        $user = $this->users[0];

        $response = $this
            ->actingAs($admin, 'api')
            ->json('GET', '/api/timelogs/' . $user['id']);

        $response
            ->assertJson(Timelog::where('user_id', $user['id'])->get()->toArray())
            ->assertStatus(200);
        
        $response = $this
            ->actingAs($user, 'api')
            ->json('GET', '/api/timelogs/' . $user['id']);
        
        $response->assertStatus(403);
    }

     /**
     * Test getting a users timelogs as the user
     *
     * @return void
     */
    public function testGetUserTimelogs() {
        foreach ($this->users as $user) {
            $response = $this
                ->actingAs($user, 'api')
                ->json('GET', '/api/timelogs');

            $response
                ->assertJson(Timelog::where('user_id', $user->id)->get()->toArray())
                ->assertStatus(200);
        }
    }

    /**
     * Test creating timelogs
     *
     * @return void
     */
    public function testCreateTimelogs() {
        $testUser = $this->users[0];
        $testProject = $this->project;
        
        $testDataCorrect = [
            'date' => '2017-09-25',
            'time' => '18:58',
            'project_id' => $testProject->id,
            'contribution' => 'Success',
        ];

        $response = $this
            ->actingAs($testUser, 'api')
            ->json('POST', '/api/timelog', $testDataCorrect);

        $response
            ->assertJson([
                'user_id'       => $testUser->id,
                'project_id'    => $testProject->id,
                'date'          => $testDataCorrect['date'],
                'time'          => $testDataCorrect['time'],
                'contribution'  => $testDataCorrect['contribution'],
            ]);

        $response = $this
            ->actingAs($testUser, 'api')
            ->json('POST', '/api/timelog', []);
        
        $response->assertExactJson([
            "errors" => [
                "date" => [
                    "The date field is required."
                ],
                "project_id" => [
                    "The project id field is required."
                ],
                "time" => [
                    "The time field is required."
                ],
                "contribution" => [
                    "The contribution field is required."
                ],
            ],
            "message" => "The given data was invalid."
        ]);

        $testDataIncorrectDateFormat = [
            'date' => '17-03-2010',
            'time' => '18:58',
            'project_id' => $testProject->id,
            'contribution' => 'Not Success',
        ];

        $response = $this
            ->actingAs($testUser, 'api')
            ->json('POST', '/api/timelog', $testDataIncorrectDateFormat);

        $response->assertExactJson([
            "errors" => [
                "date" => [
                    "The date does not match the format Y-m-d."
                ],
            ],
            "message" => "The given data was invalid."
        ]);

        $testDataTooshortcontribution = [
            'date' => '2017-09-25',
            'time' => '18:58',
            'project_id' => $testProject->id,
            'contribution' => 'x',
        ];

        $response = $this
            ->actingAs($testUser, 'api')
            ->json('POST', '/api/timelog', $testDataTooshortcontribution);

        $response->assertExactJson([
            "errors" => [
                "contribution" => [
                    "The contribution must be at least 2 characters."
                ],
            ],
            "message" => "The given data was invalid."
        ]);
    }

    /**
     * Test deleting timelogs
     *
     * @return void
     */
    public function testDeleteTimelog() {
        $regular_joe = $this->users[0];
        $admin = $this->users[1];

        $this->assertDatabaseMissing('timelogs', ['id' => '1337']);
        
        $response = $this 
            ->actingAs($regular_joe, 'api')
            ->json('DELETE', '/api/timelog/1337');

        $response->assertExactJson([
            'id' => [
                'The selected id is invalid.'
            ]
        ]);

        $timelog_not_joe = Timelog::where('user_id', '!=', $regular_joe['id'])->first();
        $this->assertDatabaseHas('timelogs', ['id' => $timelog_not_joe['id']]);
        
        $response = $this 
            ->actingAs($regular_joe, 'api')
            ->json('DELETE', '/api/timelog/' . $timelog_not_joe['id']);

        $response->assertExactJson([
            "user_id" => [
                "The selected user id is invalid."
            ]
        ]);

        $timelog_not_admin = Timelog::where('user_id', '!=', $admin['id'])->first();
        $this->assertDatabaseHas('timelogs', ['id' => $timelog_not_admin['id']]);        

        $response = $this
                ->actingAs($admin, 'api')
                ->json('DELETE', '/api/timelog/' . $timelog_not_admin['id']);

        $response->assertExactJson([
            "user_id" => [
                "The selected user id is invalid."
            ]
        ]);

        $timelog_joe = Timelog::where('user_id', $regular_joe['id'])->first();        
        $this->assertDatabaseHas('timelogs', ['id' => $timelog_joe['id']]);
        
        $response = $this
            ->actingAs($regular_joe, 'api')
            ->json('DELETE', '/api/timelog/' . $timelog_joe['id']);

        $response->assertSuccessful()->assertJson(['success' => 1]);
        $this->assertDatabaseMissing('timelogs', ['id' => $timelog_joe['id']]);

        $timelog_admin = Timelog::where('user_id', $admin['id'])->first();
        $this->assertDatabaseHas('timelogs', ['id' => $timelog_admin['id']]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('DELETE', '/api/timelog/' . $timelog_admin['id']);

        $response->assertSuccessful()->assertJson(['success' => 1]);
        $this->assertDatabaseMissing('timelogs', ['id' => $timelog_admin['id']]);

    }

    /**
     * Test deleting timelogs as admin
     *
     * @return void
     */
    public function testDeleteAsAdmin() {
        $admin = $this->users[1];
        $not_admin = $this->users[0];

        $timelog_admin = Timelog::where('user_id', $admin['id'])->first();
        $timelog_not_admin = Timelog::where('user_id', $not_admin['id'])->first();

        $response = $this
            ->actingAs($not_admin, 'api')
            ->json('DELETE', '/api/timelog/' . $timelog_not_admin['id'] . '/admin');

        $response
            ->assertStatus(403)
            ->assertExactJson(['message' => 'Forbidden, you must be an admin']);
        
        $this->assertDatabaseHas('timelogs', ['id' => $timelog_not_admin['id']]);
        $this->assertDatabaseHas('timelogs', ['id' => $timelog_admin['id']]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('DELETE', '/api/timelog/' . $timelog_not_admin['id'] . '/admin');

        $response->assertSuccessful()->assertJson(['success' => 1]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('DELETE', '/api/timelog/' . $timelog_admin['id'] . '/admin');

        $response->assertSuccessful()->assertJson(['success' => 1]);

        $this->assertDatabaseMissing('timelogs', ['id' => $timelog_admin['id']]);
        $this->assertDatabaseMissing('timelogs', ['id' => $timelog_not_admin['id']]);

        $no_such_id = '1337';

        $response = $this
            ->actingAs($admin, 'api')
            ->json('DELETE', '/api/timelog/' . $no_such_id . '/admin');

        $response->assertExactJson([
            "id" => [
                "The selected id is invalid."
            ]
        ]);
    }

    /**
     * Test updating timelogs as regular user
     *
     * @return void
     */
    public function testUpdateTimelog() {

        $test_user = $this->users[0];
        factory(Timelog::class)->create(['user_id' => $test_user->id]);

        $timelog_user = Timelog::where('user_id', $test_user->id)->first();
        $timelog_not_user = Timelog::where('user_id', '!=', $test_user->id)->first();

        $this->assertDatabaseHas('timelogs', ['id' => $timelog_user->id]);
        $this->assertDatabaseHas('timelogs', ['id' => $timelog_not_user->id]);

        $test_data = [
            'date' => '2017-04-23',
            'time' => '12:12',
            'contribution' => 'New test.'
        ];

        $response = $this
            ->actingAs($test_user, 'api')
            ->json('PUT', '/api/timelog/' . $timelog_not_user['id'], $test_data);

        $response
            ->assertSuccessful()
            ->assertExactJson([
                'user_id' => [
                    'The selected user id is invalid.'
                ]
            ]);

        $response = $this
            ->actingAs($test_user, 'api')
            ->json('PUT', '/api/timelog/' . $timelog_user['id'], $test_data);

        $response
            ->assertSuccessful()
            ->assertJson(['success' => 1]);

        $this->assertDatabaseHas('timelogs', [
            'id'            => $timelog_user['id'],
            'date'          => $test_data['date'],
            'time'          => $test_data['time'],
            'contribution'  => $test_data['contribution'],
        ]);
    }

    /**
     * Test updating timelogs as admin
     *
     * @return void
     */
    public function testUpdateTimelogAsAdmin() {
        $admin = $this->users[1];
        $not_admin = $this->users[0];

        $timelog_admin = Timelog::where('user_id', $admin['id'])->first();
        $timelog_not_admin = Timelog::where('user_id', $not_admin['id'])->first();

        $this->assertDatabaseHas('timelogs', ['id' => $timelog_admin['id']]);
        $this->assertDatabaseHas('timelogs', ['id' => $timelog_not_admin['id']]);

        $test_data = [
            'date'          => '2017-10-30',
            'time'          => '14:14',
            'contribution'  => 'New Test.',
        ];

        $response = $this
            ->actingAs($admin, 'api')
            ->json('PUT', '/api/timelog/' . $timelog_admin['id'] . '/admin', $test_data);

        $response->assertSuccessful()->assertJson(['success' => 1]);
        $this->assertDatabaseHas('timelogs', [
            'id'            => $timelog_admin['id'],
            'date'          => $test_data['date'],
            'time'          => $test_data['time'],
            'contribution'  => $test_data['contribution'],
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('PUT', '/api/timelog/' . $timelog_not_admin['id'] . '/admin', $test_data);

        $response->assertSuccessful()->assertJson(['success' => 1]);
        $this->assertDatabaseHas('timelogs', [
            'id'            => $timelog_not_admin['id'],
            'date'          => $test_data['date'],
            'time'          => $test_data['time'],
            'contribution'  => $test_data['contribution'],
        ]);

    }

    /**
     * Test getting project specific timelogs as admin or manager
     *
     * @return void
     */
    public function testGetProjectTimelogsAdminOrManager() {
        $admin = $this->users[1];
        $user = $this->users[0];
        $new_user = factory(User::class)->create(['permission' => 0]);

        $project = factory(Project::class)->create(['project_manager_id' => $user->id]);

        $response = $this
            ->actingAs($new_user, 'api')
            ->json('GET', '/api/timelogs/project/' . $project->id);
        
        $response->assertStatus(403)->assertJson(['message' => 'Forbidden, you must be a project manager or an admin']);

        $expected_response = Timelog::where('project_id', $project->id)->get()->toArray();

        $response = $this
            ->actingAs($user, 'api')
            ->json('GET', '/api/timelogs/project/' . $project->id);

        $response->assertSuccessful()->assertExactJson($expected_response);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('GET', '/api/timelogs/project/' . $project->id);
        
        $response->assertSuccessful()->assertExactJson($expected_response);

    }
}
