<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarDateModel extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'id',
        'name'
    ];
}
