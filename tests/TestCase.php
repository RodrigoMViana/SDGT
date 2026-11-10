<?php

namespace Tests;

use App\Enums\Perfil;
use App\Models\Municipio;
use App\Models\Pergunta;
use App\Models\Questionario;
use App\Models\User;
use Database\Seeders\BancoPerguntasSeeder;
use Database\Seeders\EstruturaSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Carrega relatorias, 139 municípios, temas, banco de perguntas e o admin inicial. */
    protected function seedBase(): void
    {
        $this->seed([EstruturaSeeder::class, BancoPerguntasSeeder::class]);
    }

    protected function admin(): User
    {
        return User::where('perfil', 'administrador')->first() ?? User::factory()->create();
    }

    protected function usuario(Perfil $perfil, ?Municipio $municipio = null): User
    {
        return User::factory()->perfil($perfil, $municipio?->id)->create();
    }

    protected function municipio(string $nome = 'Palmas'): Municipio
    {
        return Municipio::where('nome', $nome)->firstOrFail();
    }

    /** Questionário com todas as perguntas do banco, já publicado e aberto (hoje dentro do período). */
    protected function questionarioAberto(array $attrs = []): Questionario
    {
        $q = Questionario::create($attrs + [
            'titulo' => 'Diagnóstico de teste', 'ciclo' => '2026', 'status' => 'publicado', 'abrangencia' => 'todos',
            'data_inicio' => today()->subDays(2), 'data_fim' => today()->addDays(10), 'data_limite_assinatura' => today()->addDays(11),
            'publicado_em' => now(),
        ]);
        foreach (Pergunta::orderBy('id')->get()->values() as $i => $p) {
            $q->perguntas()->attach($p->id, ['ordem' => $i + 1]);
        }

        return $q;
    }

    protected function pergunta(string $codigo): Pergunta
    {
        return Pergunta::where('codigo', $codigo)->with('opcoes')->firstOrFail();
    }

    /** ID da opção de uma pergunta pelo texto (ou prefixo do texto). */
    protected function opcao(string $codigo, string $texto): int
    {
        return $this->pergunta($codigo)->opcoes->first(fn ($o) => str_starts_with($o->texto, $texto))->id;
    }
}
