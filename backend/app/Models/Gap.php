<?php

namespace App\Models;

use App\Enums\Frequency;
use App\Enums\GapStatus;
use App\Enums\GapType;
use App\Enums\Impact;
use App\Enums\IncidentStatus;
use App\Enums\Origin;
use App\Enums\Priority;
use App\Enums\Urgency;
use App\Observers\GapObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entidade central do sistema: representa um GAP/Melhoria ou um Incidente crítico.
 *
 * O campo "status" é string porque o conjunto de valores depende do tipo:
 * GAP usa GapStatus e Incidente usa IncidentStatus. O método statusEnum()
 * devolve o Enum correto.
 */
#[ObservedBy(GapObserver::class)]
#[Fillable([
    'code', 'type', 'title', 'description', 'sector_id', 'process', 'category_id', 'origin',
    'impact', 'urgency', 'priority', 'frequency', 'critical_justification',
    'sector_owner_id', 'it_owner_id', 'identified_at', 'due_date', 'sprint_id', 'status',
    'notes', 'solution', 'solution_evidence', 'completed_at', 'completed_by', 'created_by',
])]
class Gap extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => GapType::class,
            'impact' => Impact::class,
            'urgency' => Urgency::class,
            'priority' => Priority::class,
            'origin' => Origin::class,
            'frequency' => Frequency::class,
            'identified_at' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
        ];
    }

    public function statusEnum(): GapStatus|IncidentStatus
    {
        return $this->type === GapType::Incidente
            ? IncidentStatus::from($this->status)
            : GapStatus::from($this->status);
    }

    public function isOpen(): bool
    {
        return $this->statusEnum()->isOpen();
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->isOpen() && $this->due_date->lt(today());
    }

    /* ---------------------------------------------------------------- */
    /* Relacionamentos */
    /* ---------------------------------------------------------------- */

    /** @return BelongsTo<Sector, $this> */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Sprint, $this> */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sectorOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sector_owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function itOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'it_owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @return BelongsToMany<System, $this> */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class);
    }

    /** @return BelongsToMany<Meeting, $this> */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class);
    }

    /** @return BelongsToMany<Gap, $this> */
    public function relatedGaps(): BelongsToMany
    {
        return $this->belongsToMany(Gap::class, 'gap_links', 'gap_id', 'related_gap_id');
    }

    /** @return HasMany<GapComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(GapComment::class)->latest();
    }

    /** @return HasMany<GapHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(GapHistory::class)->latest('created_at')->latest('id');
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<Homologation, $this> */
    public function homologations(): HasMany
    {
        return $this->hasMany(Homologation::class)->latest('submitted_at')->latest('id');
    }

    /** @return HasOne<Homologation, $this> */
    public function latestHomologation(): HasOne
    {
        return $this->hasOne(Homologation::class)->latestOfMany('id');
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /* ---------------------------------------------------------------- */
    /* Scopes (filtros reutilizáveis) */
    /* ---------------------------------------------------------------- */

    /** Regra de visibilidade: líder vê apenas os GAPs do próprio setor. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role->isSectorScoped()
            ? $query->where('gaps.sector_id', $user->sector_id)
            : $query;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('gaps.status', [
            GapStatus::Concluido->value, GapStatus::Cancelado->value, IncidentStatus::Encerrado->value,
        ]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('gaps.due_date')->whereDate('gaps.due_date', '<', today());
    }

    /**
     * Aplica os filtros vindos da query string (?sector_id=1&priority=alta...).
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['sector_id'] ?? null, fn ($q, $v) => $q->where('sector_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->whereIn('status', (array) $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->whereIn('priority', (array) $v))
            ->when($filters['impact'] ?? null, fn ($q, $v) => $q->where('impact', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['it_owner_id'] ?? null, fn ($q, $v) => $q->where('it_owner_id', $v))
            ->when($filters['sprint_id'] ?? null, fn ($q, $v) => $v === 'none'
                ? $q->whereNull('sprint_id')
                : $q->where('sprint_id', $v))
            ->when($filters['system_id'] ?? null, fn ($q, $v) => $q->whereHas(
                'systems', fn ($s) => $s->where('systems.id', $v)
            ))
            ->when(($filters['overdue'] ?? null) && filter_var($filters['overdue'], FILTER_VALIDATE_BOOL), fn ($q) => $q->overdue())
            ->when(($filters['open'] ?? null) && filter_var($filters['open'], FILTER_VALIDATE_BOOL), fn ($q) => $q->open())
            ->when($filters['q'] ?? null, function ($q, $term) {
                $q->where(fn ($w) => $w
                    ->where('code', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%"));
            });
    }
}
