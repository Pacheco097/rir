<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Registro imutável de auditoria (não possui updated_at). */
#[Fillable(['gap_id', 'user_id', 'field', 'old_value', 'new_value', 'created_at'])]
class GapHistory extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Gap, $this> */
    public function gap(): BelongsTo
    {
        return $this->belongsTo(Gap::class);
    }
}
