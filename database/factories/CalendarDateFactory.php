<?php

namespace Database\Factories;

use App\Models\CalendarDate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarDateFactory extends Factory
{
    protected $model = CalendarDate::class;

    public function definition(): array
    {
        return [
            'service_id' => (string) $this->faker->bothify('S-###'),
            'date' => (int) now()->format('Ymd'),
            'exception_type' => 1,
        ];
    }
}
