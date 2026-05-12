<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TransitRealtime\FeedMessage;
use TransitRealtime\TripDescriptor\ScheduleRelationship as TripScheduleRelationship;
use TransitRealtime\TripUpdate\StopTimeUpdate\ScheduleRelationship as StopTimeScheduleRelationship;

class BkkRealtimeService
{
    private string $tripUpdatesUrl;
    private int $lateThresholdSeconds;

    public function __construct()
    {
        $this->tripUpdatesUrl = env(
            'BKK_TRIP_UPDATES_URL',
            'https://go.bkk.hu/api/query/v1/ws/gtfs-rt/full/TripUpdates.pb'
        );

        $this->lateThresholdSeconds = (int) env('BKK_LATE_THRESHOLD_SECONDS', 300);
    }

    public function getRealtimeUpdates(): Collection
    {
        $apiKey = env('BKK_API_KEY');

        $response = Http::withOptions([
                'verify' => false,
                'allow_redirects' => true,
            ])
            ->timeout(20)
            ->connectTimeout(10)
            ->get($this->tripUpdatesUrl, array_filter([
                'key' => $apiKey,
            ]));

        if (! $response->successful()) {
            Log::warning('BKK TripUpdates API request failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return collect();
        }

        $feed = new FeedMessage();
        $feed->mergeFromString($response->body());

        $updates = collect();

        foreach ($feed->getEntity() as $entity) {
            if (! $entity->hasTripUpdate()) {
                continue;
            }

            $tripUpdate = $entity->getTripUpdate();

            if (! $tripUpdate->hasTrip()) {
                continue;
            }

            $trip = $tripUpdate->getTrip();
            $routeId = $trip->getRouteId();
            $tripId = $trip->getTripId();

            if ($routeId === '' || $tripId === '') {
                continue;
            }

            $isCanceled = $trip->getScheduleRelationship() === TripScheduleRelationship::CANCELED;
            $maxDelaySeconds = $this->getMaxDelaySeconds($tripUpdate);
            $isLate = $maxDelaySeconds >= $this->lateThresholdSeconds;

            if (! $isCanceled && ! $isLate) {
                continue;
            }

            $delayMinutes = (int) ceil(max(0, $maxDelaySeconds) / 60);
            $alertType = $isCanceled ? 'canceled' : 'late';

            $updates->push([
                'route_id' => $routeId,
                'trip_id' => $tripId,
                'start_date' => $trip->getStartDate() ?: now()->format('Ymd'),
                'is_late' => $isLate,
                'is_canceled' => $isCanceled,
                'delay_seconds' => $maxDelaySeconds,
                'delay_minutes' => $delayMinutes,
                'alert_type' => $alertType,
                'message' => $isCanceled
                    ? "A kedvenc járatod kimaradt."
                    : "A kedvenc járatod körülbelül {$delayMinutes} percet késik.",
            ]);
        }

        return $updates;
    }

    private function getMaxDelaySeconds(object $tripUpdate): int
    {
        $maxDelay = 0;

        foreach ($tripUpdate->getStopTimeUpdate() as $stopTimeUpdate) {
            if ($stopTimeUpdate->getScheduleRelationship() === StopTimeScheduleRelationship::SKIPPED) {
                continue;
            }

            if ($stopTimeUpdate->hasArrival()) {
                $maxDelay = max($maxDelay, (int) $stopTimeUpdate->getArrival()->getDelay());
            }

            if ($stopTimeUpdate->hasDeparture()) {
                $maxDelay = max($maxDelay, (int) $stopTimeUpdate->getDeparture()->getDelay());
            }
        }

        return $maxDelay;
    }
}
