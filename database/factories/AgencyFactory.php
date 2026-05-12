<?php

namespace Database\Factories;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        return [
            'id' => (string) $this->faker->unique()->bothify('A-###'),
            'name' => $this->faker->company(),
            'url' => $this->faker->url(),
            'time_zone' => 'Europe/Budapest',
            'lang' => 'hu',
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
