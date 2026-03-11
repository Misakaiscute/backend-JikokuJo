<?php

use App\Broadcasting\TripPositionChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    // return (new TripPositionChannel())->join($user, $tripId);
    return true;
});
