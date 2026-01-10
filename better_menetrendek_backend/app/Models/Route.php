<?php

namespace App\Models;

use App\Models\AgencyModel;
use App\Models\TripModel;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $agency_id
 * @property string|null $short_name
 * @property string|null $long_name
 * @property int $type
 * @property string $desc
 * @property string $color
 * @property string $text_color
 * @property int $sort_order
 * @property-read \App\Models\Agency $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trip> $trips
 * @property-read int|null $trips_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereLongName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereShortName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereTextColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Route whereType($value)
 * @mixin \Eloquent
 * @mixin IdeHelperRoute
 */
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
