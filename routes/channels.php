<?php

use App\Broadcasting\TripPositionChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Broadcast::routes(['middleware' => ['web', 'auth']]);
Broadcast::routes(['middleware' => []]);
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
//     return (new TripPositionChannel())->join($user, $tripId);
// });
Broadcast::channel('trip.{trip_id}', function ($user, $trip_id) {
    Log::info('Channel auth callback hit', [
        'user_id' => $user?->id ?? 'no user',
        'trip_id' => $trip_id,
        'channel' => 'trip.' . $trip_id,
        'auth_check' => auth()->check(),
    ]);

    // TEMP: Allow everyone (for testing)
    return ['id' => $user?->id ?? 999, 'name' => $user?->name ?? 'Test User'];

    // Or even simpler:
    // return true;  // ← uncomment this line instead if you don't need presence member data
});
