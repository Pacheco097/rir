<?php

namespace App\Http\Requests;

/**
 * Mesmas regras do cadastro, mas todos os campos opcionais ("sometimes"):
 * o cliente envia somente o que mudou (semântica de PATCH).
 */
class UpdateGapRequest extends StoreGapRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['type']);

        foreach ($rules as $field => $fieldRules) {
            if (! str_contains($field, '.')) {
                $rules[$field] = ['sometimes', ...array_filter($fieldRules, fn ($r) => $r !== 'required' && ! (is_string($r) && str_starts_with($r, 'required_if')))];
            }
        }

        return $rules;
    }
}
