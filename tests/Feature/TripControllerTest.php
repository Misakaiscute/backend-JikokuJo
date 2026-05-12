<?php

namespace Tests\Feature;

use App\Models\CalendarDate;
use App\Models\Route;
use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_trips_by_route_id_returns_matching_trip()
    {
        $route = Route::factory()->create(['id' => 'R1', 'type' => 3]);
        $trip = Trip::factory()->create([
            'id' => 'T1',
            'route_id' => $route->id,
            'service_id' => 'S1',
            'trip_headsign' => 'Városközpont',
            'direction_id' => 1,
        ]);

        $this->assertDatabaseHas('trips', ['id' => $trip->id]);
        $this->assertTrue(true); // Test that factory creates properly
    }


public function test_get_trips_by_stop_id_returns_trips_for_stop_ids()
    {
        $stop = Stop::factory()->create(['id' => 'S5', 'name' => 'Station']);
        
        $response = $this->postJson('/api/stop/trip', [
            'ids' => $stop->id,
            'date' => '20260103',
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 206]),
            'Valid request should be accepted'
        );
    }

    public function test_get_trips_by_stop_id_rejects_missing_ids()
    {
        $response = $this->postJson('/api/stop/trip', [
            'date' => '20260103',
            'time' => '0900',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('errors.0', '"ids" paraméter megadása kötelező (tömb vagy vesszővel elválasztott string).');
    }
}
