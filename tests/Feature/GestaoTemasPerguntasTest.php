<?php

namespace Tests\Feature;

use App\Models\OpcaoResposta;
use App\Models\Pergunta;
use App\Models\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** EP02 — Temas de Avaliação · EP04 — Perguntas e Respostas */
class GestaoTemasPerguntasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        $this->actingAs($this->admin());
    }

    public function test_hu0201_listar_temas(): void
    {
        $this->get('/admin/temas')->assertOk()->assertSee('Legislação Tributária')->assertSee('Governança da Transição');
        $this->get('/admin/temas?busca=Recursos')->assertOk()->assertSee('Recursos Humanos')->assertDontSee('Legislação Tributária');
    }

    public function test_hu0202_e_0203_cadastrar_e_atualizar_tema(): void
    {
        $this->post('/admin/temas', ['nome' => 'Transparência Fiscal', 'ordem' => 7, 'ativo' => 1])->assertRedirect('/admin/temas');
        $t = Tema::where('nome', 'Transparência Fiscal')->firstOrFail();
        $this->put("/admin/temas/{$t->id}", ['nome' => 'Transparência Fiscal e Ativa', 'ordem' => 8])->assertRedirect();
        $this->assertDatabaseHas('temas', ['id' => $t->id, 'nome' => 'Transparência Fiscal e Ativa', 'ativo' => false]);
        $this->post('/admin/temas', ['nome' => 'Recursos Humanos', 'ordem' => 1])->assertSessionHasErrors('nome');
    }

    public function test_hu0204_ativar_inativar_tema(): void
    {
        $t = Tema::first();
        $this->patch("/admin/temas/{$t->id}/status")->assertRedirect();
        $this->assertFalse($t->fresh()->ativo);
        $this->patch("/admin/temas/{$t->id}/status");
        $this->assertTrue($t->fresh()->ativo);
    }

    public function test_hu0205_excluir_tema_somente_sem_perguntas(): void
    {
        $comPerguntas = Tema::where('nome', 'Recursos Humanos')->first();
        $this->delete("/admin/temas/{$comPerguntas->id}")->assertSessionHas('erro');
        $this->assertDatabaseHas('temas', ['id' => $comPerguntas->id]);

        $vazio = Tema::create(['nome' => 'Tema vazio', 'ordem' => 9]);
        $this->delete("/admin/temas/{$vazio->id}")->assertSessionHas('sucesso');
        $this->assertDatabaseMissing('temas', ['id' => $vazio->id]);
    }

    public function test_hu0401_listar_perguntas_com_filtros(): void
    {
        $this->get('/admin/perguntas')->assertOk()->assertSee('LT01');
        $tema = Tema::where('nome', 'Recursos Humanos')->first();
        $this->get('/admin/perguntas?tema_id='.$tema->id)->assertSee('RH02')->assertDontSee('LT01');
        $this->get('/admin/perguntas?tipo=multipla')->assertSee('TI02')->assertDontSee('RH02');
    }

    public function test_hu0402_a_0404_crud_de_pergunta(): void
    {
        $tema = Tema::first();
        $this->post('/admin/perguntas', ['tema_id' => $tema->id, 'codigo' => 'LT99', 'enunciado' => 'Nova pergunta?', 'tipo' => 'unica', 'peso' => 2, 'obrigatoria' => 1, 'ativo' => 1])
            ->assertRedirect();
        $p = Pergunta::where('codigo', 'LT99')->firstOrFail();
        $this->assertTrue($p->obrigatoria);

        $this->put("/admin/perguntas/{$p->id}", ['tema_id' => $tema->id, 'codigo' => 'LT99', 'enunciado' => 'Pergunta alterada?', 'tipo' => 'texto', 'peso' => 0])
            ->assertRedirect();
        $this->assertSame('Pergunta alterada?', $p->fresh()->enunciado);

        $this->delete("/admin/perguntas/{$p->id}")->assertRedirect('/admin/perguntas');
        $this->assertDatabaseMissing('perguntas', ['id' => $p->id]);
    }

    public function test_hu0404_nao_exclui_pergunta_associada_a_questionario(): void
    {
        $this->questionarioAberto();
        $p = $this->pergunta('RH03');
        $this->delete("/admin/perguntas/{$p->id}")->assertSessionHas('erro');
        $this->assertDatabaseHas('perguntas', ['id' => $p->id]);
    }

    public function test_hu0406_a_0409_crud_de_respostas(): void
    {
        $p = Pergunta::create(['tema_id' => Tema::first()->id, 'codigo' => 'X1', 'enunciado' => 'Teste', 'tipo' => 'unica', 'peso' => 1]);
        $this->get("/admin/perguntas/{$p->id}")->assertOk()->assertSee('Nenhuma resposta cadastrada');

        $this->post("/admin/perguntas/{$p->id}/opcoes", ['texto' => 'Sim', 'pontuacao' => 10, 'solicita_evidencia' => 1])->assertRedirect();
        $this->post("/admin/perguntas/{$p->id}/opcoes", ['texto' => 'Não', 'pontuacao' => 0])->assertRedirect();
        $this->assertCount(2, $p->opcoes()->get());
        $sim = $p->opcoes()->where('texto', 'Sim')->first();
        $this->assertTrue($sim->solicita_evidencia);

        $this->put("/admin/opcoes/{$sim->id}", ['texto' => 'Sim, integralmente', 'pontuacao' => 8])->assertRedirect();
        $this->assertSame('Sim, integralmente', $sim->fresh()->texto);
        $this->assertFalse($sim->fresh()->solicita_evidencia);

        $this->delete("/admin/opcoes/{$sim->id}")->assertRedirect();
        $this->assertDatabaseMissing('opcoes_resposta', ['id' => $sim->id]);
        $this->get("/admin/perguntas/{$p->id}")->assertSee('Não');
    }

    public function test_hu0405_configurar_e_remover_dependencia(): void
    {
        $gt03 = $this->pergunta('GT03');
        $gt01 = $this->pergunta('GT01');
        $sim = $this->opcao('GT01', 'Sim');

        $this->put("/admin/perguntas/{$gt03->id}/dependencia", ['pergunta_pai_id' => $gt01->id, 'opcoes' => [$sim]])->assertSessionHas('sucesso');
        $this->assertSame($gt01->id, $gt03->fresh()->pergunta_pai_id);
        $this->assertEquals([$sim], $gt03->fresh()->opcoesHabilitadoras->pluck('id')->all());

        // opção de outra pergunta é rejeitada
        $this->put("/admin/perguntas/{$gt03->id}/dependencia", ['pergunta_pai_id' => $gt01->id, 'opcoes' => [$this->opcao('RH04', 'Sim')]])->assertSessionHas('erro');

        $this->put("/admin/perguntas/{$gt03->id}/dependencia", ['pergunta_pai_id' => ''])->assertSessionHas('sucesso');
        $this->assertNull($gt03->fresh()->pergunta_pai_id);
    }

    public function test_hu0405_impede_dependencia_circular(): void
    {
        $gt01 = $this->pergunta('GT01');
        $gt02 = $this->pergunta('GT02'); // GT02 já depende de GT01
        $this->put("/admin/perguntas/{$gt01->id}/dependencia", ['pergunta_pai_id' => $gt02->id, 'opcoes' => [$gt02->opcoes->first()->id]])
            ->assertSessionHas('erro', 'Dependência circular não permitida.');
    }

    public function test_pergunta_em_questionario_publicado_fica_bloqueada(): void
    {
        $this->questionarioAberto();
        $p = $this->pergunta('LT03');
        $this->put("/admin/perguntas/{$p->id}", ['tema_id' => $p->tema_id, 'codigo' => 'LT03', 'enunciado' => 'ALTERADO', 'tipo' => 'unica', 'peso' => 1, 'ajuda' => 'Nova ajuda'])
            ->assertRedirect();
        $this->assertNotSame('ALTERADO', $p->fresh()->enunciado);
        $this->assertSame('Nova ajuda', $p->fresh()->ajuda);

        $o = $p->opcoes->first();
        $this->put("/admin/opcoes/{$o->id}", ['texto' => 'X', 'pontuacao' => 1])->assertSessionHas('erro');
        $this->post("/admin/perguntas/{$p->id}/opcoes", ['texto' => 'X', 'pontuacao' => 1])->assertSessionHas('erro');
        $this->assertSame(OpcaoResposta::where('pergunta_id', $p->id)->count(), $p->opcoes->count());
    }
}
