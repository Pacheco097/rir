<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso na rota: ->middleware('permission:gaps.create')
 * Aceita várias permissões separadas por "|" (basta ter uma).
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        $allowed = $user && collect(explode('|', $permissions))
            ->contains(fn (string $p) => $user->hasPermission($p));

        abort_unless($allowed, 403, 'Você não tem permissão para executar esta ação.');

        return $next($request);
    }
}
