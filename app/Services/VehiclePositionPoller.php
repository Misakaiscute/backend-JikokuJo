<?php

namespace App\Services;

use App\Events\VehiclePositionUpdated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use TransitRealtime\FeedMessage;

class VehiclePositionPoller
{
    private string $tripId;
    private string $channelName;
    private int $pollInterval = 5; //BKK api endpoint pollolása mpben
    private int $statisticsCheckInterval = 6; //ennyi ciklus után van user watch count check
    private int $iterationCount = 0;

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

                if ($watchersCount === 0) {
                    Log::info("Nincs néző erre a tripre {$this->tripId} → stopping polling.");
                    break;
                }

                Log::info("{$watchersCount} néző erre a tripre {$this->tripId} ezen a cikluson {$this->iterationCount}");
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
        /** @var Response $response */
        $response = Http::timeout(10)->get(
            "https://go.bkk.hu/api/query/v1/ws/gtfs-rt/full/VehiclePositions.pb?key=" . $apiKey
        );

        if (!$response->successful()) {
            Log::warning("BKK API státusza {$response->status()} erre a tripre {$this->tripId}");
            return;
        }

        $feed = new FeedMessage();
        $feed->mergeFromString($response->body());

        $entityCount = count($feed->getEntity());
        Log::info("BKK API visszaküldött {$entityCount} entitást erre a tripre {$this->tripId}");

        //teszteléshez aktv tripek keresése
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
        foreach ($feed->getEntity() as $entity) {
            $vehiclePos = $entity->getVehicle();

            if ($vehiclePos && $vehiclePos->getTrip() && $vehiclePos->getTrip()->getTripId() === $this->tripId) {
                $found = true;
                $position = $vehiclePos->getPosition();
                
                if ($position) {
                    $lat = $position->getLatitude();
                    $lon = $position->getLongitude();
                    $bearing = $position->getBearing() ?: null;

                    broadcast(new VehiclePositionUpdated($this->tripId, $lat, $lon, $bearing));
                    Log::info("Streamelés a következő tripre: {$this->tripId}: lat={$lat}, lon={$lon}");
                } else {
                    Log::warning("Trip {$this->tripId} megtalálva de nincs adat a pozíciójáról");
                }

                break;
            }
        }

        if (!$found) {
            Log::debug("Trip {$this->tripId} nem aktív jelenleg");
        }
    }

    private function getPresenceChannelMemberCount(): int
    {
        try {
            /** @var Response $response */
            $response = Http::timeout(5)->get("http://localhost:8080/stats/channels", [
                'channels' => [$this->channelName],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $channels = $data['channels'] ?? [];
                
                foreach ($channels as $channel) {
                    if ($channel['name'] === $this->channelName && isset($channel['user_count'])) {
                        return (int) $channel['user_count'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("Nem sikerült Reverb statisztikáját lekérni erre a tripre: {$this->tripId}: " . $e->getMessage());
        }

        Log::warning("Nem érhető el Reverb endpointja erre a tripre {$this->tripId}, 0 néző feltételezve");
        return 0;
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
