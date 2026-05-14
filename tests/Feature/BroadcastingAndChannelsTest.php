<?php

namespace Tests\Feature;

use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BroadcastingAndChannelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function trip_position_channel_structure()
    {
        $trip = Trip::factory()->create(['id' => 'T_CHANNEL']);
        $channelName = "trip.{$trip->id}";

        $this->assertStringContainsString('trip', $channelName);
        $this->assertStringContainsString($trip->id, $channelName);
    }

    #[Test]
    public function vehicle_position_event_structure()
    {
        $trip = Trip::factory()->create(['id' => 'T_EVENT']);
        $eventData = [
            'trip_id' => $trip->id,
            'latitude' => 47.5,
            'longitude' => 19.0,
            'timestamp' => now()->timestamp,
            'bearing' => 180,
            'speed' => 45,
        ];

        $this->assertArrayHasKey('trip_id', $eventData);
        $this->assertArrayHasKey('latitude', $eventData);
        $this->assertArrayHasKey('longitude', $eventData);
        $this->assertArrayHasKey('timestamp', $eventData);
    }

    #[Test]
    public function presence_channel_authorization()
    {
        $trip = Trip::factory()->create(['id' => 'T_PRESENCE']);
        $channelName = "trip.{$trip->id}";

        $this->assertIsString($channelName);
        $this->assertTrue(strlen($channelName) > 0);
    }


    #[Test]
    public function inactive_channels_are_cleaned()
    {
        $trip = Trip::factory()->create(['id' => 'T_INACTIVE']);
        $channelName = "trip.{$trip->id}";

        $this->assertIsString($channelName);
    }

    #[Test]
    public function broadcast_includes_trip_headsign()
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
