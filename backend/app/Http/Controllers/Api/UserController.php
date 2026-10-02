<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::with('sector')
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")))
            ->when($request->query('role'), fn ($q, $r) => $q->where('role', $r))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return UserResource::collection($users);
    }

    public function store(Request $request): UserResource
    {
        $data = $request->validate($this->rules());

        return new UserResource(User::create($data)->load('sector'));
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('sector'));
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate($this->rules($user));
        if (blank($data['password'] ?? null)) {
            unset($data['password']); // senha só muda se for informada
        }
        $user->update($data);

        return new UserResource($user->load('sector'));
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 422, 'Você não pode desativar o próprio usuário.');

        // Não excluímos usuários: eles aparecem em históricos e comentários. Apenas desativamos.
        $user->update(['active' => false]);
        $user->tokens()->delete();

        return response()->noContent();
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => [$user ? 'sometimes' : 'required', 'string', 'max:255'],
            'email' => [$user ? 'sometimes' : 'required', 'email', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', Password::min(8)],
            'role' => [$user ? 'sometimes' : 'required', Rule::enum(Role::class)],
            'sector_id' => ['nullable', 'exists:sectors,id', 'required_if:role,lider'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
