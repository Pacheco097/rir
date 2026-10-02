<?php

namespace App\Models;

use App\Enums\SprintStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['number', 'name', 'goal', 'start_date', 'end_date', 'status'])]
class Sprint extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'status' => SprintStatus::class,
        ];
    }

    /** @return HasMany<Gap, $this> */
    public function gaps(): HasMany
    {
        return $this->hasMany(Gap::class);
    }

    public function label(): string
    {
        return "Sprint {$this->number} - {$this->name}";
    }
}
