<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $service_id
 * @property int $date
 * @property int $exception_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate whereExceptionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarDate whereServiceId($value)
 * @mixin \Eloquent
 */
class CalendarDate extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'id',
        'name'
    ];
}
