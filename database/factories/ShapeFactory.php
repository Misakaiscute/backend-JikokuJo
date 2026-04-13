<?php

namespace Database\Factories;

use App\Models\Shape;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShapeFactory extends Factory
{
    protected $model = Shape::class;

    public function definition(): array
    {
        return [
            'id' => (string) $this->faker->bothify('SH-###'),
            'pt_sequence' => $this->faker->numberBetween(1, 10),
            'pt_lat' => $this->faker->randomFloat(6, 47.0, 48.0),
            'pt_lon' => $this->faker->randomFloat(6, 18.0, 19.0),
            'dist_traveled' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
