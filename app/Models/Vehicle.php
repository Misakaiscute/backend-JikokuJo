<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'bkk_id',
        'trip_id',
        'lat',
        'lon',
        'speed',
        'bearing',
        'updated_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'speed' => 'float',
        'direction_id' => 'integer',
        'updated_at' => 'datetime',
    ];

    /**
     * Users currently watching this vehicle's live position
     */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_vehicle_watches')
                    ->withTimestamps('started_watching_at');
    }

    /**
     * Formatted current position as array
     */
    public function getCurrentPositionAttribute(): ?array
    {
        if (is_null($this->lat) || is_null($this->lon)) {
            return null;
        }

        return [
            'lat'        => $this->lat,
            'lon'        => $this->lon,
            'speed'      => $this->speed,
            'direction_id'    => $this->direction_id,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Scope: Vehicles being watched by a specific user
     */
    public function scopeWatchedBy($query, User $user)
    {
        return $query->whereHas('watchers', fn ($q) => $q->where('user_id', $user->id));
    }
}

