<?php

namespace App\Http\Requests;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';
        $agendaRules = collect(Meeting::AGENDA_TOPICS)
            ->mapWithKeys(fn ($label, $key) => ["agenda.{$key}" => ['nullable', 'string']])
            ->all();

        return [
            'title' => [$required, 'string', 'max:255'],
            'sector_id' => [$required, 'exists:sectors,id'],
            'leader_id' => ['nullable', 'exists:users,id'],
            'it_owner_id' => ['nullable', 'exists:users,id'],
            'date' => [$required, 'date'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'objective' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(MeetingStatus::class)],
            'notes' => ['nullable', 'string'],
            'agenda' => ['nullable', 'array'],
            ...$agendaRules,
            'minutes' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'decisions' => ['nullable', 'string'],
            'next_steps' => ['nullable', 'string'],
            'participant_ids' => ['array'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
            'gap_ids' => ['array'],
            'gap_ids.*' => ['integer', 'exists:gaps,id'],
        ];
    }
}
