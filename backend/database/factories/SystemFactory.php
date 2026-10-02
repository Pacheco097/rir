<?php

namespace Database\Factories;

use App\Enums\SystemType;
use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<System> */
class SystemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' '.fake()->randomNumber(3),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(SystemType::cases()),
            'active' => true,
        ];
    }
}
