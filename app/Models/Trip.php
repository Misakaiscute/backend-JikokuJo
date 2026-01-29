<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $route_id
 * @property string $service_id
 * @property string $trip_headsign
 * @property int $direction_id
 * @property string $block_id
 * @property string $shape_id
 * @property int $wheelchair_accessible
 * @property int $bikes_allowed
 * @property-read \App\Models\Route $route
 * @property-read \App\Models\Shape $shape
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shape> $shapePoints
 * @property-read int|null $shape_points_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StopTime> $stopTimes
 * @property-read int|null $stop_times_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stop> $stops
 * @property-read int|null $stops_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereBikesAllowed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereBlockId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereDirectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereShapeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereTripHeadsign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trip whereWheelchairAccessible($value)
 * @mixin \Eloquent
 */
class Trip extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'trips';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'route_id', 'service_id', 'trip_headsign', 'direction_id',
        'block_id', 'shape_id', 'wheelchair_accessible', 'bikes_allowed'
    ];

    protected $casts = [
        'direction_id'          => 'integer',
        'wheelchair_accessible' => 'integer',
        'bikes_allowed'         => 'integer',
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('id', $this->id)
                     ->where('service_id', $this->service_id);
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function shape()
    {
        return $this->belongsTo(Shape::class, 'shape_id', 'id');
    }

    public function stopTimes()
    {
        return $this->hasMany(StopTime::class, 'trip_id', 'id');
    }

    public function shapePoints()
    {
        return $this->hasMany(Shape::class, 'id', 'shape_id')
                    ->orderBy('pt_sequence');
    }

    public function stops() {
        return $this->hasManyThrough(Stop::class, StopTime::class, 'trip_id', 'stop_id', 'trip_id', 'stop_id')
                      ->orderBy('stop_times.stop_sequence');
    }
}
