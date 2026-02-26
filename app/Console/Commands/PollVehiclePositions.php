<?php

namespace App\Console\Commands;

use App\Events\VehiclePositionUpdated;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PollVehiclePositions extends Command
{
    protected $signature = 'poll:vehicle-positions {--once : Run only one cycle and exit}';
    protected $description = 'Poll BKK GTFS-RT .txt feed and broadcast real-time vehicle positions via Reverb';

    public function handle()
    {
        $this->info('🚀 Starting BKK vehicle position poller (using .txt troubleshooting feed)...');

        if ($this->option('once')) {
            $this->pollOnce();
            return;
        }

        while (true) {
            if (Cache::get('stop-vehicle-poller', false)) {
                $this->warn('Stop signal received. Shutting down gracefully...');
                Cache::forget('stop-vehicle-poller');
                break;
            }

            $start = microtime(true);

            $this->pollOnce();

            $duration = microtime(true) - $start;
            $this->info("Cycle completed in " . number_format($duration, 3) . " seconds");

            sleep(5);
        }

        $this->info('Poller stopped.');
    }

    private function pollOnce(): void
    {
        try {
            $key = env('BKK_API_KEY');
            if (!$key) {
                $this->error('BKK_API_KEY not set in .env');
                return;
            }

            $url = "https://go.bkk.hu/api/query/v1/ws/gtfs-rt/full/VehiclePositions.txt?key={$key}";
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                $this->error("BKK .txt fetch failed: HTTP {$response->status()} - {$response->body()}");
                return;
            }

            $body = $response->body();
            $lines = explode("\n", $body);

            $broadcastCount = 0;

            $inEntity = false;
            $currentVehicleId = null;
            $currentPosition = [];
            $currentTimestamp = null;

            foreach ($lines as $line) {
                $trimmed = trim($line);

                if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                    continue;
                }

                // New entity block starts
                if (str_starts_with($trimmed, 'entity {')) {
                    // Process previous if any
                    if ($currentVehicleId && !empty($currentPosition['latitude']) && !empty($currentPosition['longitude'])) {
                        $this->processVehicle($currentVehicleId, $currentPosition, $currentTimestamp);
                        $broadcastCount++;
                    }

                    $inEntity = true;
                    $currentVehicleId = null;
                    $currentPosition = [];
                    $currentTimestamp = null;
                    continue;
                }

                // End of entity block
                if ($trimmed === '}' && $inEntity) {
                    if ($currentVehicleId && !empty($currentPosition['latitude']) && !empty($currentPosition['longitude'])) {
                        $this->processVehicle($currentVehicleId, $currentPosition, $currentTimestamp);
                        $broadcastCount++;
                    }
                    $inEntity = false;
                    continue;
                }

                // Parse key: value (ignore very deep nesting for simplicity; BKK usually has vehicle.vehicle.id)
                if (preg_match('/^(\s*)([a-z_]+)\s*:\s*(.+?)\s*$/', $line, $matches)) {
                    $indent = $matches[1];
                    $key = $matches[2];
                    $value = trim($matches[3], '"');

                    // Vehicle ID (usually under vehicle.vehicle.id)
                    if ($key === 'id' && str_contains($line, 'vehicle {') && str_contains($line, 'id:')) {
                        if (str_starts_with($value, 'BKK_')) {
                            $currentVehicleId = $value;
                        }
                    }

                    // Position fields
                    if (in_array($key, ['latitude', 'longitude', 'bearing', 'speed'])) {
                        $currentPosition[$key] = (float) $value;
                    }

                    // Timestamp from vehicle.timestamp
                    if ($key === 'timestamp' && str_contains($line, 'vehicle {')) {
                        $currentTimestamp = (int) $value;
                    }
                }
            }

            // Don't forget the very last entity
            if ($currentVehicleId && !empty($currentPosition['latitude']) && !empty($currentPosition['longitude'])) {
                $this->processVehicle($currentVehicleId, $currentPosition, $currentTimestamp);
                $broadcastCount++;
            }

            $this->info("✅ Processed .txt feed → broadcasted {$broadcastCount} valid vehicle positions");

        } catch (\Exception $e) {
            $this->error("Poll cycle failed: " . $e->getMessage());
            Log::error('BKK poller error', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function processVehicle(string $bkkId, array $position, ?int $timestamp): void
    {
        // Basic validation
        if (empty($bkkId) || $position['latitude'] == 0 || $position['longitude'] == 0) {
            return;
        }

        $vehicle = Vehicle::firstOrCreate(
            ['bkk_id' => $bkkId],
            ['updated_at' => now()]
        );

        $updateData = [
            'lat'   => $position['latitude'] ?? null,
            'lon'  => $position['longitude'] ?? null,
            'bearing'    => $position['bearing'] ?? null,
            'speed'      => $position['speed'] ?? 0,
            'last_updated_at' => $timestamp
                ? \Carbon\Carbon::createFromTimestamp($timestamp)
                : now(),
        ];

        $vehicle->update($updateData);

        // Broadcast to public channel vehicle.{id}.position
        broadcast(new VehiclePositionUpdate(
            $vehicle, ['timestamp' => $timestamp ?? now()->timestamp]
        ));
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
