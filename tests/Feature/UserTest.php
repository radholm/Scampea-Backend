<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\User;
use Faker;
use App\Role;

class UserTest extends TestCase
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
    }

     /**
      * Test the user auth route
      *
      * @return void
      */
    public function testUserGet()
    {
        foreach ($this->users as $user) {
            $response = $this
                ->actingAs($user, 'api')
                ->json('GET', '/api/user');

            $response
                ->assertSuccessful()
                ->assertJson($user->toArray());
        }
    }

     /**
      * Test getting info for all users as a non-admin
      *
      * @return void
      */
    public function testGetAllUsersNonAdmin()
    {
        $response = $this
            ->actingAs($this->users[0], 'api')
            ->json('GET', '/api/users');

        // Everyone can access all users right now
        $response->assertStatus(200);
    }

     /**
      * Test getting info for all users as a admin
      *
      * @return void
      */
    public function testGetAllUsersAdmin()
    {
        $response = $this
            ->actingAs($this->users[1], 'api')
            ->json('GET', '/api/users');

        $response
            ->assertJson(User::all()->toArray());
    }

     /**
      * Test creation of user when not logged in
      *
      * @return void
      */
    public function testCreateUserNotLoggedIn()
    {
        $response = $this->json('POST', '/api/user/create');

        $response->assertStatus(401);
    }

     /**
      * Test creation of user when not authorized
      *
      * @return void
      */
    public function testCreateUserNonAdmin()
    {
        $response = $this
            ->actingAs($this->users[0], 'api')
            ->json('POST', '/api/user/create');

        $response->assertStatus(403);
    }

     /**
      * Test creation of user
      *
      * @return void
      */
    public function testCreateUserAsAdmin()
    {
        $role_ids = Role::pluck('id');

        $test_picture = $this->getBase64Image('test1.png');

        $testUser = [
            'username'      => $this->faker->unique()->userName,
            'first_name'    => $this->faker->firstName,
            'last_name'     => $this->faker->lastName,
            'permission'    => $this->faker->numberBetween($min = 0, $max = 1),
            'password'      => 'secret',
            'password_confirmation' => 'secret',
            'role'          => $role_ids->random(),
            'picture'       => $test_picture['picture'],
        ];

        $response = $this
            ->actingAs($this->users[1], 'api')
            ->json('POST', '/api/user/create', []);

        $response
            ->assertExactJson([
                "errors" => [
                    "username" => [
                        "The username field is required."
                    ],
                    "first_name" => [
                        "The first name field is required."
                    ],
                    "last_name" => [
                        "The last name field is required."
                    ],
                    "password" => [
                        "The password field is required."
                    ],
                    "role" => [
                        "The role field is required."
                    ]
                ],
                "message" => "The given data was invalid."
            ]);

        $response = $this
            ->actingAs($this->users[1], 'api')
            ->json('POST', '/api/user/create', $testUser);

        $picture_path = public_path('pictures/' . $testUser['username'] . '.' . $test_picture['extension']);
        $database_picture_path = '/pictures/' . $testUser['username'] . '.' . $test_picture['extension'];

        $response
            ->assertJson([
                'username'      => $testUser['username'],
                'first_name'    => $testUser['first_name'],
                'last_name'     => $testUser['last_name'],
                'permission'    => $testUser['permission'],
                'picture'       => $database_picture_path,
            ]);

        $this->assertDatabaseHas('users', [
            'username'  => $testUser['username'],
            'picture'   => $database_picture_path,
        ]);

        $this->assertFileExists($picture_path);

        \File::delete($picture_path);
        $this->assertFalse(\File::exists($picture_path));
    }

    /**
     * Test for deleting a user
     *
     * @return void
     */
    public function testDeleteUser()
    {
        $admin = $this->users[1];
        $not_admin = $this->users[0];
        $user_to_delete = factory(User::class)->create();

        $this->assertDatabaseHas('users', ['id' => $user_to_delete->id]);

        $response = $this
            ->actingAs($not_admin, 'api')
            ->json('DELETE', '/api/user/delete/' . $user_to_delete->id);

        $response->assertStatus(403);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('DELETE', '/api/user/delete/' . $user_to_delete->id);
        
        $response->assertSuccessful()->assertExactJson(['success' => 1]);
        $this->assertDatabaseMissing('users', ['id' => $user_to_delete->id]);

    }

    /**
     * Test for changing a password
     *
     * @return void
     */
    public function testChangePassword() {
        $testUser = $this->users[0];

        $response = $this
            ->actingAs($testUser, 'api')
            ->json('PUT', '/api/user/changePassword', []);

        $response
            ->assertExactJson([
                "errors" => [
                    "old_password" => [
                        "The old password field is required."
                    ],
                    "new_password" => [
                        "The new password field is required."
                    ],
                ],
                "message" => "The given data was invalid."
            ]);

        $postData = [
            'old_password'  => 'WRONG PASSWORD',
            'new_password'  => 'test',
            'new_password_confirmation'  => 'test',
        ];

        $response = $this
            ->actingAs($testUser, 'api')
            ->json('PUT', '/api/user/changePassword', $postData);

        $response
            ->assertJson(['success' => false, 'error' => 'Password missmatch'])
            ->assertStatus(400);

        $postData['old_password'] = 'secret';
        $oldPassword = $testUser->password;

        $response = $this
            ->actingAs($testUser, 'api')
            ->json('PUT', '/api/user/changePassword', $postData);

        $response->assertJson(['success' => true]);
        $response->assertStatus(200);

        $newPassword = $testUser->password;

        $this->assertDatabaseHas('users', [
            'username' => $testUser->username,
            'password' => $newPassword,
        ]);

        $this->assertFalse($newPassword === $oldPassword);

    }

    /**
     * Test for updating a user as admin
     *
     * @return void
     */
    public function testUpdate() {
        $admin = $this->users[1];
        $not_admin = $this->users[0];

        $new_user = User::get()->first();
        
        $response = $this
            ->actingAs($not_admin, 'api')
            ->json('PUT', '/api/user/update/' . $new_user['id'], []);
        
        $response->assertStatus(403)->assertExactJson([
            'message' => 'Forbidden, you must be an admin',
        ]);

        $new_data = [
            'username'              => 'taddcvaasdvadv',
            'first_name'            => 'test',
            'last_name'             => 'test',
            'expertise'             => 'none',
            'password'              => 'Thisisanewpassword',
            'password_confirmation' => 'Thisisanewpassword',
        ];

        $old_password = User::where('id', $new_user['id'])->select('password');

        $test_pic = $this->getBase64Image('test1.png');

        $new_new_data = [
            'expertise' => 'levelup',
            'picture'   => $test_pic['picture'],
        ];

        $db_pic_path = '/pictures/' . $new_user['username'] . '.' . $test_pic['extension'];

        $new_new_db_data = [
            'id'        => $new_user['id'],
            'expertise' => 'levelup',
            'picture'   => $db_pic_path,
        ];

        $this->assertDatabaseMissing('users', $new_new_db_data);

        $response = $this
            ->actingAs($admin, 'api')
            ->json('PUT', '/api/user/update/' . $new_user['id'], $new_new_data);

        $response->assertSuccessful()->assertJson(['success' => 1]);

        $this->assertDatabaseHas('users', $new_new_db_data);
        $file_path = public_path('pictures/' . $new_user['username'] . '.' . $test_pic['extension']);
        $this->assertTrue(\File::exists($file_path));

        $new_pic = $this->getBase64Image('test2.png');

        $last_data = [
            'username'  => 'somethingelse',
            'picture'   => $new_pic['picture'],
            'expertise' => 'anonymous',
            'last_name' => 'got married',
        ];

        $last_db_pic_path = '/pictures/' . $last_data['username'] . '.' . $new_pic['extension'];

        $last_db_data = [
            'id'        => $new_user['id'],
            'username'  => $last_data['username'],
            'picture'   => $last_db_pic_path,
            'expertise' => $last_data['expertise'],
            'last_name' => $last_data['last_name'],
        ];

        $response = $this
            ->actingAs($admin, 'api')
            ->json('PUT', '/api/user/update/' . $new_user['id'], $last_data);

        $response->assertSuccessful()->assertJson(['success' => 1]);
        $this->assertDatabaseHas('users', $last_db_data);

        $new_file_path = public_path('pictures/' . $last_data['username'] . '.' . $new_pic['extension']);

        $this->assertTrue(\File::exists($file_path));
        $this->assertTrue(\File::exists($new_file_path));

        \File::delete($file_path);
        \File::delete($new_file_path);

        $this->assertFalse(\File::exists($file_path));
        $this->assertFalse(\File::exists($new_file_path));

        $new_password = User::where('id', $new_user['id'])->select('password');
        $this->assertFalse($old_password === $new_password);

        $wrong_pass = [
            'username'              => 'teseasdfasdfccncncnc',
            'password'              => 'test',
            'password_confirmation' => 'nottest'
        ];

        $response = $this
            ->actingAs($admin, 'api')
            ->json('PUT', '/api/user/update/' . $new_user['id'], $wrong_pass);

        $response->assertExactJson([
            "errors" => [
                "password" => [
                    "The password confirmation does not match."
                ],
            ],
            "message" => "The given data was invalid."
        ]);

        $too_short_pass = [
            'username'              => 'teseasdadf.c.ccncncnc',
            'password'              => 't',
            'password_confirmation' => 't'
        ];

        $response = $this
            ->actingAs($admin, 'api')
            ->json('PUT', '/api/user/update/' . $new_user['id'], $too_short_pass);

        $response->assertExactJson([
            "errors" => [
                "password" => [
                    "The password must be at least 3 characters."
                ],
            ],
            "message" => "The given data was invalid."
        ]);

        $response = $this
            ->actingAs($not_admin, 'api')
            ->json('PUT', '/api/user/update/' . $new_user['id'], $new_data);

        $response->assertStatus(403);
    }

    /**
     * Test for updating a user as user
     *
     * @return void
     */
    public function testUpdateUser() {
        $user = $this->users[0];
        $test_p1 = 'test1.png';
        $test_p2 = 'test2.png';

        $test_picture = $this->getBase64Image($test_p1);

        $expertise = 'testerssssss';
        $test_data = [
            'picture'   => $test_picture['picture'],
            'expertise' => $expertise,
        ];

        $response = $this
            ->actingAs($user, 'api')
            ->json('PUT', '/api/user/update', $test_data);

        $response->assertSuccessful()->assertJson(['success' => 1]);
        $picture_path = public_path('pictures/' . $user->username . '.' . $test_picture['extension']);
        $this->assertFileExists($picture_path);

        $database_picture_path = '/pictures/' . $user->username . '.' . $test_picture['extension'];

        $this->assertDatabaseHas('users', [
            'id'        => $user->id,
            'expertise' => $expertise,
            'picture'   => $database_picture_path,
        ]);

        $first_p_size = \File::size($picture_path);

        $new_test_picture = $this->getBase64Image($test_p2);
        $new_expertise = 'something else';

        $test_data['expertise'] = $new_expertise;
        $test_data['picture'] = $new_test_picture['picture'];

        $response = $this
            ->actingAs($user, 'api')
            ->json('PUT', '/api/user/update', $test_data);

        $response->assertSuccessful()->assertJson(['success' => 1]);
        $this->assertFileExists($picture_path);

        $this->assertDatabaseHas('users', [
            'id'        => $user->id,
            'expertise' => $new_expertise,
            'picture'   => $database_picture_path,
        ]);

        $second_p_size = \File::size($picture_path);

        $this->assertFalse($first_p_size === $second_p_size);

        \File::delete($picture_path);
        $this->assertFalse(\File::exists($picture_path));
    }
    
    /**
     * Method to create a base64 encoded image
     *
     * @return array with picture blob and extension 
     */
    public function getBase64Image($pic_name) {
        $p_path = public_path('pictures/' . $pic_name);
        $this->assertTrue(\File::exists($p_path));
        $p_type = pathinfo($p_path, PATHINFO_EXTENSION);
        $p_data = \File::get($p_path);
        $p_base64 = 'data:image/' . $p_type . ';base64,' . base64_encode($p_data);

        return ['picture' => $p_base64, 'extension' => $p_type];
    }
}
