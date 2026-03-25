<?php

use App\Broadcasting\TripPositionChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Broadcast::routes(['middleware' => ['web', 'auth']]);
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    logger([
    'user_id' => $user?->id,
    'tripId' => $tripId
]);
    return (new TripPositionChannel())->join($user, $tripId);
});
