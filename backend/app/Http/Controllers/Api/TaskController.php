<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Gap;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Gap $gap): AnonymousResourceCollection
    {
        Gate::authorize('view', $gap);

        return TaskResource::collection($gap->tasks()->with('assignee:id,name')->get());
    }

    public function store(Request $request, Gap $gap): TaskResource
    {
        $task = $gap->tasks()->create($request->validate($this->rules(true)));

        return new TaskResource($task->load('assignee:id,name'));
    }

    public function update(Request $request, Task $task): TaskResource
    {
        $task->update($request->validate($this->rules(false)));

        return new TaskResource($task->load('assignee:id,name'));
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }

    private function rules(bool $creating): array
    {
        return [
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
