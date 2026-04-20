<?php

namespace Database\Factories;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'route_id' => (string) $this->faker->unique()->bothify('R-###'),
            'service_id' => (string) $this->faker->bothify('S-###'),
            'trip_headsign' => $this->faker->words(3, true),
            'direction_id' => $this->faker->numberBetween(0, 1),
            'block_id' => null,
            'shape_id' => null,
            'wheelchair_accessible' => 0,
            'bikes_allowed' => 0,
        ];
    }
}
