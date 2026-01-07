<?php

namespace App\Models;

use App\Models\AgencyModel;
use App\Models\TripModel;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'routes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'agency_id', 'short_name', 'long_name', 'type',
        'desc', 'color', 'text_color', 'sort_order'
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'route_id');
    }
}
