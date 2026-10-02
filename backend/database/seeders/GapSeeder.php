<?php

namespace Database\Seeders;

use App\Enums\Frequency;
use App\Enums\GapStatus;
use App\Enums\GapType;
use App\Enums\HomologationResult;
use App\Enums\Impact;
use App\Enums\IncidentStatus;
use App\Enums\Origin;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Enums\Urgency;
use App\Models\Category;
use App\Models\Gap;
use App\Models\Meeting;
use App\Models\Sector;
use App\Models\Sprint;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * GAPs fictícios, mas com cara de ambiente real (Protheus, PIMS, Fluig...).
 * Distribuídos em vários status para o Dashboard e o Kanban terem conteúdo.
 */
class GapSeeder extends Seeder
{
    public function run(): void
    {
        Auth::setUser(User::where('email', 'admin@empresa.com')->first());

        $ti = User::where('role', 'ti')->get();
        $sprints = Sprint::all()->keyBy('number');
        $counter = ['GAP' => 0, 'INC' => 0];

        // [setor, título, sistemas, categoria, origem, impacto, urgência, frequência, status, sprint]
        $rows = [
            ['Fiscal', 'Rejeição de NF-e por NCM inválido', ['TOTVS Protheus'], 'Erro', Origin::Protheus, Impact::Critico, Urgency::Imediata, Frequency::Diaria, GapStatus::EmExecucao, 3],
            ['Fiscal', 'Relatório de apuração de ICMS divergente', ['TOTVS Protheus', 'BI'], 'Relatório/BI', Origin::Protheus, Impact::Alto, Urgency::CurtoPrazo, Frequency::Semanal, GapStatus::Homologacao, 3],
            ['Fiscal', 'Automatizar conferência do SPED Fiscal', ['TOTVS Protheus'], 'Melhoria', Origin::ProcessoInterno, Impact::Medio, Urgency::MedioPrazo, Frequency::Eventual, GapStatus::Backlog, null],
            ['Fiscal', 'TES configurada incorretamente para remessa', ['TOTVS Protheus'], 'Dados/Cadastro', Origin::Dados, Impact::Alto, Urgency::CurtoPrazo, Frequency::Diaria, GapStatus::Concluido, 1],
            ['Fiscal', 'Treinamento na rotina de livros fiscais', ['TOTVS Protheus'], 'Treinamento', Origin::Usuario, Impact::Baixo, Urgency::SemUrgencia, Frequency::Unica, GapStatus::Levantado, null],
            ['Financeiro', 'Conciliação bancária manual (CNAB)', ['TOTVS Protheus', 'Integrações'], 'Integração', Origin::Integracao, Impact::Alto, Urgency::CurtoPrazo, Frequency::Diaria, GapStatus::Planejado, 3],
            ['Financeiro', 'Títulos duplicados no contas a pagar', ['TOTVS Protheus', 'Fluig'], 'Erro', Origin::Integracao, Impact::Critico, Urgency::Imediata, Frequency::Semanal, GapStatus::Concluido, 2],
            ['Financeiro', 'Fluxo de caixa projetado no BI', ['BI'], 'Relatório/BI', Origin::ProcessoInterno, Impact::Medio, Urgency::MedioPrazo, Frequency::Continua, GapStatus::Priorizado, 4],
            ['Financeiro', 'Aprovação de pagamentos fora do Fluig', ['Fluig'], 'Processo', Origin::Fluig, Impact::Medio, Urgency::CurtoPrazo, Frequency::Diaria, GapStatus::Analise, null],
            ['Compras', 'Duplicidade de produtos no cadastro', ['TOTVS Protheus'], 'Dados/Cadastro', Origin::Dados, Impact::Alto, Urgency::CurtoPrazo, Frequency::Continua, GapStatus::EmExecucao, 3],
            ['Compras', 'Cotação não envia e-mail ao fornecedor', ['TOTVS Protheus'], 'Erro', Origin::Protheus, Impact::Medio, Urgency::CurtoPrazo, Frequency::Diaria, GapStatus::Bloqueado, 3],
            ['Compras', 'Workflow de aprovação de pedido por alçada', ['Fluig', 'TOTVS Protheus'], 'Melhoria', Origin::Fluig, Impact::Medio, Urgency::MedioPrazo, Frequency::Diaria, GapStatus::Backlog, null],
            ['Compras', 'Painel de pedidos em atraso', ['BI'], 'Relatório/BI', Origin::ProcessoInterno, Impact::Baixo, Urgency::MedioPrazo, Frequency::Semanal, GapStatus::Triagem, null],
            ['Comercial', 'Pedido de venda trava no faturamento', ['TOTVS Protheus'], 'Erro', Origin::Protheus, Impact::Alto, Urgency::Imediata, Frequency::Diaria, GapStatus::Homologacao, 3],
            ['Comercial', 'Tabela de preços sem vigência', ['TOTVS Protheus'], 'Dados/Cadastro', Origin::Dados, Impact::Medio, Urgency::CurtoPrazo, Frequency::Eventual, GapStatus::Concluido, 2],
            ['Comercial', 'Relatório de comissão de vendedores', ['TOTVS Protheus', 'BI'], 'Relatório/BI', Origin::ProcessoInterno, Impact::Baixo, Urgency::SemUrgencia, Frequency::Eventual, GapStatus::Levantado, null],
            ['Logística', 'Integração PIMS x Protheus falhando na baixa de estoque', ['PIMS', 'TOTVS Protheus', 'Integrações'], 'Integração', Origin::Pims, Impact::Critico, Urgency::Imediata, Frequency::Diaria, GapStatus::EmExecucao, 3],
            ['Logística', 'Etiqueta de expedição sem código de barras', ['Sistemas internos'], 'Sistema', Origin::Outro, Impact::Medio, Urgency::CurtoPrazo, Frequency::Diaria, GapStatus::Planejado, 4],
            ['Logística', 'Inventário rotativo sem coletor', ['TOTVS Protheus'], 'Melhoria', Origin::ProcessoInterno, Impact::Medio, Urgency::MedioPrazo, Frequency::Semanal, GapStatus::Backlog, null],
            ['Logística', 'Lentidão na rotina de transferência entre filiais', ['TOTVS Protheus'], 'Performance', Origin::Infraestrutura, Impact::Alto, Urgency::CurtoPrazo, Frequency::Diaria, GapStatus::Concluido, 1],
            ['Operações', 'Apontamento de produção duplicado no PIMS', ['PIMS'], 'Erro', Origin::Pims, Impact::Alto, Urgency::CurtoPrazo, Frequency::Diaria, GapStatus::Concluido, 2],
            ['Operações', 'Integração TBDC sem reprocessamento', ['TBDC', 'Integrações'], 'Integração', Origin::Tbdc, Impact::Alto, Urgency::CurtoPrazo, Frequency::Semanal, GapStatus::Priorizado, 4],
            ['Operações', 'Dashboard de produtividade por frente', ['BI', 'PIMS'], 'Relatório/BI', Origin::Pims, Impact::Medio, Urgency::MedioPrazo, Frequency::Continua, GapStatus::Analise, null],
            ['Operações', 'Cadastro de equipamentos desatualizado', ['PIMS'], 'Dados/Cadastro', Origin::Dados, Impact::Medio, Urgency::MedioPrazo, Frequency::Eventual, GapStatus::Reaberto, 2],
            ['RH', 'Importação do ponto eletrônico com erro', ['TOTVS Protheus', 'Integrações'], 'Integração', Origin::Integracao, Impact::Alto, Urgency::Imediata, Frequency::Semanal, GapStatus::Homologacao, 3],
            ['RH', 'Portal de holerite para colaboradores', ['Sistemas internos'], 'Melhoria', Origin::Usuario, Impact::Baixo, Urgency::SemUrgencia, Frequency::Continua, GapStatus::Levantado, null],
            ['RH', 'Acesso indevido a dados de folha', ['TOTVS Protheus'], 'Segurança', Origin::Protheus, Impact::Critico, Urgency::Imediata, Frequency::Unica, GapStatus::Concluido, 1],
            ['PCM', 'Ordem de manutenção sem vínculo com o ativo', ['TOTVS Protheus'], 'Processo', Origin::Protheus, Impact::Medio, Urgency::MedioPrazo, Frequency::Diaria, GapStatus::Triagem, null],
            ['PCM', 'Indicador MTBF/MTTR no BI', ['BI'], 'Relatório/BI', Origin::ProcessoInterno, Impact::Medio, Urgency::MedioPrazo, Frequency::Continua, GapStatus::Backlog, 4],
            ['PCM', 'Plano de manutenção preventiva não gera OS', ['TOTVS Protheus'], 'Erro', Origin::Protheus, Impact::Alto, Urgency::CurtoPrazo, Frequency::Semanal, GapStatus::Cancelado, null],
            ['Contabilidade', 'Lançamentos contábeis sem centro de custo', ['TOTVS Protheus'], 'Dados/Cadastro', Origin::Dados, Impact::Alto, Urgency::CurtoPrazo, Frequency::Diaria, GapStatus::EmExecucao, 3],
            ['Contabilidade', 'Fechamento mensal demora 5 dias', ['TOTVS Protheus', 'BI'], 'Processo', Origin::ProcessoInterno, Impact::Alto, Urgency::MedioPrazo, Frequency::Eventual, GapStatus::Analise, null],
            ['Contabilidade', 'Rateio de despesas manual em planilha', ['TOTVS Protheus'], 'Melhoria', Origin::ProcessoInterno, Impact::Medio, Urgency::MedioPrazo, Frequency::Semanal, GapStatus::Levantado, null],
        ];

        foreach ($rows as $i => [$sectorName, $title, $systemNames, $categoryName, $origin, $impact, $urgency, $frequency, $status, $sprintNumber]) {
            $sector = Sector::where('name', $sectorName)->first();
            $priority = Priority::suggest($impact, $urgency);
            $itOwner = $ti[$i % $ti->count()];
            $identified = now()->subDays(150 - $i * 4);
            $needsOwner = $status->requiresItOwner() || in_array($status, [GapStatus::Bloqueado, GapStatus::Reaberto, GapStatus::Analise], true);

            $gap = Gap::create([
                'code' => sprintf('GAP-%04d', ++$counter['GAP']),
                'type' => GapType::Gap,
                'title' => $title,
                'description' => "Levantado em reunião com o setor {$sectorName}. {$title}. O problema gera retrabalho e impacta indicadores do setor.",
                'sector_id' => $sector->id,
                'process' => "Processo de {$sectorName}",
                'category_id' => Category::where('name', $categoryName)->value('id'),
                'origin' => $origin,
                'impact' => $impact,
                'urgency' => $urgency,
                'priority' => $priority,
                'frequency' => $frequency,
                'critical_justification' => ($impact === Impact::Critico || $priority === Priority::Critica)
                    ? 'Impacta diretamente a operação/obrigações legais; risco de multa ou parada.'
                    : null,
                'sector_owner_id' => $sector->leader_id,
                'it_owner_id' => $needsOwner ? $itOwner->id : null,
                'identified_at' => $identified->toDateString(),
                // Alguns prazos já vencidos para o indicador de atraso ter conteúdo.
                'due_date' => $identified->copy()->addDays($i % 4 === 0 ? 30 : 160)->toDateString(),
                'sprint_id' => $sprintNumber ? $sprints[$sprintNumber]->id : null,
                'status' => $status->value,
                'created_by' => $sector->leader_id,
            ]);
            // created_at retroativo para o gráfico de evolução.
            $gap->timestamps = false;
            $gap->forceFill(['created_at' => $identified, 'updated_at' => $identified])->saveQuietly();
            $gap->timestamps = true;

            $gap->systems()->sync(System::whereIn('name', $systemNames)->pluck('id'));
            $gap->meetings()->sync(Meeting::where('sector_id', $sector->id)->where('status', 'realizada')->pluck('id'));

            $this->enrich($gap, $status, $itOwner, $sector);
        }

        // Incidentes críticos (fluxo Kanban próprio)
        $incidents = [
            ['Logística', 'Faturamento parado: SEFAZ fora do ar para a filial 02', IncidentStatus::EmAtendimento],
            ['Financeiro', 'Erro ao gerar remessa CNAB do dia', IncidentStatus::Novo],
            ['Operações', 'PIMS sem comunicação com o banco de dados', IncidentStatus::Encerrado],
        ];
        foreach ($incidents as $i => [$sectorName, $title, $status]) {
            $sector = Sector::where('name', $sectorName)->first();
            Gap::create([
                'code' => sprintf('INC-%04d', ++$counter['INC']),
                'type' => GapType::Incidente,
                'title' => $title,
                'description' => "{$title}. Operação impactada.",
                'sector_id' => $sector->id,
                'impact' => Impact::Critico,
                'urgency' => Urgency::Imediata,
                'priority' => Priority::Critica,
                'critical_justification' => 'Impede a operação do setor.',
                'origin' => Origin::Infraestrutura,
                'sector_owner_id' => $sector->leader_id,
                'it_owner_id' => $ti[$i % $ti->count()]->id,
                'identified_at' => now()->subDays($i)->toDateString(),
                'due_date' => now()->addDay()->toDateString(),
                'status' => $status->value,
                'solution' => $status === IncidentStatus::Encerrado ? 'Serviço de banco reiniciado e monitoramento ajustado.' : null,
                'completed_at' => $status === IncidentStatus::Encerrado ? now()->subHours(5) : null,
                'completed_by' => $status === IncidentStatus::Encerrado ? $ti[0]->id : null,
                'created_by' => $sector->leader_id,
            ]);
        }

        Auth::forgetUser();
    }

