<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gap;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Busca global. Se o termo for um código exato (GAP-0025 / INC-0003),
 * devolve "redirect" para o frontend abrir o GAP direto.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));
        $user = $request->user();

        if (mb_strlen($term) < 2) {
            return response()->json(['redirect' => null, 'gaps' => [], 'meetings' => []]);
        }

        if (preg_match('/^(GAP|INC)-?(\d+)$/i', $term, $m)) {
            $code = sprintf('%s-%04d', strtoupper($m[1]), (int) $m[2]);
            $exact = Gap::visibleTo($user)->where('code', $code)->first(['id', 'code']);
            if ($exact) {
                return response()->json(['redirect' => "/gaps/{$exact->id}", 'gaps' => [], 'meetings' => []]);
            }
        }

        $like = "%{$term}%";
        $gaps = Gap::visibleTo($user)
            ->where(fn ($q) => $q
                ->where('code', 'like', $like)
                ->orWhere('title', 'like', $like)
                ->orWhere('status', 'like', $like)
                ->orWhere('priority', 'like', $like)
                ->orWhereHas('sector', fn ($s) => $s->where('name', 'like', $like))
                ->orWhereHas('systems', fn ($s) => $s->where('name', 'like', $like))
                ->orWhereHas('category', fn ($s) => $s->where('name', 'like', $like))
                ->orWhereHas('itOwner', fn ($s) => $s->where('name', 'like', $like))
                ->orWhereHas('sprint', fn ($s) => $s->where('name', 'like', $like)))
            ->with('sector:id,name')
            ->limit(10)
            ->get()
            ->map(fn (Gap $g) => [
                'id' => $g->id, 'code' => $g->code, 'title' => $g->title,
                'sector' => $g->sector?->name, 'status' => $g->statusEnum()->label(),
            ]);

        $meetings = $user->hasPermission('meetings.view')
            ? Meeting::visibleTo($user)->where('title', 'like', $like)->with('sector:id,name')->limit(5)->get()
                ->map(fn (Meeting $m) => [
                    'id' => $m->id, 'title' => $m->title, 'date' => $m->date->format('Y-m-d'), 'sector' => $m->sector?->name,
                ])
            : [];

        return response()->json(['redirect' => null, 'gaps' => $gaps, 'meetings' => $meetings]);
    }
}
