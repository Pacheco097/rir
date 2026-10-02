<?php

namespace App\Http\Requests;

use App\Enums\Frequency;
use App\Enums\GapType;
use App\Enums\Impact;
use App\Enums\Origin;
use App\Enums\Priority;
use App\Enums\Urgency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request: a validação sai do controller e fica numa classe própria.
 * Se falhar, o Laravel responde 422 automaticamente com os erros por campo.
 */
class StoreGapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a permissão já é checada pelo middleware da rota
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(GapType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'sector_id' => [$this->user()->role->isSectorScoped() ? 'nullable' : 'required', 'exists:sectors,id'],
            'process' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'origin' => ['nullable', Rule::enum(Origin::class)],
            'impact' => ['required', Rule::enum(Impact::class)],
            'urgency' => ['required', Rule::enum(Urgency::class)],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'frequency' => ['nullable', Rule::enum(Frequency::class)],
            // Regra 3 também validada aqui para a mensagem aparecer no campo certo do formulário.
            'critical_justification' => ['nullable', 'string', 'required_if:impact,critico', 'required_if:priority,critica'],
            'sector_owner_id' => ['nullable', 'exists:users,id'],
            'it_owner_id' => ['nullable', 'exists:users,id'],
            'identified_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'sprint_id' => ['nullable', 'exists:sprints,id'],
            'notes' => ['nullable', 'string'],
            'system_ids' => ['array'],
            'system_ids.*' => ['integer', 'exists:systems,id'],
            'meeting_ids' => ['array'],
            'meeting_ids.*' => ['integer', 'exists:meetings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'critical_justification.required_if' => 'GAPs críticos exigem justificativa.',
        ];
    }
}
