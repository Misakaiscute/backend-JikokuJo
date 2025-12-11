<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'trips';
    protected $primaryKey = ['id', 'service_id'];

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
}
