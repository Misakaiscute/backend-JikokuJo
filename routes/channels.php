<?php

use App\Broadcasting\TripPositionChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
//     return (new TripPositionChannel())->join($user, $tripId);
// });
Broadcast::channel('trip.{tripId}', function (User $user, string $tripId) {
    Log::info('CHANNEL AUTH HIT', [
        'user_id' => $user->id,
        'tripId' => $tripId,
    ]);

    return [
        'id' => (string) $user->id,
        'name' => (string) ($user->name ?? 'user'),
    ];
}, ['guards' => ['web']]);
