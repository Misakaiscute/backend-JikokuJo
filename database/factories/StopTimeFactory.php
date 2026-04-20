<?php

namespace Database\Factories;

use App\Models\StopTime;
use Illuminate\Database\Eloquent\Factories\Factory;

class StopTimeFactory extends Factory
{
    protected $model = StopTime::class;

    public function definition(): array
    {
        return [
            'trip_id' => (string) $this->faker->bothify('T-###'),
            'stop_id' => (string) $this->faker->bothify('S-###'),
            'arrival_time' => $this->faker->numberBetween(0, 2359),
            'departure_time' => $this->faker->numberBetween(0, 2359),
            'stop_sequence' => $this->faker->numberBetween(1, 10),
            'stop_headsign' => $this->faker->sentence(3),
            'pickup_type' => 0,
            'drop_off_type' => 0,
            'shape_dist_traveled' => $this->faker->randomFloat(3, 0, 100),
        ];
    }
}
