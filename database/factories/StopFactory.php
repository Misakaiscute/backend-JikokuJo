<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StopFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Deák tér',
                'Astoria',
                'Kálvin tér',
                'Nyugati pályaudvar'
            ]),
        ];
    }
}