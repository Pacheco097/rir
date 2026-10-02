<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Sector;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Usuários fictícios. Senha de todos: "password".
 * NUNCA rode este seeder em produção.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $ti = Sector::where('name', 'TI')->first();

        $make = fn (string $name, string $email, Role $role, ?Sector $sector = null, ?string $job = null) => User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password', 'role' => $role, 'sector_id' => $sector?->id, 'job_title' => $job, 'active' => true],
        );

        $make('Administrador', 'admin@empresa.com', Role::Admin, $ti, 'Coordenador de TI');
        $matheus = $make('Matheus Pacheco', 'matheus@empresa.com', Role::TI, $ti, 'Analista de Sistemas ERP');
        $carla = $make('Carla Mendes', 'carla@empresa.com', Role::TI, $ti, 'Analista de Integrações');
        $rafael = $make('Rafael Souza', 'rafael@empresa.com', Role::TI, $ti, 'Desenvolvedor');
        $make('Diretoria (Consulta)', 'diretoria@empresa.com', Role::Consulta, null, 'Diretor Administrativo');

        $leaders = [
            'Fiscal' => 'João Almeida', 'Financeiro' => 'Patrícia Lima', 'Compras' => 'Marcos Ribeiro',
            'Comercial' => 'Fernanda Costa', 'Logística' => 'Ricardo Gomes', 'Operações' => 'Ana Paula Rocha',
            'RH' => 'Juliana Martins', 'PCM' => 'Eduardo Nunes', 'Contabilidade' => 'Luciana Barros',
        ];
        foreach ($leaders as $sectorName => $name) {
            $sector = Sector::where('name', $sectorName)->first();
            $email = Str::slug(Str::before($name, ' ').' '.$sectorName, '.').'@empresa.com';
            $leader = $make($name, $email, Role::Lider, $sector, "Líder de {$sectorName}");
            $sector->update(['leader_id' => $leader->id]);
        }

        // Responsáveis técnicos por sistema.
        System::where('name', 'TOTVS Protheus')->update(['owner_id' => $matheus->id]);
        System::whereIn('name', ['Integrações', 'Fluig'])->update(['owner_id' => $carla->id]);
        System::whereIn('name', ['PIMS', 'TBDC', 'Sistemas internos', 'BI'])->update(['owner_id' => $rafael->id]);
        $ti->update(['leader_id' => User::where('email', 'admin@empresa.com')->value('id')]);
    }
}
