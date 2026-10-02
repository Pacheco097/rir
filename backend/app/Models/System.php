<?php

namespace App\Models;

use App\Enums\SystemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description', 'type', 'owner_id', 'active'])]
class System extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['active' => 'boolean', 'type' => SystemType::class];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsToMany<Gap, $this> */
    public function gaps(): BelongsToMany
    {
        return $this->belongsToMany(Gap::class);
    }
}
