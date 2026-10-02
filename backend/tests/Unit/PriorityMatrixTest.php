<?php

namespace Tests\Unit;

use App\Enums\GapStatus;
use App\Enums\Impact;
use App\Enums\Priority;
use App\Enums\Urgency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Testes unitários puros: não sobem o Laravel nem o banco, por isso são instantâneos. */
class PriorityMatrixTest extends TestCase
{
    public static function matrix(): array
    {
        return [
            'crítico + imediata' => [Impact::Critico, Urgency::Imediata, Priority::Critica],
            'crítico + curto prazo' => [Impact::Critico, Urgency::CurtoPrazo, Priority::Critica],
            'alto + curto prazo' => [Impact::Alto, Urgency::CurtoPrazo, Priority::Alta],
            'médio + médio prazo' => [Impact::Medio, Urgency::MedioPrazo, Priority::Media],
            'baixo + sem urgência' => [Impact::Baixo, Urgency::SemUrgencia, Priority::Baixa],
        ];
    }

    #[DataProvider('matrix')]
    public function test_suggests_priority_from_impact_and_urgency(Impact $impact, Urgency $urgency, Priority $expected): void
    {
        $this->assertSame($expected, Priority::suggest($impact, $urgency));
    }

    public function test_workflow_follows_the_main_path(): void
    {
        $path = [
            GapStatus::Levantado, GapStatus::Triagem, GapStatus::Analise, GapStatus::Priorizado,
            GapStatus::Backlog, GapStatus::Planejado, GapStatus::EmExecucao, GapStatus::Homologacao,
        ];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue($path[$i]->canTransitionTo($path[$i + 1]), "{$path[$i]->value} -> {$path[$i + 1]->value}");
        }
    }

    public function test_cannot_skip_from_raised_to_execution(): void
    {
        $this->assertFalse(GapStatus::Levantado->canTransitionTo(GapStatus::EmExecucao));
        $this->assertFalse(GapStatus::Levantado->canTransitionTo(GapStatus::Concluido));
    }
}
