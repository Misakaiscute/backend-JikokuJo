<?php

namespace Tests\Unit;

use App\Models\Agency;
use App\Models\Route;
use App\Models\Shape;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\CalendarDate;
use App\Models\StopTime;
use App\Models\Pathway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
public function test_route_has_many_trips()
    {
        $route = Route::factory()->create(['id' => 'R3']);
        $trip1 = Trip::factory()->create(['id' => 'T1', 'route_id' => $route->id]);
        $trip2 = Trip::factory()->create(['id' => 'T2', 'route_id' => $route->id]);

        $this->assertTrue($route->trips->contains($trip1));
        $this->assertTrue($route->trips->contains($trip2));
        $this->assertEquals(2, $route->trips->count());
    }

    #[Test]
    public function test_trip_belongs_to_route()
    {
        $route = Route::factory()->create(['id' => 'R4']);
        $trip = Trip::factory()->create(['id' => 'T3', 'route_id' => $route->id]);

        $this->assertEquals($route->id, $trip->route->id);
    }

    #[Test]
    public function test_trip_has_many_stop_times()
    {
        $trip = Trip::factory()->create(['id' => 'T4']);
        $stop = Stop::factory()->create(['id' => 'ST1']);
        
        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 2,
        ]);

        $this->assertEquals(2, $trip->stopTimes->count());
    }

    public function test_trip_belongs_to_shape()
    {
        $shape = Shape::factory()->create(['id' => 'SH1']);
        $trip = Trip::factory()->create(['id' => 'T6', 'shape_id' => $shape->id]);

        $this->assertEquals($shape->id, $trip->shape->id);
    }


    #[Test]
    public function test_shape_has_many_trips()
    {
        $shape = Shape::factory()->create(['id' => 'SH2']);
        $trip1 = Trip::factory()->create(['id' => 'T9', 'shape_id' => $shape->id]);
        $trip2 = Trip::factory()->create(['id' => 'T10', 'shape_id' => $shape->id]);

        $this->assertEquals(2, $shape->trips->count());
    }


    #[Test]
    public function test_calendar_date_marks_active_services()
    {
        $date = 20260426;
        
        CalendarDate::factory()->create([
            'service_id' => 'S_WEEKDAY',
            'date' => $date,
            'exception_type' => 1, // Service operates
        ]);

        CalendarDate::factory()->create([
            'service_id' => 'S_HOLIDAY',
            'date' => $date,
            'exception_type' => 2, // Service does not operate
        ]);

        $activeServices = CalendarDate::where('date', $date)
            ->where('exception_type', 1)
            ->pluck('service_id')
            ->toArray();

        $this->assertContains('S_WEEKDAY', $activeServices);
        $this->assertNotContains('S_HOLIDAY', $activeServices);
    }

    /**
     * Test StopTime ordering by sequence
     */
    public function test_stop_times_ordered_by_sequence()
    {
        $trip = Trip::factory()->create(['id' => 'T11']);
        $stop = Stop::factory()->create(['id' => 'ST5']);

        // Create stop times in reversed order
        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 3,
            'arrival_time' => 600,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'arrival_time' => 300,
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 2,
            'arrival_time' => 450,
        ]);

        // Verify they are ordered by sequence
        $ordered = $trip->stopTimes()->orderBy('stop_sequence')->get();
        $this->assertEquals([1, 2, 3], $ordered->pluck('stop_sequence')->toArray());
    }

    /**
     * Test Trip with multiple service_ids (composite key behavior)
     */
    public function test_trip_composite_key_same_id_different_services()
    {
        Trip::factory()->create([
            'id' => 'T12',
            'service_id' => 'S_WEEKDAY',
            'route_id' => 'R5',
        ]);

        Trip::factory()->create([
            'id' => 'T12', // Same ID but different service
            'service_id' => 'S_WEEKEND',
            'route_id' => 'R5',
        ]);

        // Both should exist in database
        $trips = Trip::where('id', 'T12')->get();
        $this->assertEquals(2, $trips->count());
    }

    /**
     * Test Shape with multiple pt_sequence (composite key behavior)
     */
    public function test_shape_composite_key_multiple_sequences()
    {
        Shape::factory()->create([
            'id' => 'SH3',
            'pt_sequence' => 1,
            'pt_lat' => 47.5,
            'pt_lon' => 19.0,
        ]);

        Shape::factory()->create([
            'id' => 'SH3', // Same shape ID but different sequence
            'pt_sequence' => 2,
            'pt_lat' => 47.51,
            'pt_lon' => 19.01,
        ]);

        $points = Shape::where('id', 'SH3')->get();
        $this->assertEquals(2, $points->count());
        $this->assertEquals([1, 2], $points->pluck('pt_sequence')->toArray());
    }
}
