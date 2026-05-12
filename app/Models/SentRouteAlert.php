<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentRouteAlert extends Model
{
    protected $fillable = [
        'user_id',
        'route_id',
        'trip_id',
        'alert_type',
        'alert_key',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
