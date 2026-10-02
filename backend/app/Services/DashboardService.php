<?php

namespace App\Services;

use App\Enums\Frequency;
use App\Enums\GapStatus;
use App\Enums\Impact;
use App\Enums\IncidentStatus;
use App\Enums\MeetingStatus;
use App\Enums\Priority;
use App\Models\Gap;
use App\Models\Meeting;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores gerenciais.
 *
 * As contagens são feitas no banco (GROUP BY / COUNT) e não carregando todos
 * os registros em memória — importante quando a base tiver milhares de GAPs.
 */
class DashboardService
{
    /** @return array<string, mixed> */
    public function executive(User $user, ?int $sectorId = null): array
    {
        $base = fn (): Builder => Gap::query()
            ->visibleTo($user)
            ->when($sectorId, fn ($q) => $q->where('gaps.sector_id', $sectorId));

        return [
            'totals' => $this->totals($base),
            'by_sector' => $this->groupCount($base(), 'sectors', 'sector_id'),
            'by_category' => $this->groupCount($base(), 'categories', 'category_id'),
            'by_owner' => $this->groupCount($base()->open(), 'users', 'it_owner_id'),
            'by_system' => $this->bySystem($base()),
            'by_priority' => $this->byEnum($base()->open(), 'priority', Priority::class),
            'by_status' => $this->byStatus($base()),
            'evolution' => $this->evolution($base()),
            'completed_by_sprint' => $this->completedBySprint($base()),
            'overdue' => $this->gapList($base()->overdue()->orderBy('due_date')),
            'priority_gaps' => $this->gapList($base()->open()
                ->whereIn('priority', [Priority::Critica->value, Priority::Alta->value])
                ->orderByRaw("CASE priority WHEN 'critica' THEN 0 ELSE 1 END")
                ->orderBy('due_date')),
            'recurring' => $this->gapList($base()->open()
                ->whereIn('frequency', [Frequency::Diaria->value, Frequency::Continua->value])),
            'pending_validation' => $this->gapList($base()->where('status', GapStatus::Homologacao->value)),
            'upcoming_meetings' => $this->meetings($user, $sectorId, upcoming: true),
            'recent_meetings' => $this->meetings($user, $sectorId, upcoming: false),
        ];
    }

    /** @return array<string, int|float|null> */
    private function totals(callable $base): array
    {
        $completedStatuses = [GapStatus::Concluido->value, IncidentStatus::Encerrado->value];

        $completed = $base()->whereIn('status', $completedStatuses)->whereNotNull('completed_at')
            ->get(['identified_at', 'created_at', 'completed_at']);

        // Média calculada em PHP para funcionar igual em MySQL e SQLite (funções de data diferem).
        $avgDays = $completed->isEmpty() ? null : round($completed->avg(
            fn (Gap $g) => Carbon::parse($g->identified_at ?? $g->created_at)->diffInDays($g->completed_at)
        ), 1);

        return [
            'total' => $base()->count(),
            'open' => $base()->open()->count(),
            'critical' => $base()->open()->where(fn ($q) => $q
                ->where('priority', Priority::Critica->value)
                ->orWhere('impact', Impact::Critico->value))->count(),
            'in_progress' => $base()->whereIn('status', [GapStatus::EmExecucao->value, IncidentStatus::EmAtendimento->value])->count(),
            'blocked' => $base()->where('status', GapStatus::Bloqueado->value)->count(),
            'homologation' => $base()->whereIn('status', [GapStatus::Homologacao->value, IncidentStatus::Validacao->value])->count(),
            'completed' => $base()->whereIn('status', $completedStatuses)->count(),
            'overdue' => $base()->overdue()->count(),
            'incidents_open' => $base()->open()->where('type', 'incidente')->count(),
            'avg_resolution_days' => $avgDays,
        ];
    }

