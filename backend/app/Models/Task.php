<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gap_id', 'title', 'description', 'assignee_id', 'status', 'due_date'])]
class Task extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => TaskStatus::class, 'due_date' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<Gap, $this> */
    public function gap(): BelongsTo
    {
        return $this->belongsTo(Gap::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
