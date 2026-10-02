<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /** Dashboard executivo (o líder recebe automaticamente só o seu setor). */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->dashboard->executive($request->user(), $request->integer('sector_id') ?: null));
    }

    public function sector(Request $request, Sector $sector): JsonResponse
    {
        $user = $request->user();
        abort_if($user->role->isSectorScoped() && $user->sector_id !== $sector->id, 403);

        return response()->json([
            'sector' => $sector->only(['id', 'name']),
            ...$this->dashboard->executive($user, $sector->id),
        ]);
    }
}
