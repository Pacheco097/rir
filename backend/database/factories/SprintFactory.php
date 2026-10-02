<?php

namespace Database\Factories;

use App\Enums\SprintStatus;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sprint> */
class SprintFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'number' => fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->words(3, true),
            'goal' => fake()->sentence(),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+14 days'),
            'status' => SprintStatus::Planejamento,
        ];
    }
}
