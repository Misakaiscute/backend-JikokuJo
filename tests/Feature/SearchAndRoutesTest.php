<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchAndRoutesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function toggle_favourite_adds_new_favourite()
    {
        $user = User::factory()->create();
        $route = Route::factory()->create(['id' => 'R_FAV1']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/routes/favourite/toggle', [
            'route_id' => $route->id,
            'time' => '0800',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('favourites', [
            'user_id' => $user->id,
            'route_id' => $route->id,
            'time' => '0800',
        ]);
    }

    #[Test]
    public function favourite_stores_custom_time()
    {
        $user = User::factory()->create();
        $route = Route::factory()->create(['id' => 'R_TIME']);

        Sanctum::actingAs($user);

        $customTime = '1730'; // 5:30 PM

        $response = $this->postJson('/api/routes/favourite/toggle', [
            'route_id' => $route->id,
            'time' => $customTime,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('favourites', [
            'user_id' => $user->id,
            'route_id' => $route->id,
            'time' => $customTime,
        ]);
    }

    #[Test]
    public function get_user_favourites_requires_authentication()
    {
        $response = $this->getJson('/api/user/favourites');

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function toggle_favourite_requires_authentication()
    {
        $route = Route::factory()->create(['id' => 'R_AUTH']);

        $response = $this->postJson('/api/routes/favourite/toggle', [
            'route_id' => $route->id,
            'time' => '0800',
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function favourite_with_nonexistent_route()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/routes/favourite/toggle', [
            'route_id' => 'NONEXISTENT',
            'time' => '0800',
        ]);

        // Should fail or be handled appropriately
        $this->assertTrue(
            in_array($response->getStatusCode(), [400, 404, 422])
        );
    }


}
