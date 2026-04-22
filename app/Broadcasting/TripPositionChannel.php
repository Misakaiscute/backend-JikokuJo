<?php

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TripPositionChannel
{
    public function join(?User $user, string $tripId): array|bool
    {
        if (!$tripId) {
            return false;
        }

        // Mark channel as active when someone joins
        $cacheKey = "channel_activity:presence-trip.{$tripId}";
        Cache::put($cacheKey, time(), 90);
        
        // Add to Redis set of active channels for the poller
        Redis::sadd('active_channels', $tripId);
        
        Log::info("User joined trip channel: {$tripId}");

        return [
            'id' => $user?->id ?? uniqid('guest_', true),
            'name' => $user?->email ?? 'guest',
        ];
    }
    
    public function leave(?User $user, string $tripId): void
    {
        if (!$tripId) {
            return;
        }
        
        Log::info("User left trip channel: {$tripId}");
        // Note: Redis cleanup is handled by the poller when it checks member count
    }
}