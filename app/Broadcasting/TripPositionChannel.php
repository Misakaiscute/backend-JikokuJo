<?php

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TripPositionChannel
{
    public function join(?User $user, string $tripId): array|bool
    {
        // Allow anyone to join the presence channel
        if (!$tripId) {
            return false;
        }

        // Mark channel as active when someone joins
        // $cacheKey = "channel_activity:trip.{$tripId}";
        // Cache::put($cacheKey, time(), 60); // Cache for 60 seconds

        return [
            'id' => $user?->id ?? 'guest-' . session()->getId(),
            'name' => $user?->email ?? 'guest',
        ];
    }
}