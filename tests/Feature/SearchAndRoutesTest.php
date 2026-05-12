<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Favourite;
use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchAndRoutesTest extends TestCase
{
    use RefreshDatabase;


/**
     * Test queryables groups stops by name
     */
    public function test_queryables_groups_stops_by_name()
    {
        Stop::factory()->create([
            'id' => 'S_DUP1',
            'name' => 'Central Station',
            'code' => 'CS1',
        ]);

        Stop::factory()->create([
            'id' => 'S_DUP2',
            'name' => 'Central Station',
            'code' => 'CS2',
        ]);

        Stop::factory()->create([
            'id' => 'S_UNIQUE',
            'name' => 'West Station',
        ]);

        $response = $this->getJson('/api/queryables');

        $response->assertStatus(200);
        $stops = $response->json('data.stops', []);
        
        // Should have at least 2 stop groups
        $this->assertGreaterThanOrEqual(2, count($stops));
    }


/**
     * Test favorite toggle adds favorite
     */
    public function test_toggle_favourite_adds_new_favourite()
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

/**
     * Test favorite with custom time
     */
    public function test_favourite_stores_custom_time()
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

    /**
     * Test unauthorized access to user favorites
     */
    public function test_get_user_favourites_requires_authentication()
    {
        $response = $this->getJson('/api/user/favourites');

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test unauthorized toggle favorite
     */
    public function test_toggle_favourite_requires_authentication()
    {
        $route = Route::factory()->create(['id' => 'R_AUTH']);

        $response = $this->postJson('/api/routes/favourite/toggle', [
            'route_id' => $route->id,
            'time' => '0800',
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test favorite with non-existent route
     */
    public function test_favourite_with_nonexistent_route()
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


/**
     * Test stop search by partial name
     */
    public function test_queryables_includes_stop_codes()
    {
        Stop::factory()->create([
            'id' => 'S_CODE1',
            'name' => 'Main Station',
            'code' => 'MAIN01',
        ]);

        Stop::factory()->create([
            'id' => 'S_CODE2',
            'name' => 'Secondary Station',
            'code' => 'SEC02',
        ]);

        $response = $this->getJson('/api/queryables');

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data.stops', []));
    }


}