    /** Adiciona comentários, tarefas e homologações coerentes com o status. */
    private function enrich(Gap $gap, GapStatus $status, User $itOwner, Sector $sector): void
    {
        $leader = User::find($sector->leader_id);

        if ($status !== GapStatus::Levantado) {
            $gap->comments()->create(['user_id' => $itOwner->id, 'body' => 'Realizada análise do processo. Identificado ponto de falha na rotina; seguimos com o levantamento técnico.']);
        }

        if (in_array($status, [GapStatus::EmExecucao, GapStatus::Homologacao, GapStatus::Concluido, GapStatus::Bloqueado, GapStatus::Planejado], true)) {
            $gap->tasks()->createMany([
                ['title' => 'Analisar causa raiz', 'assignee_id' => $itOwner->id, 'status' => TaskStatus::Concluida],
                ['title' => 'Desenvolver/ajustar rotina', 'assignee_id' => $itOwner->id,
                    'status' => in_array($status, [GapStatus::Planejado, GapStatus::Bloqueado], true) ? TaskStatus::AFazer : TaskStatus::Concluida],
                ['title' => 'Testar em base de homologação', 'assignee_id' => $itOwner->id,
                    'status' => $status === GapStatus::EmExecucao ? TaskStatus::EmAndamento : ($status === GapStatus::Concluido || $status === GapStatus::Homologacao ? TaskStatus::Concluida : TaskStatus::AFazer)],
            ]);
        }

        if ($status === GapStatus::Bloqueado) {
            $gap->comments()->create(['user_id' => $itOwner->id, 'body' => '[Status: Bloqueado] Aguardando liberação de acesso do fornecedor (TOTVS).']);
        }

        if (in_array($status, [GapStatus::Homologacao, GapStatus::Concluido, GapStatus::Reaberto], true)) {
            $gap->forceFill([
                'solution' => 'Ajustada a rotina e corrigidos os parâmetros envolvidos. Executado reprocessamento dos registros afetados.',
                'solution_evidence' => 'Prints dos testes na base de homologação e log de reprocessamento anexados.',
            ])->saveQuietly();

            $homologation = $gap->homologations()->create([
                'submitted_by' => $itOwner->id,
                'submitted_at' => now()->subDays(10),
            ]);

            if ($status === GapStatus::Concluido) {
                $homologation->update([
                    'tested_by' => $leader?->id, 'tested_at' => now()->subDays(8),
                    'result' => HomologationResult::Aprovado, 'test_result' => 'Testado com 10 documentos reais, todos corretos.',
                ]);
                $gap->forceFill(['completed_at' => now()->subDays(8 + $gap->id % 40), 'completed_by' => $itOwner->id])->saveQuietly();
                $gap->comments()->create(['user_id' => $leader?->id, 'body' => '[Homologação APROVADA] Confirmado que o problema foi resolvido.']);
            }

            if ($status === GapStatus::Reaberto) {
                $homologation->update([
                    'tested_by' => $leader?->id, 'tested_at' => now()->subDays(8),
                    'result' => HomologationResult::Reprovado, 'test_result' => 'O problema continua ocorrendo para equipamentos novos.',
                ]);
                $gap->comments()->create(['user_id' => $leader?->id, 'body' => '[Homologação REPROVADA] Confirmado que o problema continua.']);
            }
        }
    }
}
