<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'agency';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'name', 'url', 'time_zone', 'lang', 'phone'
    ];

    public function routes()
    {
        return $this->hasMany(Route::class, 'agency_id');
    }
}
