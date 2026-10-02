<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGapRequest;
use App\Http\Requests\UpdateGapRequest;
use App\Http\Resources\GapHistoryResource;
use App\Http\Resources\GapListResource;
use App\Http\Resources\GapResource;
use App\Models\Gap;
use App\Services\GapService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class GapController extends Controller
{
    public function __construct(private readonly GapService $service) {}

    private const SORTABLE = ['code', 'title', 'priority', 'due_date', 'identified_at', 'status', 'created_at'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $gaps = Gap::query()
            ->visibleTo($request->user())
            ->filter($request->query())
            ->with(['sector:id,name', 'category:id,name,color', 'itOwner:id,name', 'sprint:id,number,name', 'systems:id,name'])
            ->withCount(['tasks', 'comments'])
            // Ordenar prioridade pelo "peso" e não alfabeticamente.
            ->when($sort === 'priority',
                fn ($q) => $q->orderByRaw("CASE priority WHEN 'critica' THEN 4 WHEN 'alta' THEN 3 WHEN 'media' THEN 2 ELSE 1 END {$direction}"),
                fn ($q) => $q->orderBy($sort, $direction))
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 20), 100));

        return GapListResource::collection($gaps);
    }

    public function store(StoreGapRequest $request): GapResource
    {
        $gap = $this->service->create($request->validated(), $request->user());

        return new GapResource($this->loadDetail($gap));
    }

    public function show(Gap $gap): GapResource
    {
        Gate::authorize('view', $gap);

        return new GapResource($this->loadDetail($gap));
    }

    public function update(UpdateGapRequest $request, Gap $gap): GapResource
    {
        Gate::authorize('update', $gap);

        $gap = $this->service->update($gap, $request->validated(), $request->user());

        return new GapResource($this->loadDetail($gap));
    }

    public function destroy(Gap $gap)
    {
        Gate::authorize('delete', $gap);
        $gap->delete(); // soft delete: mantém histórico e o código não é reutilizado

        return response()->noContent();
    }

    public function transition(Request $request, Gap $gap): GapResource
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        Gate::authorize('transition', [$gap, $data['status']]);

        $gap = $this->service->transition($gap, $data['status'], $request->user(), $data['reason'] ?? null);

        return new GapResource($this->loadDetail($gap));
    }

    public function convert(Request $request, Gap $gap): GapResource
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);

        $gap = $this->service->convertIncidentToGap($gap, $request->user(), $data['reason'] ?? null);

        return new GapResource($this->loadDetail($gap));
    }

    public function history(Gap $gap): AnonymousResourceCollection
    {
        Gate::authorize('view', $gap);

        return GapHistoryResource::collection($gap->histories()->with('user:id,name')->get());
    }

    /** Regra 5: vincula GAPs relacionados (nos dois sentidos). */
    public function link(Request $request, Gap $gap): GapResource
    {
        Gate::authorize('update', $gap);
        $data = $request->validate(['related_gap_id' => ['required', 'integer', 'exists:gaps,id', 'different:gap_id']]);
        abort_if((int) $data['related_gap_id'] === $gap->id, 422, 'Um GAP não pode ser vinculado a ele mesmo.');

        $gap->relatedGaps()->syncWithoutDetaching([$data['related_gap_id']]);
        Gap::find($data['related_gap_id'])->relatedGaps()->syncWithoutDetaching([$gap->id]);

        return new GapResource($this->loadDetail($gap));
    }

    public function unlink(Gap $gap, Gap $related): GapResource
    {
        Gate::authorize('update', $gap);

        $gap->relatedGaps()->detach($related->id);
        $related->relatedGaps()->detach($gap->id);

        return new GapResource($this->loadDetail($gap));
    }

    private function loadDetail(Gap $gap): Gap
    {
        return $gap->refresh()->load([
            'sector:id,name', 'category:id,name,color', 'itOwner:id,name', 'sectorOwner:id,name',
            'sprint:id,number,name', 'systems:id,name', 'meetings:id,title,date', 'creator:id,name',
            'completer:id,name', 'tasks.assignee:id,name', 'homologations.submitter:id,name',
            'homologations.tester:id,name', 'attachments.user:id,name',
            'relatedGaps' => fn ($q) => $q->with('sector:id,name'),
        ]);
    }
}
