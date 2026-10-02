<?php

namespace Database\Factories;

use App\Enums\GapStatus;
use App\Enums\GapType;
use App\Enums\Impact;
use App\Enums\Priority;
use App\Enums\Urgency;
use App\Models\Gap;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Gap> */
class GapFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        return [
            'code' => sprintf('GAP-%04d', ++self::$sequence),
            'type' => GapType::Gap,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'sector_id' => Sector::factory(),
            'impact' => Impact::Medio,
            'urgency' => Urgency::MedioPrazo,
            'priority' => Priority::Media,
            'sector_owner_id' => User::factory(),
            'identified_at' => now()->toDateString(),
            'status' => GapStatus::Levantado->value,
        ];
    }

    public function status(GapStatus $status): static
    {
        return $this->state(['status' => $status->value]);
    }

    public function incident(): static
    {
        return $this->state(fn () => [
            'type' => GapType::Incidente,
            'code' => sprintf('INC-%04d', ++self::$sequence),
            'status' => 'novo',
        ]);
    }
}
