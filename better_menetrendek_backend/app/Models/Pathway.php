<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pathway extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'pathways';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'mode',
        'is_bidirectional',
        'from_stop_id',
        'to_stop_id',
        'traversal_time',
    ];

    protected $casts = [
        'mode'             => 'integer',
        'is_bidirectional' => 'boolean',
        'traversal_time'   => 'integer',
    ];

    public function fromStop()
    {
        return $this->belongsTo(Stop::class, 'from_stop_id', 'id');
    }

    public function toStop()
    {
        return $this->belongsTo(Stop::class, 'to_stop_id', 'id');
    }

    public function stops()
    {
        return $this->fromStop()->merge($this->toStop());
    }
}
