<?php

namespace App\Jobs;

use App\Services\VehiclePositionPoller;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PollVehiclePosition implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $tripId)
    {
    }

    public function handle(): void
    {
        try {
            Log::info("Trip lekérdezése elkezdődött a háttérben: {$this->tripId}");
            
            $poller = new VehiclePositionPoller($this->tripId);
            $poller->poll();
            
            Log::info("Trip lekérdezése befejeződött a háttérben: {$this->tripId}");
        } catch (\Exception $e) {
            Log::error("PollVehiclePosition hiba {$this->tripId}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
