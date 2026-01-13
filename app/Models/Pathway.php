<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $mode
 * @property bool $is_bidirectional
 * @property string $from_stop_id
 * @property string $to_stop_id
 * @property int $traversal_time
 * @property-read \App\Models\Stop $fromStop
 * @property-read \App\Models\Stop $toStop
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereFromStopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereIsBidirectional($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereToStopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pathway whereTraversalTime($value)
 * @mixin \Eloquent
 * @mixin IdeHelperPathway
 */
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
