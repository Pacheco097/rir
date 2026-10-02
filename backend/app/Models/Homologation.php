<?php

namespace App\Models;

use App\Enums\HomologationResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'gap_id', 'submitted_by', 'submitted_at', 'tested_by', 'tested_at', 'result', 'test_result', 'notes',
])]
class Homologation extends Model
{
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'tested_at' => 'datetime',
            'result' => HomologationResult::class,
        ];
    }

    public function isPending(): bool
    {
        return $this->result === null;
    }

    /** @return BelongsTo<Gap, $this> */
    public function gap(): BelongsTo
    {
        return $this->belongsTo(Gap::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }
}
