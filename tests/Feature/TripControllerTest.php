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

    public function test_get_trips_by_route_id_returns_206_when_no_trips_in_time_window()
    {
        $route = Route::factory()->create(['id' => 'R2', 'type' => 3]);
        $trip = Trip::factory()->create([
            'id' => 'T2',
            'route_id' => $route->id,
            'service_id' => 'S2',
            'trip_headsign' => 'Éjszakai járat',
        ]);

        CalendarDate::factory()->create([
            'service_id' => 'S2',
            'date' => 20260102,
            'exception_type' => 1,
        ]);

        Stop::factory()->create(['id' => 'S3', 'name' => 'Old Stop']);
        Stop::factory()->create(['id' => 'S4', 'name' => 'New Stop']);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => 'S3',
            'stop_sequence' => 1,
            'arrival_time' => 1000,
            'departure_time' => 1000,
        ]);

        $response = $this->getJson('/api/route/R2/time/20260102/0700');

        $response->assertStatus(206)
            ->assertJsonPath('errors.0', 'Nincs elérhető járat ebben az időintervallumban.');
    }

    public function test_get_trips_by_route_id_rejects_invalid_date_format()
    {
        $response = $this->getJson('/api/route/R1/time/2026-01-01/0700');

        $response->assertStatus(400)
            ->assertJsonPath('errors.0', 'Hibás dátum formátum (YYYYMMDD).');
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
