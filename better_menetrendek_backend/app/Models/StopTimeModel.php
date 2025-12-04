<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StopTimeModel extends Model
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
        return $this->belongsTo(TripModel::class, 'trip_id', 'id');
    }

    public function stop()
    {
        return $this->belongsTo(StopModel::class, 'stop_id');
    }
}
