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
        if (! $tripId) {
            return false;
        }

        $channelName = "presence-trip.{$tripId}";
        $activityKey = "channel_activity:{$channelName}";

        Redis::setex($activityKey, 90, (string) time());
        Redis::sadd('active_channels', $tripId);

        return [
            'id' => $user?->id ?? uniqid('guest_', true),
            'name' => $user?->email ?? 'guest',
        ];
    }
}