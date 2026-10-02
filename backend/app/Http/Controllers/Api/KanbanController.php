<?php

namespace App\Http\Controllers\Api;

use App\Enums\KanbanColumn;
use App\Http\Controllers\Controller;
use App\Http\Resources\GapListResource;
use App\Models\Gap;
use App\Services\KanbanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class KanbanController extends Controller
{
    public function __construct(private readonly KanbanService $kanban) {}

    public function index(Request $request): JsonResponse
    {
        $columns = $this->kanban->board($request->user(), $request->query())
            ->map(fn ($col) => [...$col, 'gaps' => GapListResource::collection($col['gaps'])]);

        return response()->json($columns);
    }

    public function move(Request $request, Gap $gap): GapListResource
    {
        $data = $request->validate([
            'column' => ['required', Rule::enum(KanbanColumn::class)],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $column = KanbanColumn::from($data['column']);
        Gate::authorize('transition', [$gap, $column->targetStatus()?->value]);

        $gap = $this->kanban->move($gap, $column, $request->user(), $data['reason'] ?? null);

        return new GapListResource($gap->load(['sector:id,name', 'itOwner:id,name', 'sprint:id,number,name', 'systems:id,name']));
    }
}
