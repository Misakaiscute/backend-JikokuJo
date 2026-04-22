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
    protected $description = 'Run BKK polling loop';

    public function handle()
    {
        $this->info("Poller started...");
        $trip_ids = "";
        while (true) {
            $channels = $this->getActiveChannels();

            foreach ($channels as $channelId) {
                $trip_ids += $channelId;
            }
            $this->broadcastBkkData($trip_ids);
            sleep(5);
        }
    }

    private function getActiveChannels()
    {
        // Example: stored in Redis set
        return Redis::smembers('active_channels') ?? [];
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
                            tripId:    $this->$vehiclePos->getTrip()->getTripId(),
                            lat:       0.0,
                            lon:       0.0,
                            speed:     null,
                            bearing:   null,                    // ← itt biztosan null
                            timestamp: now()->toIso8601String(),
                            message:   "Trip {$this->$vehiclePos->getTrip()->getTripId()} megtalálva de nincs adat a pozíciójáról"
                        ));
                    }
                    break;
                }
                
            }
        }
    }
}