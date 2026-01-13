<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $url
 * @property string $time_zone
 * @property string $lang
 * @property string $phone
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Route> $routes
 * @property-read int|null $routes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereTimeZone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereUrl($value)
 * @mixin \Eloquent
 * @mixin IdeHelperAgency
 */
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
