<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GapCommentResource;
use App\Models\Gap;
use App\Services\GapService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Comentários são apenas inseridos — nunca editados — para preservar o histórico. */
class GapCommentController extends Controller
{
    public function index(Gap $gap): AnonymousResourceCollection
    {
        Gate::authorize('view', $gap);

        return GapCommentResource::collection($gap->comments()->with('user.sector')->get());
    }

    public function store(Request $request, Gap $gap, GapService $service): GapCommentResource
    {
        Gate::authorize('comment', $gap);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $comment = $service->comment($gap, $request->user(), $data['body']);

        return new GapCommentResource($comment->load('user.sector'));
    }
}
