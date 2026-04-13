<?php

namespace App\Console\Commands;

use App\Services\TripAlertPoller;
use Illuminate\Console\Command;

class PollTripAlerts extends Command
{
    protected $signature = 'poll:trip-alerts';
    protected $description = 'Poll BKK GTFS-Realtime trip updates and notify users about delays or cancellations.';

    public function handle(): int
    {
        $poller = new TripAlertPoller();
        $poller->poll();

        return 0;
    }
}
