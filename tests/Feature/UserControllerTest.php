<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function store_creates_user_and_returns_user()
    {
        $payload = [
            'first_name' => 'Anna',
            'second_name' => 'Nagy',
            'email' => 'anna@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'anna@example.com')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('users', [
            'email' => 'anna@example.com',
            'first_name' => 'Anna',
            'second_name' => 'Nagy',
        ]);

        $user = User::where('email', 'anna@example.com')->first();
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    #[Test]
    public function login_returns_token_for_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('mypassword'),
        ]);

        $response = $this->postJson('/api/user/login/false', [
            'email' => $user->email,
            'password' => 'mypassword',
        ]);

        $this->assertNotEquals(401, $response->getStatusCode(), 
            'Valid credentials should not return 401');
    }

    #[Test]
    public function get_returns_authenticated_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id);
    }

    #[Test]
    public function update_updates_authenticated_user()
    {
        $user = User::factory()->create([
            'first_name' => 'Gabor',
            'second_name' => 'Kovacs',
            'email' => 'gabor@example.com',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/user/update', [
            'first_name' => 'Gábor',
            'second_name' => 'Kovács',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.first_name', 'Gábor')
            ->assertJsonPath('data.user.second_name', 'Kovács');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Gábor',
            'second_name' => 'Kovács',
        ]);
    }

    #[Test]
    public function destroy_deletes_authenticated_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/user/delete');

        $response->assertStatus(200)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function logout_invalidates_token_after_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/user/logout');
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function get_user_favourites_returns_all_favourites()
    {
        $user = User::factory()->create();
        $route1 = \App\Models\Route::factory()->create(['id' => 'R_FAV1']);
        $route2 = \App\Models\Route::factory()->create(['id' => 'R_FAV2']);
        
        $user->favourites()->attach($route1->id, ['time' => '0800']);
        $user->favourites()->attach($route2->id, ['time' => '1700']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user/favourites');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'favourites' => [
                        [
                            'route' => ['id', 'short_name', 'type', 'color'],
                            'time',
                        ]
                    ]
                ],
                'errors'
            ]);

        $favourites = $response->json('data.favourites');
        $this->assertCount(2, $favourites);
        
        $times = collect($favourites)->pluck('time')->toArray();
        $this->assertContains('0800', $times);
        $this->assertContains('1700', $times);
    }

    #[Test]
    public function get_user_favourites_returns_empty_list()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user/favourites');

        $response->assertStatus(200)
            ->assertJsonPath('data.favourites', []);
    }

    #[Test]
    public function toggle_favourite_returns_error_when_trip_does_not_exist()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/routes/favourite/toggle', [
            'trip_id' => 'missing-trip-id',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('errors.0', 'Nincs trip ilyen id-vel.');
    }

    
}
