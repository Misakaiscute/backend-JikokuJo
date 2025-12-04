<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'agency';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'name', 'url', 'time_zone', 'lang', 'phone'
    ];

    public function routes()
    {
        return $this->hasMany(RouteModel::class, 'agency_id');
    }
}
