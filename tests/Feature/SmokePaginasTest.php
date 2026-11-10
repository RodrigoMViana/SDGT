<?php

namespace Tests\Feature;

use App\Models\Pergunta;
use App\Models\Preenchimento;
use App\Models\Questionario;
use App\Models\User;
use Tests\TestCase;
use Tests\UsaBaseDemonstracao;

/** Percorre todas as telas de cada perfil com a base de demonstração (detecta erros de renderização). */
class SmokePaginasTest extends TestCase
{
    use UsaBaseDemonstracao;

    public function test_todas_as_paginas_renderizam_por_perfil(): void
    {
        $q = Questionario::where('ciclo', '2026')->first();
        $rasc = Questionario::where('status', 'rascunho')->first();
        $p = Pergunta::where('codigo', 'GT02')->first();
        $pr = Preenchimento::where('questionario_id', $q->id)->where('status', 'validado')->first();

        $paginas = [
            'administrador' => ['/tribunal/painel', '/tribunal/painel/comparar', '/tribunal/painel/relatorias', '/tribunal/painel/evolucao',
                '/tribunal/relatorios/resultados', "/tribunal/preenchimentos/{$pr->id}", '/admin/questionarios', "/admin/questionarios/{$q->id}",
                "/admin/questionarios/{$rasc->id}", "/admin/questionarios/{$rasc->id}/edit", "/admin/questionarios/{$rasc->id}/perguntas", '/admin/questionarios/create',
                '/admin/perguntas', "/admin/perguntas/{$p->id}", "/admin/perguntas/{$p->id}/edit", '/admin/perguntas/create',
                '/admin/temas', '/admin/temas/create', '/admin/auditoria', '/admin/usuarios', '/admin/usuarios/create', '/admin/municipios',
                '/materiais', '/admin/materiais/create', '/notificacoes'],
            'auditor' => ['/tribunal/painel', '/tribunal/relatorios/resultados', '/materiais', '/notificacoes'],
        ];
        foreach ($paginas as $perfil => $urls) {
            $u = User::where('perfil', $perfil)->first();
            foreach ($urls as $url) {
                $this->actingAs($u)->get($url)->assertOk();
            }
        }

        foreach (['Palmas', 'Araguaína', 'Gurupi', 'Porto Nacional', 'Paraíso do Tocantins'] as $nome) {
            $m = $this->municipio($nome);
            $prM = Preenchimento::where('questionario_id', $q->id)->where('municipio_id', $m->id)->first();
            foreach (['responsavel', 'gestor'] as $perfil) {
                $u = User::where('perfil', $perfil)->where('municipio_id', $m->id)->first();
                $this->actingAs($u)->get('/municipio/questionarios')->assertOk();
                $this->get("/municipio/questionarios/{$q->id}")->assertOk();
                $this->get('/notificacoes')->assertOk();
                if ($prM) {
                    $this->get("/municipio/preenchimentos/{$prM->id}")->assertOk();
                    if ($perfil === 'responsavel' && $prM->editavel()) {
                        $this->get("/municipio/preenchimentos/{$prM->id}/editar")->assertOk();
                        $this->get("/municipio/preenchimentos/{$prM->id}/revisar")->assertOk();
                    }
                }
            }
        }
        $this->get('/login')->assertRedirect(); // autenticado
    }
}
