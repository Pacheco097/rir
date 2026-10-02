<?php

namespace Database\Seeders;

use App\Enums\SystemType;
use App\Models\Category;
use App\Models\Sector;
use App\Models\System;
use Illuminate\Database\Seeder;

/** Dados de referência — este seeder também serve para produção. */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            'Fiscal' => 'Apuração de impostos, escrituração e obrigações acessórias.',
            'Financeiro' => 'Contas a pagar, a receber, tesouraria e conciliação.',
            'Compras' => 'Solicitações, cotações, pedidos e cadastro de fornecedores.',
            'Comercial' => 'Vendas, pedidos de clientes e faturamento.',
            'Logística' => 'Expedição, recebimento, estoque e transporte.',
            'Operações' => 'Operação agrícola/industrial e apontamentos.',
            'RH' => 'Folha de pagamento, ponto e benefícios.',
            'PCM' => 'Planejamento e controle da manutenção.',
            'TI' => 'Tecnologia da informação.',
            'Contabilidade' => 'Fechamento contábil e demonstrações.',
        ];
        foreach ($sectors as $name => $description) {
            Sector::updateOrCreate(['name' => $name], ['description' => $description, 'active' => true]);
        }

        $systems = [
            ['TOTVS Protheus', SystemType::Erp, 'ERP corporativo (Financeiro, Fiscal, Compras, Estoque, Faturamento).'],
            ['PIMS', SystemType::Operacional, 'Gestão da operação agrícola/industrial.'],
            ['TBDC', SystemType::Operacional, 'Sistema operacional de apoio.'],
            ['Fluig', SystemType::Workflow, 'Workflows e aprovações (BPM/ECM).'],
            ['Sistemas internos', SystemType::Interno, 'Aplicações desenvolvidas pelo TI.'],
            ['Integrações', SystemType::Integracao, 'Interfaces entre Protheus, PIMS, Fluig e terceiros.'],
            ['BI', SystemType::Bi, 'Painéis e relatórios gerenciais.'],
            ['Outros', SystemType::Outro, 'Demais sistemas.'],
        ];
        foreach ($systems as [$name, $type, $description]) {
            System::updateOrCreate(['name' => $name], ['type' => $type, 'description' => $description, 'active' => true]);
        }

        $categories = [
            'Sistema' => '#2563eb', 'Integração' => '#7c3aed', 'Dados/Cadastro' => '#0891b2',
            'Processo' => '#059669', 'Treinamento' => '#65a30d', 'Segurança' => '#dc2626',
            'Relatório/BI' => '#d97706', 'Performance' => '#db2777', 'Erro' => '#b91c1c', 'Melhoria' => '#4f46e5',
        ];
        foreach ($categories as $name => $color) {
            Category::updateOrCreate(['name' => $name], ['color' => $color, 'active' => true]);
        }
    }
}
