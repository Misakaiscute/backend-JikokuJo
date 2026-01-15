<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $pt_sequence
 * @property float $pt_lat
 * @property float $pt_lon
 * @property float $dist_traveled
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Shape> $shapePoints
 * @property-read int|null $shape_points_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trip> $trips
 * @property-read int|null $trips_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape whereDistTraveled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape wherePtLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape wherePtLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shape wherePtSequence($value)
 * @mixin \Eloquent
 */
class Shape extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'shapes';
    protected $primaryKey = ['id', 'pt_sequence'];

    protected $fillable = [
        'id', 'pt_sequence', 'pt_lat', 'pt_lon', 'dist_traveled'
    ];

    protected $casts = [
        'pt_sequence'   => 'integer',
        'pt_lat'        => 'double',
        'pt_lon'        => 'double',
        'dist_traveled' => 'double',
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('id', $this->id)
                     ->where('pt_sequence', $this->pt_sequence);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'shape_id', 'id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('pt_sequence');
    }

    public function shapePoints()
    {
        return $this->hasMany(Shape::class, 'id', 'shape_id')
                    ->ordered();
    }
}
