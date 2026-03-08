<?php

namespace App\Console\Commands;

use App\Services\VehiclePositionPoller;
use Illuminate\Console\Command;

class PollVehiclePositions extends Command
{
    protected $signature = 'vehicles:poll-positions {tripId}';

    protected $description = 'Poll positions for a specific vehicle while anyone is watching the presence channel';

    public function handle(): void
    {
        $tripId = $this->argument('tripId');
        
        $this->info("Starting vehicle position polling for trip: {$tripId}");
        $this->info("Polling will continue as long as users are watching the presence channel.");

        $poller = new VehiclePositionPoller($tripId);
        $poller->poll();

        $this->info("Polling completed for trip: {$tripId}");
    }
}
