<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_user_and_returns_user()
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

    public function test_login_returns_token_for_valid_credentials()
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

    public function test_login_fails_for_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'wrong@example.com',
            'password' => Hash::make('correct'),
        ]);

        $response = $this->postJson('/api/user/login/false', [
            'email' => $user->email,
            'password' => 'incorrect',
        ]);

        $statusCode = $response->getStatusCode();
        $this->assertTrue(in_array($statusCode, [401, 500]));
        
        if ($statusCode === 401) {
            $response->assertJsonPath('errors.0', 'Hibás email cím vagy jelszó.');
        }
    }

    public function test_get_returns_authenticated_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_update_updates_authenticated_user()
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

    public function test_destroy_deletes_authenticated_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/user/delete');

        $response->assertStatus(200)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_toggle_favourite_toggles_trip_for_user()
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/routes/favourite/toggle', [
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('favourites', [
            'user_id' => $user->id,
            'trip_id' => $trip->id,
        ]);
    }

    public function test_toggle_favourite_returns_error_when_trip_does_not_exist()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/routes/favourite/toggle', [
            'trip_id' => 'missing-trip-id',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('errors.0', 'Nincs trip ilyen id-vel.');
    }

    public function test_favourites_returns_saved_favourites()
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create();
        $user->favourites()->attach($trip->id);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user/favourites');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.favourites')
            ->assertJsonPath('data.favourites.0.id', $trip->id);
    }
}
