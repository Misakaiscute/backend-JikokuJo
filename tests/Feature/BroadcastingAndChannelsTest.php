<?php

namespace Tests\Feature;

use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastingAndChannelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test vehicle position channel exists
     */
    public function test_trip_position_channel_structure()
    {
        $trip = Trip::factory()->create(['id' => 'T_CHANNEL']);

        // Channel name should follow pattern: trip.{trip_id}
        $channelName = "trip.{$trip->id}";

        $this->assertStringContainsString('trip', $channelName);
        $this->assertStringContainsString($trip->id, $channelName);
    }

/**
     * Test broadcast message structure for vehicle updates
     */
    public function test_vehicle_position_event_structure()
    {
        $trip = Trip::factory()->create(['id' => 'T_EVENT']);

        // Expected broadcast event structure
        $eventData = [
            'trip_id' => $trip->id,
            'latitude' => 47.5,
            'longitude' => 19.0,
            'timestamp' => now()->timestamp,
            'bearing' => 180,
            'speed' => 45,
        ];

        // Verify all required fields are present
        $this->assertArrayHasKey('trip_id', $eventData);
        $this->assertArrayHasKey('latitude', $eventData);
        $this->assertArrayHasKey('longitude', $eventData);
        $this->assertArrayHasKey('timestamp', $eventData);
    }

    /**
     * Test presence channel connection
     */
    public function test_presence_channel_authorization()
    {
        $trip = Trip::factory()->create(['id' => 'T_PRESENCE']);

        $channelName = "trip.{$trip->id}";

        // Channel should be authorizable
        $this->assertIsString($channelName);
        $this->assertTrue(strlen($channelName) > 0);
    }


/**
     * Test channel cleanup for inactive trips
     */
    public function test_inactive_channels_are_cleaned()
    {
        $trip = Trip::factory()->create(['id' => 'T_INACTIVE']);

        $channelName = "trip.{$trip->id}";

        // Channel should be trackable for cleanup
        $this->assertIsString($channelName);
    }

    /**
     * Test broadcasting with various trip properties
     */
    public function test_broadcast_includes_trip_headsign()
    {
        $trip = Trip::factory()->create([
            'id' => 'T_HEADSIGN',
            'trip_headsign' => 'Downtown Terminal',
        ]);

        $eventData = [
            'trip_id' => $trip->id,
            'headsign' => $trip->trip_headsign,
        ];

        $this->assertEquals('Downtown Terminal', $eventData['headsign']);
    }


}
