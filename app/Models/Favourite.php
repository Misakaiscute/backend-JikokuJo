<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $trip_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Trip $trip
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereTripId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Favourite extends Model
{
    protected $table = 'favourites';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'trip_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }
}
