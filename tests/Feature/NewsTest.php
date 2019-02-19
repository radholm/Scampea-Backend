<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\News;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    protected $users = [];

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

        factory(News::class, 10)->create();
    }

    /**
     * Test getting user news when not logged in
     *
     * @return void
     */
    public function testGetUserNewsNotLoggedIn()
    {
        $response = $this->json('GET', '/api/news');

        $response->assertStatus(401);
    }

    /**
     * Test getting user news when logged in
     *
     * @return void
     */
    public function testGetUserNewsLoggedIn()
    {
        foreach ($this->users as $user) {
            $response = $this
                ->actingAs($user, 'api')
                ->json('GET', '/api/news');

            $response->assertSuccessful();
        }
    }

    /**
     * Test creating a news item when not logged in 
     *
     * @return void
     */
    public function testCreateNewsNotLoggedIn()
    {
       $response = $this->json('POST', '/api/user/create');
       
       $response->assertStatus(401);
    }

    /**
     * Test creating a news item when not admin
     *
     * @return void
     */
    public function testCreateNewsNotAdmin()
    {
       $response = $this
        ->actingAs($this->users[0], 'api')
        ->json('POST', '/api/user/create');

       $response->assertStatus(403);
    }

    /**
     * Test creating a news item while not an admin
     *
     * @return void
     */
    public function testCreateNewsAsAdmin()
    {
        $testUser = $this->users[1];
        
        $testData = [
            'title' => 'Title',
            'text' => 'This is some text',
        ];

        $response = $this
            ->actingAs($testUser, 'api')
            ->json('POST', '/api/news/create', $testData);

        $formatEntry = function($id) use ($testData) {
            return [
              'title' => $testData['title'],
              'text' => $testData['text'],
              'user_id' => $id,
           ];
        };

        $correctData = User::pluck('id')->map($formatEntry);

        $response
            ->assertJson($correctData->toArray());
    }
}
