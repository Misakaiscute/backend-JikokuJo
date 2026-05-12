<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Favourite extends Pivot
{
    use HasFactory;

    protected $table = 'favourites';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'route_id',
        'time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
}
