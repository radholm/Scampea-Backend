<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\Project;
use App\Role;
use App\ProjectUser;
use Faker;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected $users = [];
    protected $faker;
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

        factory(Project::class, 10)->create();

        $this->faker = Faker\Factory::create();
        $this->project = [ 'name' => $this->faker->bs ];
    }

    /**
     * Test getting projects when not logged in
     *
     * @return void
     */
    public function testGetProjectsNotLoggedIn()
    {
        $response = $this->json('GET', '/api/projects');

        $response->assertStatus(401);
    }

    /**
     * Test getting projects when logged in
     *
     * @return void
     */
    public function testGetProjectsLoggedIn()
    {
        $response = $this
            ->actingAs($this->users[0], 'api')
            ->json('GET', '/api/projects');
        $response->assertJson(Project::all()->toArray());
    }

    /**
     * Test creating news when logged in
     *
     * @return void
     */
    public function testCreateProjectNotLoggedIn()
    {
        $response = $this->json('POST', '/api/project/create');

        $response->assertStatus(401);
    }

    /**
     * Test creating project logged in but not admin
     *
     * @return void
     */
    public function testCreateProjectNonAdmin()
    {
        $response = $this
            ->actingAs($this->users[0], 'api')
            ->json('POST', '/api/project/create');

        $response->assertStatus(403);
    }

    /**
     * Test creating project as admin
     *
     * @return void
     */
    public function testCreateProjectAsAdmin()
    {
        $new_project = factory(Project::class)->create();

        $response = $this
            ->actingAs($this->users[1], 'api')
            ->json('POST', '/api/project/create');

        $response->assertExactJson([
            "errors" => [
                "name" => ["The name field is required."],
                "project_manager_id" => ["The project manager id field is required."],
            ],
            "message" => "The given data was invalid."
        ]);

        $response = $this
            ->actingAs($this->users[1], 'api')
            ->json('POST', '/api/project/create', [ 'name' => $new_project['name'], 'project_manager_id' => $new_project['project_manager_id'] ]);

        $response
            ->assertJson([ 'name' => $new_project['name'], 'project_manager_id' => $new_project['project_manager_id'] ]);
    }
    
    /**
     * Testing adding users to a project
     *
     * @return void
     */
    public function testAddingUserToProjectAsAdminOrManager() {
        $admin = $this->users[1];
        $user = $this->users[0];

        $project = Project::where('project_manager_id', '!=', $user->id)->first();
        $project_user = Project::where('project_manager_id', $user->id)->first();
        $test_user = factory(User::class)->create();
        
        $p_user = [
            'user_id'       => $test_user->id,
            'project_id'    => $project->id,
        ];

        $response = $this
            ->actingAs($admin, 'api')
            ->json('POST', '/api/project/' . $p_user['project_id'] . '/user/' . $p_user['user_id']);

        $response
            ->assertSuccessful()
            ->assertJson($p_user);
        
        $this->assertDatabaseHas('project_user', $p_user);

        $new_user = factory(User::class)->create();

        $new_p_user = [
            'user_id'       => $new_user->id,
            'project_id'    => $project_user->id,
        ];

        $response = $this
            ->actingAs($user, 'api')
            ->json('POST', '/api/project/' . $project_user->id . '/user/' . $new_p_user['user_id']);

        $response
            ->assertSuccessful()
            ->assertJson($new_p_user);
        
        $this->assertDatabaseHas('project_user', $new_p_user);

        $this->assertFalse($user->id === $project->project_manager_id);

        $response = $this
            ->actingAs($user, 'api')
            ->json('POST', '/api/project/' . $project->id . '/user/' . $new_p_user['user_id']);
        
        $response->assertStatus(403)->assertJson(['message' => 'Forbidden, you must be a project manager or an admin']);
        
        $this->assertDatabaseHas('project_user', $new_p_user);
        
        $no_such_project = '1337';
        $this->assertDatabaseMissing('projects', ['id' => $no_such_project]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('POST', 'api/project/' . $no_such_project . '/user/' . $p_user['user_id']);

        $response->assertExactJson([
            'project_id' => [
                'The selected project id is invalid.'
            ]
        ]);

        $no_such_user = '1337';
        $this->assertDatabaseMissing('users', ['id' => $no_such_user]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('POST', 'api/project/' . $p_user['project_id'] . '/user/' . $no_such_user);

        $response->assertExactJson([
            'user_id' => [
                'The selected user id is invalid.'
            ]
        ]);
            
    }

    /**
     * Testing adding the same user to a project twice
     *
     * @return void
     */
    public function testAddingUserToProjectTwice() {
        $admin = $this->users[1];

        //$project = factory(Project::class)->create();
        $project = Project::get()->first();
        $user = factory(User::class)->create();

        $p_user = [
            'user_id'       => $user->id,
            'project_id'    => $project->id,
        ];

        $this->assertDatabaseMissing('project_user', $p_user);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('POST', '/api/project/' . $p_user['project_id'] . '/user/' . $p_user['user_id']);
            
        $this->assertDatabaseHas('project_user', $p_user);
            
        $response = $this
            ->actingAs($admin, 'api')
            ->json('POST', '/api/project/' . $p_user['project_id'] . '/user/' . $p_user['user_id']);

        $response->assertExactJson([
            'user_id' => [
                'The user is already in that project.'
            ]
        ]);
    }

    /**
     * Testing delete user
     *
     * @return void
     */
    public function testDeleteUserFromProject()
    {
        $project = factory(Project::class)->create();
        $user = factory(User::class)->create();

        $projectUser = factory(ProjectUser::class)->create([
                'user_id'       => $user->id,
                'project_id'    => $project->id
            ]);

        $response = $this
            ->actingAs($this->users[1], 'api')
            ->json('DELETE', '/api/project/' . $project->id . '/user/' . $user->id);

        $response->assertJson(['success' => 1]);
    }

    /**
     * Testing delete a user from a project they're not a part of
     *
     * @return void
     */
    public function testDeleteUserFromProjectTheyAreNotPartOf()
    {
        $project = factory(Project::class)->create();
        $project2 = factory(Project::class)->create();
        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        factory(ProjectUser::class)->create([
            'user_id'       => $user->id,
            'project_id'    => $project2->id
        ]);

        factory(ProjectUser::class)->create([
            'user_id'       => $user2->id,
            'project_id'    => $project->id
        ]);

        $response = $this
            ->actingAs($this->users[1], 'api')
            ->json('DELETE', '/api/project/' . $project->id . '/user/' . $user->id);

        $response
            ->assertJson([
                "user_id" => [
                    "The user is not in that project."
                ]
            ]);
    }

    /**
     * Test for deleting a project
     *
     * @return void
     */
    public function testDeleteProject() {

        $projectToDelete = factory(Project::class)->create();

        $this->assertDatabaseHas('projects', ['id' => $projectToDelete->id]);

        $unauthUser = $this->users[0];
        $admin = $this->users[1];

        $response = $this
            ->actingAs($unauthUser, 'api')
            ->json('DELETE', '/api/project/' . $projectToDelete->id);

        $response->assertStatus(403);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('DELETE', '/api/project/' . $projectToDelete->id);

        $response->assertJson(['success' => 1]);

        $this->assertDatabaseMissing('projects', ['id' => $projectToDelete->id]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('DELETE', '/api/project/a');

        $response->assertJson([
            "id" => [
                "The id must be a number."
            ]
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('DELETE', '/api/project/120940214912489127');

        $response->assertJson(['id' => ['The selected id is invalid.']]);

    }

    /**
     * Test for updating a project (name)
     *
     * @return void
     */
    public function testUpdate() {

        $not_admin = $this->users[0];
        $admin = $this->users[1];

        $project = Project::get()->first();

        $this->assertDatabaseHas('projects', ['id' => $project['id']]);

        $updated_name = "This is a test.";

        $this->assertDatabaseMissing('projects', [
            'id'    => $project['id'],
            'name'  => $updated_name,
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('PUT', '/api/project/' . $project['id'], ['name' => $updated_name]);
        
        $response->assertSuccessful()->assertJson(['success' => 1]);
        $this->assertDatabaseHas('projects', [
            'id'    => $project['id'],
            'name'  => $updated_name,
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('PUT', '/api/project/' . $project['id'], []);
        
        $response->assertExactJson([
            'errors' => [
                'name' => [
                    'The name field is required.'
                ],
            ],
            'message' => 'The given data was invalid.'
        ]);

        $response = $this
            ->actingAs($not_admin, 'api')
            ->json('PUT', '/api/project/' . $project['id'], ['name' => $updated_name]);

        
        $response->assertStatus(403)->assertExactJson([
            'message' => 'Forbidden, you must be an admin',
        ]);
    }

}
