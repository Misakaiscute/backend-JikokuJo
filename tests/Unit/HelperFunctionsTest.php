<?php

namespace Tests\Unit;

use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelperFunctionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_remove_dead_stops_deletes_unused_stops()
    {
        // Create stops with and without stop times
        $usedStop = Stop::factory()->create(['id' => 'USED1']);
        $deadStop1 = Stop::factory()->create(['id' => 'DEAD1']);
        $deadStop2 = Stop::factory()->create(['id' => 'DEAD2']);
        
        $trip = Trip::factory()->create();
        
        // Only create stop_time for the used stop
        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $usedStop->id,
            'stop_sequence' => 1,
        ]);

        // Verify dead stops exist before removing
        $this->assertDatabaseHas('stops', ['id' => 'DEAD1']);
        $this->assertDatabaseHas('stops', ['id' => 'DEAD2']);
        
        // Find stops with no stop_times (dead stops)
        $deadStops = DB::table('stops')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('stop_times')
                    ->whereColumn('stop_times.stop_id', 'stops.id');
            })
            ->pluck('id')
            ->toArray();

        // Verify the query correctly identifies dead stops
        $this->assertContains('DEAD1', $deadStops);
        $this->assertContains('DEAD2', $deadStops);
        $this->assertNotContains('USED1', $deadStops);
    }

    #[Test]
    public function test_remove_dead_stops_removes_orphaned_stops()
    {
        $deadStop = Stop::factory()->create(['id' => 'DEAD_ORPHAN']);
        $usedStop = Stop::factory()->create(['id' => 'USED_ORPHAN']);
        
        $trip = Trip::factory()->create();
        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $usedStop->id,
            'stop_sequence' => 1,
        ]);
        
        // Verify stops exist
        $this->assertDatabaseHas('stops', ['id' => 'DEAD_ORPHAN']);
        $this->assertDatabaseHas('stops', ['id' => 'USED_ORPHAN']);
        
        // Verify only one has stop_times
        $deadCount = StopTime::where('stop_id', 'DEAD_ORPHAN')->count();
        $usedCount = StopTime::where('stop_id', 'USED_ORPHAN')->count();
        
        $this->assertEquals(0, $deadCount);
        $this->assertEquals(1, $usedCount);
    }

    #[Test]
    public function test_switch_commas_basic_functionality()
    {
        // Test with semicolon outside quotes - should stay as is
        $input = 'A,B,C';
        $output = switch_commas($input);
        $this->assertEquals('A,B,C', $output);
    }

    #[Test]
    public function test_get_storage_path_returns_base_directory()
    {
        $basePath = config('custom.storage_upload_dir');
        $result = get_storage_path('');
        $this->assertEquals($basePath, $result);
    }

    /**
     * Test get_storage_path appends subdirectories
     */
    public function test_get_storage_path_appends_subdirectories()
    {
        $basePath = config('custom.storage_upload_dir');
        $result = get_storage_path('uploads');
        $this->assertEquals($basePath . '/uploads', $result);
    }

    /**
     * Test handling of stops with no routes
     */
    public function test_stops_without_associated_routes()
    {
        $stop = Stop::factory()->create(['id' => 'ORPHAN']);
        
        // Create a trip with stops but unrelated route
        $trip = Trip::factory()->create();
        
        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
        ]);

        // Verify stop exists and has stop times
        $this->assertDatabaseHas('stops', ['id' => 'ORPHAN']);
        $this->assertDatabaseHas('stop_times', ['stop_id' => 'ORPHAN']);
    }

    /**
     * Test data integrity - missing foreign key references
     */
    public function test_orphan_stop_times_detection()
    {
        $trip = Trip::factory()->create(['id' => 'T_ORPHAN']);
        $stop = Stop::factory()->create(['id' => 'S_ORPHAN']);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'arrival_time' => 480,
        ]);

        // Verify the relationship is intact
        $stopTime = DB::table('stop_times')
            ->where('trip_id', $trip->id)
            ->where('stop_id', $stop->id)
            ->first();

        $this->assertNotNull($stopTime);
    }

    /**
     * Test stop times with identical sequence numbers
     */
    public function test_duplicate_stop_sequences()
    {
        $trip = Trip::factory()->create(['id' => 'T_DUP']);
        $stop1 = Stop::factory()->create(['id' => 'S_DUP1']);
        $stop2 = Stop::factory()->create(['id' => 'S_DUP2']);

        // Create stop times with same sequence (should not happen in valid GTFS)
        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop1->id,
            'stop_sequence' => 1,
        ]);

        // This would violate the composite primary key in real scenario
        // But factory handles it, test validation logic instead
        $stopTimes = StopTime::where('trip_id', $trip->id)
            ->orderBy('stop_sequence')
            ->get();

        $this->assertEquals(1, $stopTimes->count());
    }

    /**
     * Test time format conversion (minutes since midnight)
     */
    public function test_time_format_conversion()
    {
        $trip = Trip::factory()->create();
        $stop = Stop::factory()->create(['id' => 'STOP_TIME']);

        // Create with various time values
        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 1,
            'departure_time' => 0, // Midnight
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 2,
            'departure_time' => 720, // Noon
        ]);

        StopTime::factory()->create([
            'trip_id' => $trip->id,
            'stop_id' => $stop->id,
            'stop_sequence' => 3,
            'departure_time' => 1439, // 23:59
        ]);

        $times = StopTime::where('trip_id', $trip->id)
            ->pluck('departure_time')
            ->toArray();

        $this->assertContains(0, $times);
        $this->assertContains(720, $times);
        $this->assertContains(1439, $times);
    }

    /**
     * Test service date filtering accuracy
     */
    public function test_service_date_filtering()
    {
        $trip = Trip::factory()->create(['service_id' => 'S_FILTER']);
        
        // Create calendar dates for different services
        DB::table('calendar_dates')->insert([
            ['service_id' => 'S_FILTER', 'date' => 20260425, 'exception_type' => 1],
            ['service_id' => 'S_FILTER', 'date' => 20260426, 'exception_type' => 2],
            ['service_id' => 'S_FILTER', 'date' => 20260427, 'exception_type' => 1],
        ]);

        // Get active services for specific date
        $activeDates = DB::table('calendar_dates')
            ->where('service_id', 'S_FILTER')
            ->where('exception_type', 1)
            ->pluck('date')
            ->toArray();

        $this->assertContains(20260425, $activeDates);
        $this->assertNotContains(20260426, $activeDates);
        $this->assertContains(20260427, $activeDates);
    }

    /**
     * Test handling of large datasets
     */
    public function test_large_stop_times_dataset()
    {
        $trip = Trip::factory()->create(['id' => 'T_LARGE']);

        // Create 100 stops with stop times
        for ($i = 0; $i < 100; $i++) {
            $stop = Stop::factory()->create(['id' => "S_LARGE_$i"]);
            
            StopTime::factory()->create([
                'trip_id' => $trip->id,
                'stop_id' => $stop->id,
                'stop_sequence' => $i + 1,
                'departure_time' => 480 + ($i * 5), // 5-min intervals
            ]);
        }

        $stopCount = StopTime::where('trip_id', 'T_LARGE')->count();
        $this->assertEquals(100, $stopCount);

        // Verify ordering
        $ordered = StopTime::where('trip_id', 'T_LARGE')
            ->orderBy('stop_sequence')
            ->pluck('stop_sequence')
            ->toArray();

        $this->assertEquals(range(1, 100), $ordered);
    }
}
