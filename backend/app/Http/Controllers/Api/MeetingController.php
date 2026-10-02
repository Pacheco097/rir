<?php

namespace App\Http\Controllers\Api;

use App\Enums\MeetingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MeetingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $meetings = Meeting::query()
            ->visibleTo($request->user())
            ->with(['sector:id,name', 'leader:id,name', 'itOwner:id,name'])
            ->withCount('gaps')
            ->when($request->query('sector_id'), fn ($q, $v) => $q->where('sector_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            // Filtro por período: usado pelo calendário (mês/semana/dia).
            ->when($request->query('from'), fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->query('to'), fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($request->query('q'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->orderBy('date', $request->query('direction') === 'asc' ? 'asc' : 'desc')
            ->orderBy('start_time')
            ->paginate(min($request->integer('per_page', 20), 200));

        return MeetingResource::collection($meetings);
    }

    public function store(MeetingRequest $request): MeetingResource
    {
        $meeting = DB::transaction(function () use ($request) {
            $meeting = Meeting::create([
                ...Arr::except($request->validated(), ['participant_ids', 'gap_ids']),
                'created_by' => $request->user()->id,
            ]);
            $meeting->participants()->sync($request->validated('participant_ids', []));
            $meeting->gaps()->sync($request->validated('gap_ids', []));

            return $meeting;
        });

        return new MeetingResource($this->loadDetail($meeting));
    }

    public function show(Meeting $meeting): MeetingResource
    {
        Gate::authorize('view', $meeting);

        return new MeetingResource($this->loadDetail($meeting));
    }

    public function update(MeetingRequest $request, Meeting $meeting): MeetingResource
    {
        Gate::authorize('update', $meeting);

        DB::transaction(function () use ($request, $meeting) {
            $data = $request->validated();
            if (isset($data['agenda'])) {
                // Mescla para permitir salvar um tópico da pauta por vez.
                $data['agenda'] = array_merge($meeting->agenda ?? [], $data['agenda']);
            }
            $meeting->update(Arr::except($data, ['participant_ids', 'gap_ids']));

            if ($request->has('participant_ids')) {
                $meeting->participants()->sync($data['participant_ids'] ?? []);
            }
            if ($request->has('gap_ids')) {
                $meeting->gaps()->sync($data['gap_ids'] ?? []);
            }
        });

        return new MeetingResource($this->loadDetail($meeting));
    }

    public function destroy(Meeting $meeting)
    {
        Gate::authorize('delete', $meeting);
        $meeting->delete();

        return response()->noContent();
    }

    /** "Criar próxima reunião": copia setor, líder, TI e participantes. */
    public function scheduleNext(Request $request, Meeting $meeting): MeetingResource
    {
        Gate::authorize('update', $meeting);
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $next = DB::transaction(function () use ($meeting, $data, $request) {
            $next = Meeting::create([
                'title' => $data['title'] ?? "Follow-up: {$meeting->title}",
                'sector_id' => $meeting->sector_id,
                'leader_id' => $meeting->leader_id,
                'it_owner_id' => $meeting->it_owner_id,
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'] ?? null,
                'objective' => $meeting->next_steps ? "Acompanhar próximos passos:\n{$meeting->next_steps}" : null,
                'status' => MeetingStatus::Agendada,
                'previous_meeting_id' => $meeting->id,
                'created_by' => $request->user()->id,
            ]);
            $next->participants()->sync($meeting->participants()->pluck('users.id'));

            return $next;
        });

        return new MeetingResource($this->loadDetail($next));
    }

    private function loadDetail(Meeting $meeting): Meeting
    {
        return $meeting->refresh()->load([
            'sector:id,name', 'leader:id,name', 'itOwner:id,name', 'participants:id,name',
            'gaps' => fn ($q) => $q->with(['sector:id,name', 'itOwner:id,name']),
            'attachments.user:id,name',
        ]);
    }
}
