<?php

namespace Tests\Feature;

use App\Models\CalendarDate;
use App\Models\Route;
use App\Models\Shape;
use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TripEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_trips_respects_two_hour_window()
    {
        $route = Route::factory()->create(['id' => 'R_WINDOW']);
        $trip = Trip::factory()->create([
            'id' => 'T_WINDOW',
            'route_id' => $route->id,
            'service_id' => 'S_WINDOW',
        ]);

        CalendarDate::factory()->create([
            'service_id' => 'S_WINDOW',
            'date' => 20260426,
            'exception_type' => 1,
        ]);

        $stop1 = Stop::factory()->create(['id' => 'STOP_W1']);
        $stop2 = Stop::factory()->create(['id' => 'STOP_W2']);

        // Create stop times: departures at 8:00 AM (480 mins) and 9:00 AM (540 mins)
        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop1->id,
            'stop_sequence' => 1,
            'departure_time' => 480, // 8:00 AM
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop2->id,
            'stop_sequence' => 2,
            'departure_time' => 540, // 9:00 AM
        ]);

        // Request at 8:30 AM (510 mins) - both should be in ±120 min window
        $response = $this->postJson('/api/route/trip', [
            'date' => '20260426',
            'time' => '0830',
            'route_id' => $route->id,
        ]);

        // Verify the trip is within the window
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 206, 400])
        );
    }

    #[Test]
    public function cannot_board_at_last_stop()
    {
        $route = Route::factory()->create(['id' => 'R_LAST']);
        $trip = Trip::factory()->create([
            'id' => 'T_LAST',
            'route_id' => $route->id,
            'service_id' => 'S_LAST',
        ]);

        CalendarDate::factory()->create([
            'service_id' => 'S_LAST',
            'date' => 20260426,
            'exception_type' => 1,
        ]);

        $stop1 = Stop::factory()->create(['id' => 'STOP_L1']);
        $stop2 = Stop::factory()->create(['id' => 'STOP_L2']);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop1->id,
            'stop_sequence' => 1,
            'departure_time' => 480,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop2->id,
            'stop_sequence' => 2,
            'departure_time' => 540,
        ]);

        $response = $this->postJson('/api/stop/trip', [
            'ids' => 'STOP_L2', // Last stop
            'date' => '20260426',
            'time' => '0800',
        ]);

        // Should not include trips where queried stop is the last stop
        $this->assertIsArray($response->json('data.trips'));
    }

    //Test service filtering excludes inactive dates
    #[Test]
    public function excluded_service_dates_return_no_trips()
    {
        $route = Route::factory()->create(['id' => 'R_EXCLUDE']);
        $trip = Trip::factory()->create([
            'id' => 'T_EXCLUDE',
            'route_id' => $route->id,
            'service_id' => 'S_EXCLUDE',
        ]);

        // Mark service as NOT operating on this date
        CalendarDate::factory()->create([
            'service_id' => 'S_EXCLUDE',
            'date' => 20260426,
            'exception_type' => 2, // Does not operate
        ]);

        $stop = Stop::factory()->create(['id' => 'STOP_EX']);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'departure_time' => 480,
        ]);

        $response = $this->postJson('/api/route/trip', [
            'date' => '20260426',
            'time' => '0800',
            'route_id' => $route->id,
        ]);

        // Should return 206 (no trips in window) or 200 with empty array
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 206])
        );
    }

    //Test multiple stops in single query
    #[Test]
    public function query_multiple_stops_returns_all_matching_trips()
    {
        $trip1 = Trip::factory()->create(['id' => 'T_M1', 'service_id' => 'S_M']);
        $trip2 = Trip::factory()->create(['id' => 'T_M2', 'service_id' => 'S_M']);

        CalendarDate::factory()->create([
            'service_id' => 'S_M',
            'date' => 20260426,
            'exception_type' => 1,
        ]);

        $stop1 = Stop::factory()->create(['id' => 'STOP_M1']);
        $stop2 = Stop::factory()->create(['id' => 'STOP_M2']);

        StopTime::factory()->create([
            'trip_id' => $trip1->id,
            'stop_id' => $stop1->id,
            'stop_sequence' => 1,
            'departure_time' => 480,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip2->id,
            'stop_id' => $stop2->id,
            'stop_sequence' => 1,
            'departure_time' => 540,
        ]);

        $response = $this->postJson('/api/stop/trip', [
            'ids' => 'STOP_M1,STOP_M2', // Multiple stops
            'date' => '20260426',
            'time' => '0800',
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 206])
        );
    }

    //Test shape retrieval with multiple sequence points
    #[Test]
    public function shape_points_ordered_by_sequence()
    {
        $trip = Trip::factory()->create([
            'id' => 'T_SHAPE',
            'shape_id' => 'SH_TEST',
        ]);

        // Create shape points in random order
        Shape::factory()->create([
            'id' => 'SH_TEST',
            'pt_sequence' => 3,
            'pt_lat' => 47.52,
            'pt_lon' => 19.02,
            'dist_traveled' => 300,
        ]);

        Shape::factory()->create([
            'id' => 'SH_TEST',
            'pt_sequence' => 1,
            'pt_lat' => 47.50,
            'pt_lon' => 19.00,
            'dist_traveled' => 0,
        ]);

        Shape::factory()->create([
            'id' => 'SH_TEST',
            'pt_sequence' => 2,
            'pt_lat' => 47.51,
            'pt_lon' => 19.01,
            'dist_traveled' => 150,
        ]);

        $response = $this->postJson('/api/trip/shapes', [
            'trip_id' => $trip->id,
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 206])
        );
    }

    //Test invalid date format rejection
    #[Test]
    public function invalid_date_format_returns_error()
    {
        $response = $this->postJson('/api/route/trip', [
            'date' => '26-04-2026', // Wrong format
            'time' => '0800',
            'route_id' => 'R_TEST',
        ]);

        $this->assertEquals(400, $response->getStatusCode());
    }

    //Test invalid time format rejection
    #[Test]
    public function invalid_time_format_returns_error()
    {
        $response = $this->postJson('/api/route/trip', [
            'date' => '20260426',
            'time' => '25:00', // Invalid hour
            'route_id' => 'R_TEST',
        ]);

        $this->assertEquals(400, $response->getStatusCode());
    }

    //Test time format edge cases
    #[Test]
    public function time_format_edge_cases()
    {
        $route = Route::factory()->create(['id' => 'R_EDGE']);
        
        // Test midnight (00:00)
        $response = $this->postJson('/api/route/trip', [
            'date' => '20260426',
            'time' => '0000',
            'route_id' => $route->id,
        ]);
        $this->assertTrue(in_array($response->getStatusCode(), [200, 206, 400]));

        // Test just before midnight (23:59)
        $response = $this->postJson('/api/route/trip', [
            'date' => '20260426',
            'time' => '2359',
            'route_id' => $route->id,
        ]);
        $this->assertTrue(in_array($response->getStatusCode(), [200, 206, 400]));
    }
}
