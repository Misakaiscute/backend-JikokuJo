<?php

namespace Database\Factories;

use App\Models\Favourite;
use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FavouriteFactory extends Factory
{
    protected $model = Favourite::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'route_id' => Route::factory(),
            'time' => '0800',
        ];
    }
}
