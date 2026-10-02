<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ordem importa: cadastros base -> usuários -> reuniões/sprints -> gaps.
     */
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,   // setores, sistemas, categorias
            UserSeeder::class,      // admin, TI, líderes e consulta
            SprintSeeder::class,
            MeetingSeeder::class,
            GapSeeder::class,       // gaps, comentários, tarefas, homologações
        ]);
    }
}
