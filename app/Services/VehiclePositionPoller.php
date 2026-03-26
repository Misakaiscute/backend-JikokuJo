<?php

namespace App\Services;

use App\Events\VehiclePositionUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use TransitRealtime\FeedMessage;

class VehiclePositionPoller
{
    private string $tripId;
    private string $channelName;
    private int $pollInterval = 5; //BKK api endpoint pollolása mpben
    private int $statisticsCheckInterval = 8; //ennyi ciklus után van user watch count check
    private int $iterationCount = 0;
    private int $consecutiveStatsFailures = 0;
    private int $maxConsecutiveStatsFailures = 4; //stop csak ha 3x egymás után nem érhető el

    public function __construct(string $tripId)
    {
        $this->tripId = $tripId;
        $this->channelName = "trip.{$tripId}";
    }

    public function poll(): void
    {
        while (true) {
            $this->iterationCount++;

            if ($this->iterationCount % $this->statisticsCheckInterval === 0) {
                $watchersCount = $this->getPresenceChannelMemberCount();
            
                if ($watchersCount > 0) {
                    $this->consecutiveStatsFailures = 0;
                    Log::info("{$watchersCount} néző van a tripen {$this->tripId} → polling folytatódik");
                } else {
                    $this->consecutiveStatsFailures++;
            
                    Log::debug("Nincs néző ({$this->consecutiveStatsFailures}/{$this->maxConsecutiveStatsFailures})");
            
                    if ($this->consecutiveStatsFailures >= $this->maxConsecutiveStatsFailures) {
                        Log::info("Nincs aktív néző a {$this->tripId} tripen. Polling leállítva.");
                        break;
                    }
                }
            }

            try {
                $this->fetchAndBroadcastPosition();
            } catch (\Exception $e) {
                Log::error("Hiba a trip pozíciójának lekérésekor {$this->tripId}: " . $e->getMessage());
            }

            sleep($this->pollInterval);
        }
    }

    private function fetchAndBroadcastPosition()
    {
        $apiKey = env('BKK_API_KEY');

        if (empty($apiKey)) 
        {
            Log::error("BKK_API_KEY nincs beállítva!");
            return;
        }

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
            Log::warning("BKK API hiba | Status: {$response->status()} | Trip: {$this->tripId} | Body: " . substr($response->body(), 0, 500));
            return;
        }

        $feed = new FeedMessage();
        $feed->mergeFromString($response->body());

        $entityCount = count($feed->getEntity());
        Log::info("BKK API visszaküldött {$entityCount} entitást erre a tripre {$this->tripId}");

        //teszteléshez aktív tripek keresése
        // if ($entityCount > 0) {
        //     $sampleTrips = [];
        //     $entities = $feed->getEntity();
        //     $count = min(5, count($entities));
        //     for ($i = 0; $i < $count; $i++) {
        //         $entity = $entities[$i];
        //         $vehiclePos = $entity->getVehicle();
        //         if ($vehiclePos && $vehiclePos->getTrip()) {
        //             $sampleTrips[] = $vehiclePos->getTrip()->getTripId();
        //         }
        //     }
        //     Log::info("Aktív trip id-k: " . implode(', ', array_unique($sampleTrips)));
        // }

        $found = false;
        foreach ($feed->getEntity() as $entity) 
        {
            $vehiclePos = $entity->getVehicle();

            if ($vehiclePos && $vehiclePos->getTrip() && $vehiclePos->getTrip()->getTripId() === $this->tripId) 
            {
                $found = true;
                $position = $vehiclePos->getPosition();
            
                if ($position) 
                {
                    $lat = (float) $position->getLatitude();
                    $lon = (float) $position->getLongitude();
                    $bearingRaw = $position->getBearing();
                    $bearing = is_numeric($bearingRaw) ? (float) $bearingRaw : null;

                    broadcast(new VehiclePositionUpdated(
                        tripId:    $this->tripId,
                        lat:       $lat,
                        lon:       $lon,
                        speed:     null,
                        bearing:   $bearing,
                        timestamp: now()->toIso8601String(),
                        message:   null
                    ));

                    Log::info("Streamelés a következő tripre: {$this->tripId}: lat={$lat}, lon={$lon}, bearing=" . ($bearing ?? 'null'));
                } 
                else 
                {
                    Log::warning("Trip {$this->tripId} megtalálva de nincs adat a pozíciójáról");

                    broadcast(new VehiclePositionUpdated(
                        tripId:    $this->tripId,
                        lat:       0.0,
                        lon:       0.0,
                        speed:     null,
                        bearing:   null,                    // ← itt biztosan null
                        timestamp: now()->toIso8601String(),
                        message:   "Trip {$this->tripId} megtalálva de nincs adat a pozíciójáról"
                    ));
                }
                break;
            }
        }

        // Ha a trip egyáltalán nem található a feed-ben
        if (!$found) 
        {
            Log::debug("Trip {$this->tripId} nem aktív jelenleg");

            broadcast(new VehiclePositionUpdated(
                tripId:    $this->tripId,
                lat:       0.0,
                lon:       0.0,
                speed:     null,
                bearing:   null,                    // ← itt is biztosan null
                timestamp: now()->toIso8601String(),
                message:   "Trip {$this->tripId} nem aktív jelenleg"
            ));
        }
    }
    
    private function getPresenceChannelMemberCount(): int
    {
        $appId = env('REVERB_APP_ID', '806509');

        try 
        {
            $response = Http::timeout(8)
                ->get("http://127.0.0.1:8080/apps/{$appId}/channels/{$this->channelName}");

            if ($response->successful()) 
            {
                $data = $response->json();
                $userCount = (int) ($data['user_count'] ?? $data['subscription_count'] ?? 0);
                
                Log::debug("Reverb stats | {$this->channelName} | user_count: {$userCount}");
                
                if ($userCount > 0) {
                    $this->consecutiveStatsFailures = 0;
                    return $userCount;
                }
            } else 
            {
                Log::debug("Reverb stats status: " . $response->status() . " (várható 401, ha nincs auth)");
            }
        } catch (\Exception $e) 
        {
            Log::debug("Reverb stats hívás sikertelen: " . $e->getMessage());
        }

        $cacheKey = "channel_activity:{$this->channelName}";
        $lastActivity = Cache::get($cacheKey, 0);

        if (time() - $lastActivity < 90) 
        {
            Log::debug("Cache alapján AKTÍV néző van a {$this->channelName} csatornán");
            $this->consecutiveStatsFailures = 0;
            return 1;
        }

        Log::debug("Nincs friss cache aktivitás → 0 néző feltételezve");
        return 0;
    }

    private function getReverbAppId(): string
    {
        return env('REVERB_APP_ID', '271825');
    }

    public function setPollInterval(int $seconds): self
    {
        $this->pollInterval = $seconds;
        return $this;
    }

    public function setStatisticsCheckInterval(int $iterations): self
    {
        $this->statisticsCheckInterval = $iterations;
        return $this;
    }
}
