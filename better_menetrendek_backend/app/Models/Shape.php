<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
