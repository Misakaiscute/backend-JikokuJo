<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehiclePositionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $tripId,
        public float $lat,
        public float $lon,
        public ?float $speed = null,
        public ?float $bearing = null,
        public ?string $timestamp = null,
        // public ?string $vehicleId = null,
    ) {
        $this->timestamp ??= now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("trip.{$this->tripId}");
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'trip_id'    => $this->tripId,
            // 'vehicle_id' => $this->vehicleId,
            'lat'        => $this->lat,
            'lon'        => $this->lon,
            'speed'      => $this->speed,
            'bearing'    => $this->bearing,
            'updated_at' => $this->timestamp,
        ];
    }

    public function broadcastAs(): string
    {
        return 'vehicle.position-updated';
    }

    /**
     * Determine if the event should be broadcast synchronously.
     * Return true to broadcast immediately instead of queuing.
     */
    public function shouldBroadcastNow(): bool
    {
        return true;
    }
}