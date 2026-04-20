<?php

namespace Database\Factories;

use App\Models\Stop;
use Illuminate\Database\Eloquent\Factories\Factory;

class StopFactory extends Factory
{
    protected $model = Stop::class;

    public function definition(): array
    {
        return [
            'id' => (string) $this->faker->unique()->bothify('S-###'),
            'name' => $this->faker->randomElement([
                'Deák tér',
                'Astoria',
                'Kálvin tér',
                'Nyugati pályaudvar'
            ]),
            'lat' => $this->faker->randomFloat(6, 47.0, 48.0),
            'lon' => $this->faker->randomFloat(6, 18.0, 19.0),
            'code' => $this->faker->unique()->bothify('##'),
            'location_type' => 0,
            'location_sub_type' => null,
            'parent_station' => null,
            'wheelchair_boarding' => 0,
        ];
    }
}
