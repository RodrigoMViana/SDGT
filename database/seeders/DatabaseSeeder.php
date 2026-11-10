<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Base mínima de produção: relatorias, municípios (IBGE), temas, banco de perguntas sugerido
     * e o administrador inicial. Com SDGT_SEED_DEMO=true carrega também a base de demonstração.
     */
    public function run(): void
    {
        $this->call([
            EstruturaSeeder::class,
            BancoPerguntasSeeder::class,
        ]);

        if (filter_var(env('SDGT_SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoSeeder::class);
        }
    }
}
