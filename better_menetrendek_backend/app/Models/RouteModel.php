<?php

namespace App\Models;

use App\Models\AgencyModel;
use App\Models\TripModel;
use Illuminate\Database\Eloquent\Model;

class RouteModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'routes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'agency_id', 'short_name', 'long_name', 'type',
        'desc', 'color', 'text_color', 'sort_order'
    ];

    public function agency()
    {
        return $this->belongsTo(AgencyModel::class, 'agency_id');
    }

    public function trips()
    {
        return $this->hasMany(TripModel::class, 'route_id');
    }
}
