<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GapResource;
use App\Models\Gap;
use App\Services\HomologationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HomologationController extends Controller
{
    public function __construct(private readonly HomologationService $service) {}

    /** TI: envia para homologação com a solução e a evidência. */
    public function send(Request $request, Gap $gap): GapResource
    {
        $data = $request->validate([
            'solution' => ['required', 'string'],
            'solution_evidence' => ['required', 'string'],
        ]);

        $this->service->send($gap, $request->user(), $data['solution'], $data['solution_evidence']);

        return $this->detail($gap);
    }

    /** Líder: aprova ou reprova. */
    public function decide(Request $request, Gap $gap): GapResource
    {
        Gate::authorize('decideHomologation', $gap);
        $data = $request->validate([
            'approved' => ['required', 'boolean'],
            'test_result' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->service->decide($gap, $request->user(), $data['approved'], $data['test_result'], $data['notes'] ?? null);

        return $this->detail($gap);
    }

    private function detail(Gap $gap): GapResource
    {
        return new GapResource($gap->refresh()->load([
            'sector:id,name', 'itOwner:id,name', 'sectorOwner:id,name', 'completer:id,name',
            'homologations.submitter:id,name', 'homologations.tester:id,name',
        ]));
    }
}
