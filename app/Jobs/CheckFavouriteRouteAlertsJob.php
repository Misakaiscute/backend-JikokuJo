<?php

namespace App\Jobs;

use App\Models\Favourite;
use App\Models\SentRouteAlert;
use App\Services\BkkRealtimeService;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckFavouriteRouteAlertsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 90;

    public function handle(BkkRealtimeService $bkk, FirebaseNotificationService $firebase): void
    {
        $updatesByRoute = $bkk->getRealtimeUpdates()->groupBy('route_id');

        if ($updatesByRoute->isEmpty()) {
            return;
        }

        Favourite::with(['user.deviceTokens', 'route'])
            ->whereIn('route_id', $updatesByRoute->keys()->all())
            ->chunkById(200, function ($favourites) use ($updatesByRoute, $firebase): void {
                foreach ($favourites as $favourite) {
                    if (! $favourite instanceof Favourite) {
                        continue;
                    }

                    if (! $favourite->user || $favourite->user->deviceTokens->isEmpty()) {
                        continue;
                    }

                    foreach ($updatesByRoute->get($favourite->route_id, collect()) as $update) {
                        $this->sendUpdateToFavouriteOwner($favourite, (array) $update, $firebase);
                    }
                }
            });
    }

    private function sendUpdateToFavouriteOwner(
        Favourite $favourite,
        array $update,
        FirebaseNotificationService $firebase
    ): void {
        $alertKey = $this->buildAlertKey($favourite, $update);

        if (SentRouteAlert::where('alert_key', $alertKey)->exists()) {
            return;
        }

        $routeName = $favourite->route?->short_name ?: $favourite->route_id;
        $isCanceled = (bool) ($update['is_canceled'] ?? false);
        $delayMinutes = (int) ($update['delay_minutes'] ?? 0);

        $title = $isCanceled
            ? 'Kimaradt a kedvenc járatod'
            : 'Késik a kedvenc járatod';

        $body = $isCanceled
            ? "A(z) {$routeName} járat kimaradt. Érdemes másik útvonalat választani."
            : "A(z) {$routeName} járat várhatóan {$delayMinutes} percet késik.";

        $sentToAtLeastOneDevice = false;

        foreach ($favourite->user->deviceTokens as $deviceToken) {
            $sent = $firebase->sendToToken(
                $deviceToken->token,
                $title,
                $body,
                [
                    'type' => 'route_alert',
                    'alert_type' => $update['alert_type'] ?? ($isCanceled ? 'canceled' : 'late'),
                    'route_id' => $update['route_id'] ?? $favourite->route_id,
                    'trip_id' => $update['trip_id'] ?? '',
                    'delay_minutes' => $delayMinutes,
                ]
            );

            $sentToAtLeastOneDevice = $sentToAtLeastOneDevice || $sent;
        }

        if (! $sentToAtLeastOneDevice) {
            Log::warning('Route alert was not sent to any device', [
                'user_id' => $favourite->user_id,
                'route_id' => $favourite->route_id,
                'trip_id' => $update['trip_id'] ?? null,
            ]);

            return;
        }

        SentRouteAlert::create([
            'user_id' => $favourite->user_id,
            'route_id' => $update['route_id'] ?? $favourite->route_id,
            'trip_id' => $update['trip_id'] ?? null,
            'alert_type' => $update['alert_type'] ?? ($isCanceled ? 'canceled' : 'late'),
            'alert_key' => $alertKey,
            'sent_at' => now(),
        ]);
    }

    private function buildAlertKey(Favourite $favourite, array $update): string
    {
        return implode(':', [
            $favourite->user_id,
            $update['route_id'],
            $update['trip_id'] ?? 'unknown-trip',
            $update['start_date'] ?? now()->format('Ymd'),
            $update['alert_type'],
            $update['alert_type'] === 'late' ? ($update['delay_minutes'] ?? 0) : 'canceled',
        ]);
    }
}
