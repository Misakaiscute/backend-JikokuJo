<?php

namespace Tests\Feature;

use App\Models\Shape;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShapeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_shapes_by_trip_id_returns_all_points()
    {
        $trip = Trip::factory()->create(['shape_id' => 'SHAPE_1']);

        Shape::factory()->create([
            'id' => 'SHAPE_1',
            'pt_sequence' => 1,
            'pt_lat' => 47.5000,
            'pt_lon' => 19.0000,
            'dist_traveled' => 0,
        ]);
        
        Shape::factory()->create([
            'id' => 'SHAPE_1',
            'pt_sequence' => 2,
            'pt_lat' => 47.5100,
            'pt_lon' => 19.0100,
            'dist_traveled' => 1500,
        ]);

        Shape::factory()->create([
            'id' => 'SHAPE_1',
            'pt_sequence' => 3,
            'pt_lat' => 47.5200,
            'pt_lon' => 19.0200,
            'dist_traveled' => 3000,
        ]);

        $response = $this->postJson('/api/trip/shapes', [
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.shape_id', 'SHAPE_1')
            ->assertJsonPath('data.points', function ($points) {
                return count($points) === 3;
            });

        $points = $response->json('data.points');
        $this->assertCount(3, $points);

        foreach ($points as $point) {
            $this->assertArrayHasKey('distance_traveled', $point);
            $this->assertArrayHasKey('location', $point);
            $this->assertArrayHasKey('lat', $point['location']);
            $this->assertArrayHasKey('lon', $point['location']);
        }
    }

    #[Test]
    public function points_are_ordered_by_distance_traveled()
    {
        $trip = Trip::factory()->create(['shape_id' => 'SHAPE_ORDER']);

        Shape::factory()->create([
            'id' => 'SHAPE_ORDER',
            'pt_sequence' => 3,
            'dist_traveled' => 3000,
        ]);

        Shape::factory()->create([
            'id' => 'SHAPE_ORDER',
            'pt_sequence' => 1,
            'dist_traveled' => 0,
        ]);

        Shape::factory()->create([
            'id' => 'SHAPE_ORDER',
            'pt_sequence' => 2,
            'dist_traveled' => 1500,
        ]);

        $response = $this->postJson('/api/trip/shapes', [
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(200);

        $points = $response->json('data.points');
        $distances = array_column($points, 'distance_traveled');

        $this->assertEquals([0, 1500, 3000], $distances);
    }

    #[Test]
    public function location_coordinates_are_floats()
    {
        $trip = Trip::factory()->create(['shape_id' => 'SHAPE_FLOAT']);

        Shape::factory()->create([
            'id' => 'SHAPE_FLOAT',
            'pt_lat' => '47.50123456789',
            'pt_lon' => '19.04987654321',
            'dist_traveled' => 0,
        ]);

        $response = $this->postJson('/api/trip/shapes', [
            'trip_id' => $trip->id,
        ]);

        $points = $response->json('data.points');
        $location = $points[0]['location'];

        $this->assertIsFloat($location['lat']);
        $this->assertIsFloat($location['lon']);
        $this->assertEquals(47.50123456789, $location['lat']);
        $this->assertEquals(19.04987654321, $location['lon']);
    }

    #[Test]
    public function returns_404_when_trip_has_no_shape()
    {
        $trip = Trip::factory()->create(['shape_id' => null]);

        $response = $this->postJson('/api/trip/shapes', [
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('data.points', [])
            ->assertJsonPath('errors.0', 'Nincs shape_id a megadott járathoz.');
    }

    #[Test]
    public function returns_404_when_trip_not_found()
    {
        $response = $this->postJson('/api/trip/shapes', [
            'trip_id' => 'NON_EXISTENT_TRIP',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('data.points', [])
            ->assertJsonPath('errors.0', 'Nincs shape_id a megadott járathoz.');
    }

    #[Test]
    public function response_structure_matches_specification()
    {
        $trip = Trip::factory()->create(['shape_id' => 'SHAPE_STRUCT']);

        Shape::factory()->create([
            'id' => 'SHAPE_STRUCT',
            'pt_lat' => 47.5,
            'pt_lon' => 19.0,
            'dist_traveled' => 0,
        ]);

        $response = $this->postJson('/api/trip/shapes', [
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'shape_id',
                    'points' => [
                        [
                            'distance_traveled',
                            'location' => ['lat', 'lon'],
                        ]
                    ],
                ],
                'errors',
            ]);
    }
}
