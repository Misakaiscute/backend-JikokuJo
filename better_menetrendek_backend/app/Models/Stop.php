<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'stops';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'name', 'lat', 'lon', 'code', 'location_type',
        'location_sub_type', 'parent_station', 'wheelchair_boarding'
    ];

    protected $casts = [
        'lat' => 'double',
        'lon' => 'double',
    ];
}
