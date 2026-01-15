<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string|null $first_name
 * @property string|null $second_name
 * @property string|null $email
 * @property string|null $email_verified_at
 * @property string|null $password
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSecondName($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'second_name',
        'email',
        'password'
    ];

    protected $hidden = [
        'password',
        'email_verified_at',
        'created_at',
        'updated_at'
    ];

    public function favourites()
    {
        return $this->belongsToMany(Route::class, 'favourites', 'user_id', 'route_id')
                    ->withTimestamps();
    }

    // public function favourite(Route $route): void
    // {
    //     $this->favourites()->syncWithoutDetaching($route->id);
    // }

    // public function unfavourite(Route $route): void
    // {
    //     $this->favourites()->detach($route->id);
    // }

    public function hasFavourited(Route $route): bool
    {
        return $this->favourites()->where('route_id', $route->id)->exists();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
