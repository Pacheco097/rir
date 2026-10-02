<?php

namespace Database\Factories;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Meeting> */
class MeetingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Levantamento de gaps - '.fake()->word(),
            'sector_id' => Sector::factory(),
            'date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'objective' => fake()->sentence(),
            'status' => MeetingStatus::Agendada,
        ];
    }
}
