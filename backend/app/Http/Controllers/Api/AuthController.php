<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autenticação por token (Laravel Sanctum).
 * O frontend guarda o token e envia "Authorization: Bearer <token>" em cada requisição.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('sector')->where('email', $credentials['email'])->first();

        // Mesma mensagem para e-mail inexistente e senha errada: não revela quais e-mails existem.
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }
        if (! $user->active) {
            throw ValidationException::withMessages(['email' => 'Usuário inativo. Procure o administrador.']);
        }

        $token = $user->createToken('spa', expiresAt: now()->addHours(12))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()->load('sector')));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    /** Inclui as permissões para o frontend esconder botões que o usuário não pode usar. */
    private function userPayload(User $user): array
    {
        return [
            ...(new UserResource($user))->resolve(),
            'permissions' => $user->role->permissions(),
        ];
    }
}
