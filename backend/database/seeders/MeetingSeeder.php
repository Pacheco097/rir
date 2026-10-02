<?php

namespace Database\Seeders;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        $tiUsers = User::where('role', 'ti')->pluck('id')->all();
        $sectors = Sector::where('name', '!=', 'TI')->get();

        foreach ($sectors as $index => $sector) {
            // Reunião já realizada (com ata) ...
            $past = Meeting::create([
                'title' => "Levantamento de gaps - {$sector->name}",
                'sector_id' => $sector->id,
                'leader_id' => $sector->leader_id,
                'it_owner_id' => $tiUsers[$index % count($tiUsers)],
                'date' => now()->subDays(20 - $index)->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:30',
                'objective' => "Mapear as principais dificuldades do setor {$sector->name} nos sistemas.",
                'status' => MeetingStatus::Realizada,
                'agenda' => [
                    'objetivo' => 'Entender as dores do setor e transformar em backlog.',
                    'processo_atual' => "Fluxo atual do setor {$sector->name} no Protheus e planilhas auxiliares.",
                    'dificuldades' => 'Retrabalho manual, lentidão e falhas de integração.',
                    'gaps' => 'Ver GAPs vinculados a esta reunião.',
                    'impactos' => 'Atrasos no fechamento e risco de inconsistência.',
                    'melhorias' => 'Automatizar conferências e melhorar relatórios.',
                    'priorizacao' => 'Priorizar itens que impactam o fechamento mensal.',
                    'proximos_passos' => 'TI analisa e retorna com estimativas em 15 dias.',
                ],
                'minutes' => "Reunião realizada com o líder do setor {$sector->name}. Foram levantados os principais gaps, priorizados por impacto no fechamento.",
                'summary' => 'Levantamento inicial concluído.',
                'decisions' => 'Itens críticos entram na próxima sprint.',
                'next_steps' => 'Validar estimativas e agendar retorno.',
            ]);
            $past->participants()->sync(array_filter([$sector->leader_id, $past->it_owner_id]));

            // ... e uma reunião futura de acompanhamento.
            $next = Meeting::create([
                'title' => "Acompanhamento de gaps - {$sector->name}",
                'sector_id' => $sector->id,
                'leader_id' => $sector->leader_id,
                'it_owner_id' => $past->it_owner_id,
                'date' => now()->addDays($index + 1)->toDateString(),
                'start_time' => sprintf('%02d:00', 8 + ($index % 8)),
                'end_time' => sprintf('%02d:00', 9 + ($index % 8)),
                'objective' => 'Apresentar andamento dos GAPs e validar prioridades.',
                'status' => $index % 3 === 0 ? MeetingStatus::Confirmada : MeetingStatus::Agendada,
                'previous_meeting_id' => $past->id,
            ]);
            $next->participants()->sync(array_filter([$sector->leader_id, $next->it_owner_id]));
        }
    }
}
