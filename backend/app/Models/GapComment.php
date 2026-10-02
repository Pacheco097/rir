<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gap_id', 'user_id', 'body'])]
class GapComment extends Model
{
    /** @return BelongsTo<Gap, $this> */
    public function gap(): BelongsTo
    {
        return $this->belongsTo(Gap::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
