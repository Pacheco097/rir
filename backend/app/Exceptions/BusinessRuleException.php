<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Violação de regra de negócio (ex.: concluir GAP sem homologação).
 *
 * Diferente de um erro de validação de formulário: os dados são válidos,
 * mas a operação não é permitida no estado atual. Respondemos 422 com
 * uma mensagem clara para o usuário.
 */
class BusinessRuleException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
