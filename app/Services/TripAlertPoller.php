<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TransitRealtime\FeedMessage;
use TransitRealtime\FeedEntity;
use TransitRealtime\TripUpdate;
use TransitRealtime\TripDescriptor;
use TransitRealtime\TripDescriptor\ScheduleRelationship as TripScheduleRelationship;
use TransitRealtime\TripUpdate\StopTimeUpdate\ScheduleRelationship as StopTimeScheduleRelationship;

class TripAlertPoller
{
    private const TRIP_UPDATES_URL = 'https://go.bkk.hu/api/query/v1/ws/gtfs-rt/full/TripUpdates.pb';
    private const NOTIFICATION_CACHE_TTL_SECONDS = 120;

    public function poll()
    {
        $apiKey = env('BKK_API_KEY');

        if (empty($apiKey)) {
            Log::warning('BKK_API_KEY is not configured, trip alert poller skipped.');
            return;
        }

        /** @var Response $response */
        $response = Http::withOptions([
            'verify'          => false,
            'verify_host'     => false,
            'verify_peer'     => false,
            'allow_redirects' => true,
        ])
        ->timeout(20)
        ->connectTimeout(10)
        ->get(self::TRIP_UPDATES_URL, [
            'key' => $apiKey,
        ]);


        if (!$response->successful()) {
            Log::warning('BKK TripUpdates API returned non-successful status: ' . $response->status());
            return;
        }

        $feed = new FeedMessage();
        $feed->mergeFromString($response->body());

        $this->handleTripUpdates($feed);
    }

    private function handleTripUpdates(FeedMessage $feed): void
    {
        $updates = [];

        foreach ($feed->getEntity() as $entity) {
            if (!$entity->hasTripUpdate()) {
                continue;
            }

            $tripUpdate = $entity->getTripUpdate();
            $tripDescriptor = $tripUpdate->getTrip();
            $tripId = $tripDescriptor?->getTripId();

            if (empty($tripId)) {
                continue;
            }

            $isCancelled = $tripDescriptor->getScheduleRelationship() === TripScheduleRelationship::CANCELED;
            $maxDelay = 0;

            foreach ($tripUpdate->getStopTimeUpdate() as $stopTimeUpdate) {
                foreach ([$stopTimeUpdate->getArrival(), $stopTimeUpdate->getDeparture()] as $event) {
                    if ($event !== null && $event->getDelay() > $maxDelay) {
                        $maxDelay = $event->getDelay();
                    }
                }

                if ($stopTimeUpdate->getScheduleRelationship() !== StopTimeScheduleRelationship::SCHEDULED) {
                    $isCancelled = true;
                }
            }

            if (!$isCancelled && $maxDelay <= 0) {
                continue;
            }

            $updates[$tripId] = [
                'cancelled' => $isCancelled,
                'delay' => $maxDelay,
            ];
        }

        if (empty($updates)) {
            return;
        }

        $trips = Trip::with('favouritedBy')
            ->whereIn('id', array_keys($updates))
            ->get();

        foreach ($trips as $trip) {
            if ($trip->favouritedBy->isEmpty()) {
                continue;
            }

            $update = $updates[$trip->id] ?? null;
            if (empty($update)) {
                continue;
            }

            $messageType = $update['cancelled'] ? 'cancelled' : 'delay';
            $content = $this->buildNotificationContent($trip, $update);

            foreach ($trip->favouritedBy as $user) {
                if (empty($user->fcm_token)) {
                    continue;
                }

                $cacheKey = sprintf('trip_alert_notification:%s:%s:%s', $trip->id, $user->id, $messageType);
                if (Cache::has($cacheKey)) {
                    continue;
                }

                if ($this->sendFcmNotification($user->fcm_token, $content['title'], $content['body'], [
                    'trip_id' => $trip->id,
                    'type' => $messageType,
                    'delay' => $update['delay'],
                ])) {
                    Cache::put($cacheKey, true, self::NOTIFICATION_CACHE_TTL_SECONDS);
                }
            }
        }
    }

    private function buildNotificationContent(Trip $trip, array $update): array
    {
        if ($update['cancelled']) {
            return [
                'title' => 'Járat törölve',
                'body' => "A(z) {$trip->id} járat törlésre került. Kérjük, ellenőrizd az alternatív útvonalakat.",
            ];
        }

        return [
            'title' => 'Késés a kedvenc járatodon',
            'body' => "A(z) {$trip->id} járatra késés érkezett: {$update['delay']} másodperc.",
        ];
    }

    private function sendFcmNotification(string $token, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('services.fcm.server_key');

        if (empty($serverKey)) {
            Log::warning('FCM server key is not configured. Notification for '.$title.' was not sent.');
            return false;
        }

        /** @var Response $response */
        $response = Http::withHeaders([
            'Authorization' => 'key '.$serverKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(15)
        ->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $token,
            'priority' => 'high',
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
        ]);

        if (!$response->successful()) {
            Log::warning('FCM notification failed for token '.$token.' with status '.$response->status());
            return false;
        }

        return true;
    }
}
