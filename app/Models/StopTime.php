<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $trip_id
 * @property string $stop_id
 * @property int $arrival_time
 * @property int $departure_time
 * @property int $stop_sequence
 * @property string|null $stop_headsign
 * @property int $pickup_type
 * @property int $drop_off_type
 * @property float $shape_dist_traveled
 * @property-read \App\Models\Stop $stop
 * @property-read \App\Models\Trip $trip
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereArrivalTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereDepartureTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereDropOffType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime wherePickupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereShapeDistTraveled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereStopHeadsign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereStopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereStopSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StopTime whereTripId($value)
 * @mixin \Eloquent
 */
class StopTime extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'stop_times';
    protected $primaryKey = ['trip_id', 'stop_id', 'stop_sequence'];

    protected $fillable = [
        'trip_id', 'stop_id', 'arrival_time', 'departure_time',
        'stop_sequence', 'stop_headsign', 'pickup_type',
        'drop_off_type', 'shape_dist_traveled'
    ];

    protected $casts = [
        'arrival_time'        => 'integer',
        'departure_time'      => 'integer',
        'stop_sequence'       => 'integer',
        'shape_dist_traveled' => 'double',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'id');
    }

    public function stop()
    {
        return $this->belongsTo(Stop::class, 'stop_id');
    }
}
