<?php

namespace App\Console\Commands;

use App\Events\VehiclePositionUpdated;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PollVehiclePositions extends Command
{
    protected $signature = 'vehicles:poll-positions {vehicleId}';

    protected $description = 'Poll positions for a specific vehicle while anyone is watching';

    public function handle()
    {
        $vehicleId = $this->argument('vehicleId');
        $vehicle   = Vehicle::findOrFail($vehicleId);

        $cacheKey = "vehicle:{$vehicleId}:active_watchers";

        while (true) {
            // Check if anyone is still watching (presence channel member count)
            $watchersCount = $this->getActiveWatchersCount($vehicleId);

            if ($watchersCount === 0) {
                $this->info("No watchers for vehicle {$vehicleId} → stopping poll.");
                break;
            }

            $this->info("{$watchersCount} watchers → polling...");

            $key = env('BKK_API_KEY');
            $response = Http::get("https://go.bkk.hu/api/query/v1/ws/gtfs-rt/full/VehiclePositions.pb?key={$key}");

            if ($response->successful()) {
                $position = $response->json();

                // Broadcast immediately
                broadcast(new VehiclePositionUpdated($vehicle, $position));

                // Optional: store last position in DB / cache
                $vehicle->update(['last_lat' => $position['lat'], 'last_lng' => $position['lng']]);
            }

            // Wait before next poll (adjust to your rate limit / needs)
            sleep(5); // 5 seconds – do NOT use very low values in production
        }

        $this->info("Polling loop for vehicle {$vehicleId} ended.");
    }

    private function getActiveWatchersCount(int $vehicleId): int
    {
        // Method 1 – via Reverb's internal stats (if you enabled stats)
        // Requires Reverb 1.3+ with stats endpoint or custom listener

        // Method 2 – most reliable: use presence channel member count via Laravel's facade
        // (works if you use Redis + scaling enabled)
        return Cache::remember("vehicle:{$vehicleId}:watchers_count", 10, function () use ($vehicleId) {
            // This requires you to maintain a counter via events
            return 0; // placeholder – see below for real impl
        });

        // Better: listen to Reverb events and maintain count in Redis/DB
    }
}
