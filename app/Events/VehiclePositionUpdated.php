<?php

namespace App\Events;

use App\Models\Vehicle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehiclePositionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Vehicle $vehicle,
        public ?string $timestamp = null
    ) {
        $this->timestamp ??= now()->toIso8601String();
    }

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("vehicle.{$this->vehicle->id}.live");
    }

    public function broadcastAs(): string
    {
        return 'vehicle.position-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'vehicle_id' => $this->vehicle->vehicle_id,
            'trip_id' => $this->vehicle->trip_id,
            'lat'        => $this->vehicle->lat,
            'lon'        => $this->vehicle->lon,
            'speed'      => $this->vehicle->speed ?? null,
            'direction_id'    => $this->vehicle->direction_id ?? null,
            'updated_at' => $this->timestamp,
        ];
    }
}
