<?php

namespace App\Console\Commands;

use App\Jobs\CheckFavouriteRouteAlertsJob;
use Illuminate\Console\Command;

class CheckFavouriteRouteAlerts extends Command
{
    protected $signature = 'jikokujo:check-favourite-alerts';

    protected $description = 'Check BKK realtime updates and notify users about delayed or canceled favourite routes.';

    public function handle(): int
    {
        CheckFavouriteRouteAlertsJob::dispatch();

        $this->info('Favourite route alert check job dispatched.');

        return self::SUCCESS;
    }
}
