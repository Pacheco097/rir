<?php

namespace App\Models;

use App\Enums\MeetingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'title', 'sector_id', 'leader_id', 'it_owner_id', 'date', 'start_time', 'end_time',
    'objective', 'status', 'notes', 'agenda', 'minutes', 'summary', 'decisions',
    'next_steps', 'previous_meeting_id', 'created_by',
])]
class Meeting extends Model
{
    use HasFactory;

    /** Tópicos padrão da pauta estruturada. */
    public const AGENDA_TOPICS = [
        'objetivo' => 'Objetivo da reunião',
        'processo_atual' => 'Entendimento do processo atual',
        'dificuldades' => 'Principais dificuldades',
        'gaps' => 'Gaps identificados',
        'impactos' => 'Impactos',
        'melhorias' => 'Melhorias desejadas',
        'priorizacao' => 'Priorização',
        'proximos_passos' => 'Próximos passos',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'status' => MeetingStatus::class,
            'agenda' => 'array',
        ];
    }

    /** @return BelongsTo<Sector, $this> */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /** @return BelongsTo<User, $this> */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /** @return BelongsTo<User, $this> */
    public function itOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'it_owner_id');
    }

    /** @return BelongsTo<Meeting, $this> */
    public function previousMeeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'previous_meeting_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_participants');
    }

    /** @return BelongsToMany<Gap, $this> */
    public function gaps(): BelongsToMany
    {
        return $this->belongsToMany(Gap::class);
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** Líder só vê reuniões do próprio setor. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role->isSectorScoped()
            ? $query->where('sector_id', $user->sector_id)
            : $query;
    }
}
