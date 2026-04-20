<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
/**
 * @property int $id
 * @property int $user_id
 * @property string $trip_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Route $route
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Favourite whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Favourite extends Pivot
{
    protected $table = 'favourites';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'route_id',
        'time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
}
