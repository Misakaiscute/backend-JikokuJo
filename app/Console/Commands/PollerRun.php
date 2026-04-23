<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use App\Events\VehiclePositionUpdated;
use Illuminate\Support\Facades\Log;
use TransitRealtime\FeedMessage;

class PollerRun extends Command
{
    protected $signature = 'poller:run';
    protected $description = 'Run BKK polling loop for active trip channels';
    private int $cleanupCounter = 0;

    public function handle()
    {
        $this->info("Poller started...");
        Log::info("BKK Poller started - polling active channels every 5 seconds");
        
        while (true) {
            try {
                $trip_ids = $this->getActiveChannels();
                
                if (empty($trip_ids)) {
                    // Silently wait if no active channels
                    sleep(3);
                    continue;
                }
                
                // Check for inactive channels every 3 cycles (15 seconds) to avoid excessive API calls
                $this->cleanupCounter++;
                if ($this->cleanupCounter >= 3) {
                    $this->cleanupInactiveChannels($trip_ids);
                    $this->cleanupCounter = 0;
                }
                
                $this->info("Polling " . count($trip_ids) . " active trip(s)");
                $this->broadcastBkkData($trip_ids);
                sleep(5);
            } catch (\Exception $e) {
                Log::error("Poller error: " . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
                sleep(5);
            }
        }
    }

    private function getActiveChannels()
    {
        // Get all active trip IDs from Redis set
        $tripIds = Redis::smembers('active_channels');
        return is_array($tripIds) ? $tripIds : [];
    }

    private function cleanupInactiveChannels(array $trip_ids): void
    {
        $appId = config('reverb.apps.apps.0.app_id');
        $key = config('reverb.apps.apps.0.key');
        $secret = config('reverb.apps.apps.0.secret');
        
        try {
            $options = config('reverb.apps.apps.0.options');
            $scheme = $options['scheme'];
            $host = $options['host'];
            $port = $options['port'];
            $reverb = "{$scheme}://{$host}:{$port}";

            foreach ($trip_ids as $tripId) {
                $channelName = "presence-trip.{$tripId}";
                $timestamp = time();
                $path = "/apps/{$appId}/channels/{$channelName}";
                $method = 'GET';
                $authVersion = '1.0';
                $queryString = "auth_key={$key}&auth_timestamp={$timestamp}&auth_version={$authVersion}&info=subscription_count";
                $stringToSign = "{$method}\n{$path}\n{$queryString}";
                $signature = hash_hmac('sha256', $stringToSign, $secret);

                $response = Http::get("{$reverb}{$path}", [
                    'auth_key' => $key,
                    'auth_timestamp' => $timestamp,
                    'auth_version' => $authVersion,
                    'auth_signature' => $signature,
                    'info' => 'subscription_count',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $count = (int) ($data['subscription_count'] ?? 0);
                    
                    if ($count === 0) {
                        Log::info("Trip {$tripId} has 0 watchers - removing from active channels");
                        Redis::srem('active_channels', $tripId);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("Cleanup check failed: " . $e->getMessage());
        }
    }

    private function broadcastBkkData($trip_ids)
    {
        $apiKey = env('BKK_API_KEY');

        /**@var Response $response*/
        $response = Http::withOptions([
            'verify'          => false,
            'verify_host'     => false,
            'verify_peer'     => false,
            'allow_redirects' => true,
        ])
        ->timeout(20)
        ->connectTimeout(10)
        ->get("https://go.bkk.hu/api/query/v1/ws/gtfs-rt/full/VehiclePositions.pb", [
            'key' => $apiKey
        ]);

        if (!$response->successful()) 
        {
            Log::warning("BKK API hiba | Status: {$response->status()} | Body: " . substr($response->body(), 0, 500));
            return;
        }

        $feed = new FeedMessage();
        $feed->mergeFromString($response->body());

        foreach ($feed->getEntity() as $entity) 
        {
            $vehiclePos = $entity->getVehicle();

            if ($vehiclePos && $vehiclePos->getTrip() && $vehiclePos->getTrip()->getTripId())
            {
                if (in_array($vehiclePos->getTrip()->getTripId(), $trip_ids)) 
                {
                    $position = $vehiclePos->getPosition();
            
                    if ($position) 
                    {
                        $lat = (float) $position->getLatitude();
                        $lon = (float) $position->getLongitude();
                        $bearingRaw = $position->getBearing();
                        $bearing = is_numeric($bearingRaw) ? (float) $bearingRaw : null;

                        broadcast(new VehiclePositionUpdated(
                            tripId:    $vehiclePos->getTrip()->getTripId(),
                            lat:       $lat,
                            lon:       $lon,
                            speed:     null,
                            bearing:   $bearing,
                            timestamp: now()->toIso8601String(),
                            message:   null
                        ));

                        Log::info("Streamelés a következő tripre: {$vehiclePos->getTrip()->getTripId()}: lat={$lat}, lon={$lon}, bearing=" . ($bearing ?? 'null'));
                    } 
                    else 
                    {
                        Log::warning("Trip {$vehiclePos->getTrip()->getTripId()} megtalálva de nincs adat a pozíciójáról");

                        broadcast(new VehiclePositionUpdated(
                            tripId:    $vehiclePos->getTrip()->getTripId(),
                            lat:       0.0,
                            lon:       0.0,
                            speed:     null,
                            bearing:   null,
                            timestamp: now()->toIso8601String(),
                            message:   "Trip {$vehiclePos->getTrip()->getTripId()} megtalálva de nincs adat a pozíciójáról"
                        ));
                    }
                    break;
                }
                
            }
        }
    }
}