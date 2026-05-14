<?php

namespace Tests\Feature;

use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StopControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_stops_by_trip_id_returns_all_stops()
    {
        $trip = Trip::factory()->create(['id' => 'TRIP_STOPS']);

        $stop1 = Stop::factory()->create([
            'id' => 'STOP_1',
            'name' => 'Central Station',
            'lat' => 47.5000,
            'lon' => 19.0000,
        ]);

        $stop2 = Stop::factory()->create([
            'id' => 'STOP_2',
            'name' => 'Main Street',
            'lat' => 47.5100,
            'lon' => 19.0100,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop1->id,
            'stop_sequence' => 1,
            'arrival_time' => 480,
            'departure_time' => 485,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop2->id,
            'stop_sequence' => 2,
            'arrival_time' => 540,
            'departure_time' => 545,
        ]);

        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'stops' => [
                        [
                            'id',
                            'stop_sequence',
                            'name',
                            'arrival_time',
                            'location' => ['lat', 'lon'],
                        ]
                    ]
                ],
                'errors'
            ]);

        $stops = $response->json('data.stops');
        $this->assertCount(2, $stops);
    }

    #[Test]
    public function stops_are_ordered_by_stop_sequence()
    {
        $trip = Trip::factory()->create(['id' => 'TRIP_ORDER']);

        $stop1 = Stop::factory()->create(['id' => 'STOP_ORDER_1', 'name' => 'First Stop']);
        $stop2 = Stop::factory()->create(['id' => 'STOP_ORDER_2', 'name' => 'Second Stop']);
        $stop3 = Stop::factory()->create(['id' => 'STOP_ORDER_3', 'name' => 'Third Stop']);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop3->id,
            'stop_sequence' => 3,
            'arrival_time' => 600,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop1->id,
            'stop_sequence' => 1,
            'arrival_time' => 480,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop2->id,
            'stop_sequence' => 2,
            'arrival_time' => 540,
        ]);

        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(200);

        $stops = $response->json('data.stops');
        $sequences = array_column($stops, 'stop_sequence');

        // Should be ordered by stop_sequence
        $this->assertEquals([1, 2, 3], $sequences);
    }

    #[Test]
    public function stop_location_coordinates_are_floats()
    {
        $trip = Trip::factory()->create(['id' => 'TRIP_COORDS']);

        $stop = Stop::factory()->create([
            'id' => 'STOP_COORDS',
            'name' => 'Coordinate Test',
            'lat' => '47.50123456789',
            'lon' => '19.04987654321',
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'arrival_time' => 480,
        ]);

        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => $trip->id,
        ]);

        $stops = $response->json('data.stops');
        $location = $stops[0]['location'];

        $this->assertIsFloat($location['lat']);
        $this->assertIsFloat($location['lon']);
        $this->assertEquals(47.50123456789, $location['lat']);
        $this->assertEquals(19.04987654321, $location['lon']);
    }

    #[Test]
    public function missing_stop_name_defaults_to_unknown()
    {
        $trip = Trip::factory()->create(['id' => 'TRIP_NO_NAME']);

        $stop = Stop::factory()->create([
            'id' => 'STOP_NO_NAME',
            'name' => null,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'arrival_time' => 480,
        ]);

        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => $trip->id,
        ]);

        $stops = $response->json('data.stops');
        $this->assertEquals('Ismeretlen megálló', $stops[0]['name']);
    }

    #[Test]
    public function missing_stop_coordinates_default_to_zero()
    {
        $trip = Trip::factory()->create(['id' => 'TRIP_NO_COORDS']);

        $stop = Stop::factory()->create([
            'id' => 'STOP_NO_COORDS',
            'name' => 'No Coords Stop',
            'lat' => null,
            'lon' => null,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'arrival_time' => 480,
        ]);

        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => $trip->id,
        ]);

        $stops = $response->json('data.stops');
        $location = $stops[0]['location'];

        $this->assertEquals(0.0, $location['lat']);
        $this->assertEquals(0.0, $location['lon']);
    }

    #[Test]
    public function arrival_time_is_included()
    {
        $trip = Trip::factory()->create(['id' => 'TRIP_ARRIVAL']);

        $stop = Stop::factory()->create(['id' => 'STOP_ARRIVAL']);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'arrival_time' => 735, // 12:15 (12*60 + 15)
        ]);

        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => $trip->id,
        ]);

        $stops = $response->json('data.stops');
        $this->assertEquals(735, $stops[0]['arrival_time']);
    }

    #[Test]
    public function returns_404_when_trip_not_found()
    {
        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => 'NON_EXISTENT_TRIP',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('data.stops', [])
            ->assertJsonPath('errors.0', 'Járat nem található.');
    }

    #[Test]
    public function returns_empty_list_when_trip_has_no_stops()
    {
        $trip = Trip::factory()->create(['id' => 'TRIP_NO_STOPS']);

        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.stops', []);
    }

    #[Test]
    public function response_includes_stop_id_not_stop_name_for_id()
    {
        $trip = Trip::factory()->create(['id' => 'TRIP_ID_CHECK']);

        $stop = Stop::factory()->create([
            'id' => 'UNIQUE_STOP_ID_123',
            'name' => 'Stop Name',
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'arrival_time' => 480,
        ]);

        $response = $this->postJson('/api/trip/stops', [
            'trip_id' => $trip->id,
        ]);

        $stops = $response->json('data.stops');
        $this->assertEquals('UNIQUE_STOP_ID_123', $stops[0]['id']);
        $this->assertEquals('Stop Name', $stops[0]['name']);
    }
}
