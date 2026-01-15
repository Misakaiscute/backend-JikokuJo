<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property float $lat
 * @property float $lon
 * @property string $code
 * @property int $location_type
 * @property string $location_sub_type
 * @property string $parent_station
 * @property int $wheelchair_boarding
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereLocationSubType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereParentStation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stop whereWheelchairBoarding($value)
 * @mixin \Eloquent
 */
class Stop extends Model
{
    public $incrementing = false;
    public $timestamps = false;
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
