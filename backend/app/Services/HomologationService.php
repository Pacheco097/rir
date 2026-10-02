<?php

namespace App\Services;

use App\Enums\GapStatus;
use App\Enums\GapType;
use App\Enums\HomologationResult;
use App\Exceptions\BusinessRuleException;
use App\Models\Gap;
use App\Models\Homologation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Processo de homologação:
 *
 *   EM EXECUÇÃO → HOMOLOGAÇÃO → líder testa → APROVADO  → CONCLUÍDO
 *                                          → REPROVADO → REABERTO
 */
class HomologationService
{
    /** TI envia a solução para o setor validar. */
    public function send(Gap $gap, User $user, ?string $solution = null, ?string $evidence = null): Gap
    {
        if ($gap->type !== GapType::Gap) {
            throw new BusinessRuleException('Incidentes usam a etapa de Validação do fluxo Kanban.');
        }

        $from = GapStatus::from($gap->status);
        if (! in_array($from, [GapStatus::EmExecucao, GapStatus::Reaberto], true)) {
            throw new BusinessRuleException('Somente GAPs em execução (ou reabertos) podem ser enviados para homologação.');
        }

        $gap->solution = $solution ?? $gap->solution;
        $gap->solution_evidence = $evidence ?? $gap->solution_evidence;

        if (blank($gap->solution) || blank($gap->solution_evidence)) {
            throw new BusinessRuleException('Informe a solução aplicada e a evidência antes de enviar para homologação.');
        }
        if (blank($gap->it_owner_id)) {
            throw new BusinessRuleException('Defina o responsável TI antes de enviar para homologação.');
        }

        return DB::transaction(function () use ($gap, $user) {
            $gap->status = GapStatus::Homologacao->value;
            $gap->save();

            $gap->homologations()->create([
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);

            return $gap;
        });
    }

    /** Líder do setor registra o resultado do teste. */
    public function decide(Gap $gap, User $user, bool $approved, string $testResult, ?string $notes = null): Homologation
    {
        if ($gap->status !== GapStatus::Homologacao->value) {
            throw new BusinessRuleException('Este GAP não está aguardando homologação.');
        }

        /** @var Homologation|null $homologation */
        $homologation = $gap->homologations()->whereNull('result')->first();
        if (! $homologation) {
            throw new BusinessRuleException('Nenhuma homologação pendente para este GAP.');
        }

        return DB::transaction(function () use ($gap, $user, $approved, $testResult, $notes, $homologation) {
            $homologation->update([
                'tested_by' => $user->id,
                'tested_at' => now(),
                'result' => $approved ? HomologationResult::Aprovado : HomologationResult::Reprovado,
                'test_result' => $testResult,
                'notes' => $notes,
            ]);

            if ($approved) {
                $gap->status = GapStatus::Concluido->value;
                $gap->completed_at = now();
                $gap->completed_by = $homologation->submitted_by ?? $gap->it_owner_id;
            } else {
                $gap->status = GapStatus::Reaberto->value;
            }
            $gap->save();

            $label = $approved ? 'APROVADA' : 'REPROVADA';
            $gap->comments()->create([
                'user_id' => $user->id,
                'body' => "[Homologação {$label}] {$testResult}".($notes ? "\nObs.: {$notes}" : ''),
            ]);

            return $homologation;
        });
    }
}
