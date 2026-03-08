<?php

namespace App\Broadcasting;

use App\Models\User;

class TripPositionChannel
{
    public function join(?User $user, string $tripId): array|bool
    {
        //open a channel mindenki számára
        if (!$tripId) {
            return false;
        }

        return [
            'id' => $user?->id,
            'name' => $user?->email ?? 'guest',
        ];
    }
}
