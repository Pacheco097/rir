<?php

namespace Database\Seeders;

use App\Enums\SprintStatus;
use App\Models\Sprint;
use Illuminate\Database\Seeder;

class SprintSeeder extends Seeder
{
    public function run(): void
    {
        // Sprints de 2 semanas: duas encerradas, a atual e a próxima.
        $start = now()->startOfWeek()->subWeeks(4);
        $sprints = [
            [1, 'Estabilização fiscal', 'Corrigir rejeições de NF-e e apuração.', SprintStatus::Encerrada],
            [2, 'Integrações PIMS', 'Reduzir falhas na integração PIMS x Protheus.', SprintStatus::Encerrada],
            [3, 'Cadastros e compras', 'Saneamento de cadastros e melhorias em compras.', SprintStatus::EmAndamento],
            [4, 'Relatórios gerenciais', 'Entregar relatórios priorizados pelos setores.', SprintStatus::Planejamento],
        ];

        foreach ($sprints as $i => [$number, $name, $goal, $status]) {
            Sprint::updateOrCreate(['number' => $number], [
                'name' => $name,
                'goal' => $goal,
                'start_date' => $start->copy()->addWeeks($i * 2),
                'end_date' => $start->copy()->addWeeks($i * 2)->addDays(11),
                'status' => $status,
            ]);
        }
    }
}