    /** @return list<array{id:int|null,label:string,total:int}> */
    private function groupCount(Builder $query, string $table, string $fk): array
    {
        $labelColumn = 'name';

        return $query
            ->leftJoin($table, "{$table}.id", '=', "gaps.{$fk}")
            ->select("gaps.{$fk} as id", DB::raw("COALESCE({$table}.{$labelColumn}, 'Não definido') as label"), DB::raw('COUNT(*) as total'))
            ->groupBy("gaps.{$fk}", "{$table}.{$labelColumn}")
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'label' => $r->label, 'total' => (int) $r->total])
            ->all();
    }

    /** @return list<array{id:int,label:string,total:int}> */
    private function bySystem(Builder $query): array
    {
        return $query
            ->join('gap_system', 'gap_system.gap_id', '=', 'gaps.id')
            ->join('systems', 'systems.id', '=', 'gap_system.system_id')
            ->select('systems.id', 'systems.name as label', DB::raw('COUNT(*) as total'))
            ->groupBy('systems.id', 'systems.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'label' => $r->label, 'total' => (int) $r->total])
            ->all();
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     * @return list<array{key:string,label:string,total:int}>
     */
    private function byEnum(Builder $query, string $column, string $enum): array
    {
        $counts = $query->select($column, DB::raw('COUNT(*) as total'))->groupBy($column)->pluck('total', $column);

        return array_map(fn ($case) => [
            'key' => $case->value,
            'label' => $case->label(),
            'total' => (int) ($counts[$case->value] ?? 0),
        ], $enum::cases());
    }

    /** @return list<array{key:string,label:string,total:int}> */
    private function byStatus(Builder $query): array
    {
        return $query->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->get()
            ->map(fn ($r) => [
                'key' => $r->status,
                'label' => (GapStatus::tryFrom($r->status) ?? IncidentStatus::tryFrom($r->status))?->label() ?? $r->status,
                'total' => (int) $r->total,
            ])->sortByDesc('total')->values()->all();
    }

    /** Criados x concluídos por mês (últimos 6 meses). */
    private function evolution(Builder $query): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $rows = $query->where(fn ($q) => $q->where('created_at', '>=', $start)->orWhere('completed_at', '>=', $start))
            ->get(['created_at', 'completed_at']);

        return collect(range(0, 5))->map(function (int $i) use ($start, $rows) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');

            return [
                'month' => $month->format('m/Y'),
                'created' => $rows->filter(fn ($g) => $g->created_at?->format('Y-m') === $key)->count(),
                'completed' => $rows->filter(fn ($g) => $g->completed_at?->format('Y-m') === $key)->count(),
            ];
        })->all();
    }

    private function completedBySprint(Builder $query): array
    {
        $counts = $query->whereNotNull('sprint_id')
            ->select('sprint_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as completed"))
            ->groupBy('sprint_id')->get()->keyBy('sprint_id');

        return Sprint::orderBy('number')->get()->map(fn (Sprint $s) => [
            'id' => $s->id,
            'label' => "Sprint {$s->number}",
            'name' => $s->name,
            'status' => $s->status->value,
            'total' => (int) ($counts[$s->id]->total ?? 0),
            'completed' => (int) ($counts[$s->id]->completed ?? 0),
        ])->all();
    }

    private function gapList(Builder $query, int $limit = 8): array
    {
        return $query->with(['sector:id,name', 'itOwner:id,name'])->limit($limit)->get()
            ->map(fn (Gap $g) => [
                'id' => $g->id,
                'code' => $g->code,
                'title' => $g->title,
                'status' => $g->status,
                'status_label' => $g->statusEnum()->label(),
                'priority' => $g->priority->value,
                'sector' => $g->sector?->name,
                'it_owner' => $g->itOwner?->name,
                'due_date' => $g->due_date?->format('Y-m-d'),
            ])->all();
    }

    private function meetings(User $user, ?int $sectorId, bool $upcoming): array
    {
        return Meeting::query()
            ->visibleTo($user)
            ->when($sectorId, fn ($q) => $q->where('sector_id', $sectorId))
            ->with('sector:id,name')
            ->when($upcoming,
                fn ($q) => $q->whereDate('date', '>=', today())
                    ->whereIn('status', [MeetingStatus::Agendada->value, MeetingStatus::Confirmada->value, MeetingStatus::Reagendada->value])
                    ->orderBy('date')->orderBy('start_time'),
                fn ($q) => $q->where('status', MeetingStatus::Realizada->value)->orderByDesc('date'))
            ->limit(5)
            ->get()
            ->map(fn (Meeting $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'date' => $m->date->format('Y-m-d'),
                'start_time' => substr((string) $m->start_time, 0, 5),
                'sector' => $m->sector?->name,
                'status' => $m->status->value,
            ])->all();
    }
}
