<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use App\Services\TripAlertPoller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TransitRealtime\FeedEntity;
use TransitRealtime\FeedMessage;
use TransitRealtime\TripDescriptor;
use TransitRealtime\TripUpdate;
use TransitRealtime\TripUpdate\StopTimeEvent;
use TransitRealtime\TripUpdate\StopTimeUpdate;
use TransitRealtime\TripDescriptor\ScheduleRelationship as TripScheduleRelationship;
use TransitRealtime\TripUpdate\StopTimeUpdate\ScheduleRelationship as StopTimeScheduleRelationship;

class TripAlertPollerTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_update_polling_sends_fcm_notifications_for_favourited_trip(): void
    {
        $user = User::factory()->create(['fcm_token' => 'device-token-123']);
        $trip = Trip::factory()->create(['id' => 'T1']);
        $user->favourites()->attach($trip->id);

        Http::fake([
            'https://go.bkk.hu/api/query/v1/ws/gtfs-rt/full/TripUpdates.pb*' => Http::response($this->makeTripUpdateFeed('T1', 180), 200),
            'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1], 200),
        ]);

        putenv('BKK_API_KEY=test-key');
        $_ENV['BKK_API_KEY'] = 'test-key';
        $_SERVER['BKK_API_KEY'] = 'test-key';
        config(['services.fcm.server_key' => 'test-key']);
        Cache::flush();

        (new TripAlertPoller())->poll();

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://fcm.googleapis.com/fcm/send'
                && $data['to'] === 'device-token-123'
                && $data['notification']['title'] === 'Késés a kedvenc járatodon';
        });
    }

    private function makeTripUpdateFeed(string $tripId, int $delay): string
    {
        $tripDescriptor = new TripDescriptor();
        $tripDescriptor->setTripId($tripId);
        $tripDescriptor->setScheduleRelationship(TripScheduleRelationship::SCHEDULED);

        $stopTimeEvent = new StopTimeEvent();
        $stopTimeEvent->setDelay($delay);

        $stopTimeUpdate = new StopTimeUpdate();
        $stopTimeUpdate->setArrival($stopTimeEvent);
        $stopTimeUpdate->setScheduleRelationship(StopTimeScheduleRelationship::SCHEDULED);

        $tripUpdate = new TripUpdate();
        $tripUpdate->setTrip($tripDescriptor);
        $tripUpdate->setStopTimeUpdate([$stopTimeUpdate]);

        $entity = new FeedEntity();
        $entity->setId('alert-1');
        $entity->setTripUpdate($tripUpdate);

        $feed = new FeedMessage();
        $feed->setEntity([$entity]);

        return $feed->serializeToString();
    }
}
