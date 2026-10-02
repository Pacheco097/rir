<?php

namespace App\Http\Controllers\Api;

use App\Enums\SystemType;
use App\Http\Controllers\Controller;
use App\Http\Resources\SystemResource;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SystemController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SystemResource::collection(System::with('owner')->withCount('gaps')->orderBy('name')->get());
    }

    public function store(Request $request): SystemResource
    {
        return new SystemResource(System::create($request->validate($this->rules()))->load('owner'));
    }

    public function show(System $system): SystemResource
    {
        return new SystemResource($system->load('owner')->loadCount('gaps'));
    }

    public function update(Request $request, System $system): SystemResource
    {
        $system->update($request->validate($this->rules($system)));

        return new SystemResource($system->load('owner'));
    }

    public function destroy(System $system)
    {
        abort_if($system->gaps()->exists(), 422, 'Sistema possui GAPs vinculados. Desative-o em vez de excluir.');
        $system->delete();

        return response()->noContent();
    }

    private function rules(?System $system = null): array
    {
        return [
            'name' => [$system ? 'sometimes' : 'required', 'string', 'max:100', Rule::unique('systems')->ignore($system)],
            'description' => ['nullable', 'string'],
            'type' => [$system ? 'sometimes' : 'required', Rule::enum(SystemType::class)],
            'owner_id' => ['nullable', 'exists:users,id'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
