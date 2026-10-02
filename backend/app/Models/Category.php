<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'color', 'active'])]
class Category extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** @return HasMany<Gap, $this> */
    public function gaps(): HasMany
    {
        return $this->hasMany(Gap::class);
    }
}
