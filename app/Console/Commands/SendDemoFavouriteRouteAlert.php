<?php

namespace App\Console\Commands;

use App\Models\Favourite;
use App\Models\SentRouteAlert;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;

class SendDemoFavouriteRouteAlert extends Command
{
    protected $signature = 'jikokujo:demo-route-alert 
        {route_id : Route ID, például 0050}
        {--type=late : late vagy canceled}
        {--delay=9 : Késés percben}
        {--user= : Csak egy user_id-nak küldje}';

    protected $description = 'Send a fake favourite route delay/cancellation notification for presentation/demo.';

    public function handle(FirebaseNotificationService $firebase): int
    {
        $routeId = (string) $this->argument('route_id');
        $type = (string) $this->option('type');
        $delayMinutes = (int) $this->option('delay');

        if (! in_array($type, ['late', 'canceled'], true)) {
            $this->error('--type csak late vagy canceled lehet.');
            return self::FAILURE;
        }

        $query = Favourite::with(['user.deviceTokens', 'route'])
            ->where('route_id', $routeId);

        if ($this->option('user')) {
            $query->where('user_id', (int) $this->option('user'));
        }

        $favourites = $query->get();

        if ($favourites->isEmpty()) {
            $this->warn("Nincs kedvenc erre a route_id-ra: {$routeId}");
            return self::SUCCESS;
        }

        $sentCount = 0;

        foreach ($favourites as $favourite) {
            if (! $favourite->user || $favourite->user->deviceTokens->isEmpty()) {
                $this->warn("User {$favourite->user_id}: nincs device token.");
                continue;
            }

            $routeName = $favourite->route?->short_name ?: $favourite->route_id;
            $isCanceled = $type === 'canceled';

            $title = $isCanceled
                ? 'Kimaradt a kedvenc járatod'
                : 'Késik a kedvenc járatod';

            $body = $isCanceled
                ? "A(z) {$routeName} járat kimaradt. Érdemes másik útvonalat választani."
                : "A(z) {$routeName} járat várhatóan {$delayMinutes} percet késik.";

            $tripId = 'demo-trip-' . now()->format('Ymd-His');

            $sentToAtLeastOneDevice = false;

            foreach ($favourite->user->deviceTokens as $deviceToken) {
                $sent = $firebase->sendToToken(
                    $deviceToken->token,
                    $title,
                    $body,
                    [
                        'type' => 'route_alert',
                        'alert_type' => $type,
                        'route_id' => $routeId,
                        'trip_id' => $tripId,
                        'delay_minutes' => $delayMinutes,
                        'demo' => 'true',
                    ]
                );

                if ($sent) {
                    $sentCount++;
                    $sentToAtLeastOneDevice = true;
                    $this->info("Elküldve tokenre: " . substr($deviceToken->token, 0, 20) . "...");
                } else {
                    $this->error("Firebase küldés sikertelen tokenre: " . substr($deviceToken->token, 0, 20) . "...");
                }
            }

            if (! $sentToAtLeastOneDevice) {
                $this->error("Nem ment ki értesítés user_id={$favourite->user_id}, route_id={$routeId}");
                continue;
            }

            SentRouteAlert::create([
                'user_id' => $favourite->user_id,
                'route_id' => $routeId,
                'trip_id' => $tripId,
                'alert_type' => $type,
                'alert_key' => implode(':', [
                    'demo',
                    $favourite->user_id,
                    $routeId,
                    $tripId,
                    $type,
                    $delayMinutes,
                ]),
                'sent_at' => now(),
            ]);
        }

        $this->info("Demo notification sent to {$sentCount} device token(s).");

        return self::SUCCESS;
    }
}