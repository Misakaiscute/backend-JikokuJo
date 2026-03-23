<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes([
            // Removed auth:sanctum middleware - authorization handled by channel callbacks
        ]);

        require base_path('routes/channels.php');
    }
}