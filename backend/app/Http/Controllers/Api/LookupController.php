<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnumOptions;
use App\Enums\Frequency;
use App\Enums\GapStatus;
use App\Enums\GapType;
use App\Enums\Impact;
use App\Enums\IncidentStatus;
use App\Enums\KanbanColumn;
use App\Enums\MeetingStatus;
use App\Enums\Origin;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\SprintStatus;
use App\Enums\SystemType;
use App\Enums\TaskStatus;
use App\Enums\Urgency;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Meeting;
use App\Models\Sector;
use App\Models\Sprint;
use App\Models\System;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Entrega de uma vez todas as listas usadas em selects/filtros.
 * Evita dezenas de requisições ao abrir um formulário.
 */
class LookupController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'enums' => [
                'gap_types' => EnumOptions::of(GapType::class),
                'gap_statuses' => EnumOptions::of(GapStatus::class),
                'incident_statuses' => EnumOptions::of(IncidentStatus::class),
                'impacts' => array_map(fn (Impact $i) => [
                    'value' => $i->value, 'label' => $i->label(), 'description' => $i->description(),
                ], Impact::cases()),
                'urgencies' => EnumOptions::of(Urgency::class),
                'priorities' => EnumOptions::of(Priority::class),
                'origins' => EnumOptions::of(Origin::class),
                'frequencies' => EnumOptions::of(Frequency::class),
                'meeting_statuses' => EnumOptions::of(MeetingStatus::class),
                'sprint_statuses' => EnumOptions::of(SprintStatus::class),
                'task_statuses' => EnumOptions::of(TaskStatus::class),
                'system_types' => EnumOptions::of(SystemType::class),
                'roles' => EnumOptions::of(Role::class),
                'kanban_columns' => EnumOptions::of(KanbanColumn::class),
            ],
            'agenda_topics' => collect(Meeting::AGENDA_TOPICS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'sectors' => Sector::where('active', true)->orderBy('name')->get(['id', 'name', 'leader_id']),
            'systems' => System::where('active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::where('active', true)->orderBy('name')->get(['id', 'name', 'color']),
            'sprints' => Sprint::orderByDesc('number')->get(['id', 'number', 'name', 'status']),
            'users' => User::where('active', true)->orderBy('name')->get(['id', 'name', 'role', 'sector_id']),
        ]);
    }
}
