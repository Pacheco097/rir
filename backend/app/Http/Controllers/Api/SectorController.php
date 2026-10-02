<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectorResource;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SectorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SectorResource::collection(Sector::with('leader')->withCount('gaps')->orderBy('name')->get());
    }

    public function store(Request $request): SectorResource
    {
        return new SectorResource(Sector::create($request->validate($this->rules()))->load('leader'));
    }

    public function show(Sector $sector): SectorResource
    {
        return new SectorResource($sector->load('leader')->loadCount('gaps'));
    }

    public function update(Request $request, Sector $sector): SectorResource
    {
        $sector->update($request->validate($this->rules($sector)));

        return new SectorResource($sector->load('leader'));
    }

    public function destroy(Sector $sector)
    {
        abort_if($sector->gaps()->exists() || $sector->meetings()->exists(), 422,
            'Setor possui GAPs ou reuniões vinculadas. Desative-o em vez de excluir.');
        $sector->delete();

        return response()->noContent();
    }

    private function rules(?Sector $sector = null): array
    {
        return [
            'name' => [$sector ? 'sometimes' : 'required', 'string', 'max:100', Rule::unique('sectors')->ignore($sector)],
            'description' => ['nullable', 'string'],
            'leader_id' => ['nullable', 'exists:users,id'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
