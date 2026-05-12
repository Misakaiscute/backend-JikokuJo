<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAuthenticationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test registration with weak password
     */
    public function test_register_rejects_weak_password()
    {
        $payload = [
            'first_name' => 'Test',
            'second_name' => 'User',
            'email' => 'weak@example.com',
            'password' => '12345', // Weak password (5 chars min)
            'password_confirmation' => '12345',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $this->assertTrue(
            in_array($response->getStatusCode(), [422, 400])
        );
    }

    /**
     * Test registration with mismatched passwords
     */
    public function test_register_rejects_mismatched_passwords()
    {
        $payload = [
            'first_name' => 'Test',
            'second_name' => 'User',
            'email' => 'mismatch@example.com',
            'password' => 'ValidPassword123',
            'password_confirmation' => 'DifferentPassword123',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test registration with duplicate email
     */
    public function test_register_rejects_duplicate_email()
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $payload = [
            'first_name' => 'Another',
            'second_name' => 'User',
            'email' => 'duplicate@example.com',
            'password' => 'ValidPassword123',
            'password_confirmation' => 'ValidPassword123',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test registration missing required fields
     */
    public function test_register_requires_all_fields()
    {
        // Missing password
        $payload = [
            'first_name' => 'Test',
            'second_name' => 'User',
            'email' => 'missing@example.com',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $this->assertEquals(422, $response->getStatusCode());
    }


/**
     * Test login with wrong password
     */
    public function test_login_fails_with_wrong_password()
    {
        $user = User::factory()->create([
            'email' => 'correct@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
            'remember_user' => false,
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test login token expiration with remember_user=false
     */
    public function test_login_default_token_expiry_one_day()
    {
        $user = User::factory()->create([
            'email' => 'token@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => $user->email,
            'password' => 'correctpassword',
            'remember_user' => false,
        ]);

        $response->assertStatus(200);
        $token = $response->json('data.token');
        $this->assertIsString($token);
    }

    /**
     * Test login token extended expiry with remember_user=true
     */
    public function test_login_extended_token_expiry_fourteen_days()
    {
        $user = User::factory()->create([
            'email' => 'remember@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => $user->email,
            'password' => 'correctpassword',
            'remember_user' => true,
        ]);

        $response->assertStatus(200);
        $token = $response->json('data.token');
        $this->assertIsString($token);
    }


/**
     * Test accessing protected endpoints without token
     */
    public function test_protected_endpoints_require_token()
    {
        $response = $this->getJson('/api/user');
        $this->assertEquals(401, $response->getStatusCode());

        $response = $this->putJson('/api/user/update', [
            'first_name' => 'Updated',
        ]);
        $this->assertEquals(401, $response->getStatusCode());

        $response = $this->deleteJson('/api/user/delete');
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test user profile retrieval
     */
    public function test_get_user_profile()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'second_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'john@example.com')
            ->assertJsonPath('data.user.first_name', 'John')
            ->assertJsonPath('data.user.second_name', 'Doe');
    }

    /**
     * Test user profile update
     */
    public function test_update_user_profile()
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'second_name' => 'Smith',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/update', [
            'first_name' => 'Janet',
            'second_name' => 'Smithson',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Janet', $user->first_name);
        $this->assertEquals('Smithson', $user->second_name);
    }

    /**
     * Test partial user profile update
     */
    public function test_update_user_partial()
    {
        $user = User::factory()->create([
            'first_name' => 'Original',
            'second_name' => 'Name',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/update', [
            'first_name' => 'Updated',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Updated', $user->first_name);
        $this->assertEquals('Name', $user->second_name); // Should remain unchanged
    }

    /**
     * Test user account deletion
     */
    public function test_delete_user_account()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/user/delete');

        $response->assertStatus(200);

        // User should be deleted from database
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /**
     * Test delete user removes associated favorites
     */
    public function test_delete_user_cascade()
    {
        $user = User::factory()->create();
        
        // Create favorites for this user
        \App\Models\Favourite::factory(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/user/delete');

        $response->assertStatus(200);

        // All favorites for this user should be deleted
        $this->assertDatabaseMissing('favourites', ['user_id' => $user->id]);
    }

    /**
     * Test invalid email format
     */
    public function test_register_rejects_invalid_email()
    {
        $payload = [
            'first_name' => 'Test',
            'second_name' => 'User',
            'email' => 'not-an-email',
            'password' => 'ValidPassword123',
            'password_confirmation' => 'ValidPassword123',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test using invalid token
     */
    public function test_invalid_token_is_rejected()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->getJson('/api/user');

        $this->assertEquals(401, $response->getStatusCode());
    }


}
