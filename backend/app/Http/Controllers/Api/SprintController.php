<?php

namespace App\Http\Controllers\Api;

use App\Enums\GapType;
use App\Enums\SprintStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SprintResource;
use App\Models\Gap;
use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SprintController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $sprints = Sprint::withCount([
            'gaps',
            'gaps as completed_count' => fn ($q) => $q->where('status', 'concluido'),
        ])->orderByDesc('number')->get();

        return SprintResource::collection($sprints);
    }

    public function store(Request $request): SprintResource
    {
        $data = $request->validate($this->rules());
        $data['number'] ??= (Sprint::max('number') ?? 0) + 1;

        return new SprintResource(Sprint::create($data));
    }

    public function show(Request $request, Sprint $sprint): SprintResource
    {
        return new SprintResource($sprint->load(['gaps' => fn ($q) => $q
            ->visibleTo($request->user())
            ->with(['sector:id,name', 'itOwner:id,name'])])
            ->loadCount('gaps'));
    }

    public function update(Request $request, Sprint $sprint): SprintResource
    {
        $sprint->update($request->validate($this->rules($sprint)));

        return new SprintResource($sprint);
    }

    public function destroy(Sprint $sprint)
    {
        abort_if($sprint->status !== SprintStatus::Planejamento, 422, 'Somente sprints em planejamento podem ser excluídas.');
        $sprint->delete(); // gaps.sprint_id volta para NULL (nullOnDelete)

        return response()->noContent();
    }

    /**
     * Associa GAPs à sprint. Usa update() em cada model (e não um UPDATE em massa)
     * para que o Observer registre a mudança de Sprint no histórico de cada GAP.
     */
    public function assignGaps(Request $request, Sprint $sprint): SprintResource
    {
        $data = $request->validate([
            'gap_ids' => ['required', 'array', 'min:1'],
            'gap_ids.*' => ['integer', 'exists:gaps,id'],
        ]);

        abort_if($sprint->status === SprintStatus::Encerrada, 422, 'Sprint encerrada não recebe novos GAPs.');

        DB::transaction(function () use ($data, $sprint) {
            Gap::whereIn('id', $data['gap_ids'])->where('type', GapType::Gap->value)->get()
                ->each(fn (Gap $gap) => $gap->update(['sprint_id' => $sprint->id]));
        });

        return $this->show($request, $sprint);
    }

    public function removeGap(Request $request, Sprint $sprint, Gap $gap): SprintResource
    {
        abort_unless($gap->sprint_id === $sprint->id, 404);
        $gap->update(['sprint_id' => null]);

        return $this->show($request, $sprint);
    }

    private function rules(?Sprint $sprint = null): array
    {
        $req = $sprint ? 'sometimes' : 'required';

        return [
            'number' => ['sometimes', 'integer', 'min:1', Rule::unique('sprints')->ignore($sprint)],
            'name' => [$req, 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'start_date' => [$req, 'date'],
            'end_date' => [$req, 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::enum(SprintStatus::class)],
        ];
    }
}
