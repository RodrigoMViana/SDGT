<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Mail\NotificacaoMail;
use App\Models\Auditoria;
use App\Models\Notificacao;
use App\Models\Pergunta;
use App\Models\Questionario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** EP03 — Gestão dos Questionários · EP12 — Auditoria (publicação/prorrogação) · EP10 — e-mail */
class GestaoQuestionariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        $this->actingAs($this->admin());
    }

    private function rascunho(array $attrs = []): Questionario
    {
        $this->post('/admin/questionarios', $attrs + [
            'titulo' => 'Diagnóstico 2027', 'ciclo' => '2027', 'data_inicio' => today()->toDateString(),
            'data_fim' => today()->addDays(14)->toDateString(), 'abrangencia' => 'todos',
        ])->assertRedirect();

        return Questionario::latest('id')->first();
    }

    public function test_hu0302_cadastrar_calcula_prazo_de_assinatura_em_dia_util(): void
    {
        $q = $this->rascunho(['data_inicio' => '2026-11-01', 'data_fim' => '2026-11-13']);
        $this->assertSame('rascunho', $q->status);
        $this->assertSame('2026-11-16', $q->data_limite_assinatura->toDateString()); // sexta + 1 dia útil = segunda
    }

    public function test_hu0302_validacao_de_datas_e_abrangencia(): void
    {
        $this->post('/admin/questionarios', ['titulo' => 'X', 'ciclo' => '1', 'data_inicio' => '2026-12-10', 'data_fim' => '2026-12-01', 'abrangencia' => 'todos'])
            ->assertSessionHasErrors('data_fim');
        $this->post('/admin/questionarios', ['titulo' => 'X', 'ciclo' => '1', 'data_inicio' => '2026-12-01', 'data_fim' => '2026-12-10', 'abrangencia' => 'selecionados'])
            ->assertSessionHasErrors('municipios');
    }

    public function test_hu0301_e_0303_listar_e_visualizar(): void
    {
        $q = $this->questionarioAberto();
        $this->get('/admin/questionarios')->assertOk()->assertSee($q->titulo)->assertSee('Em preenchimento');
        $this->get("/admin/questionarios/{$q->id}")->assertOk()->assertSee('LT01')->assertSee('Prorrogar prazo');
    }

    public function test_hu0304_baixar_questionario_em_pdf(): void
    {
        $q = $this->questionarioAberto();
        $r = $this->get("/admin/questionarios/{$q->id}/pdf")->assertOk();
        $this->assertSame('application/pdf', $r->headers->get('content-type'));
    }

    public function test_hu0310_associar_perguntas_exige_pergunta_condicionante(): void
    {
        $q = $this->rascunho();
        $lt02 = $this->pergunta('LT02');
        $this->put("/admin/questionarios/{$q->id}/perguntas", ['perguntas' => [$lt02->id]])->assertSessionHas('erro');

        $ids = Pergunta::whereIn('codigo', ['LT01', 'LT02', 'RH01'])->pluck('id')->all();
        $this->put("/admin/questionarios/{$q->id}/perguntas", ['perguntas' => $ids])->assertRedirect();
        $this->assertSame(['LT01', 'LT02', 'RH01'], $q->perguntas()->pluck('codigo')->all());
    }

    public function test_hu0305_atualizar_somente_em_rascunho(): void
    {
        $q = $this->rascunho();
        $this->put("/admin/questionarios/{$q->id}", ['titulo' => 'Novo título', 'ciclo' => '2027', 'data_inicio' => today()->toDateString(),
            'data_fim' => today()->addDays(5)->toDateString(), 'abrangencia' => 'todos'])->assertRedirect();
        $this->assertSame('Novo título', $q->fresh()->titulo);

        $aberto = $this->questionarioAberto();
        $this->get("/admin/questionarios/{$aberto->id}/edit")->assertRedirect("/admin/questionarios/{$aberto->id}");
        $this->put("/admin/questionarios/{$aberto->id}", ['titulo' => 'X', 'ciclo' => '1', 'data_inicio' => '2026-01-01', 'data_fim' => '2026-01-02', 'abrangencia' => 'todos'])
            ->assertForbidden();
    }

    public function test_hu0306_publicar_valida_audita_e_notifica_municipios(): void
    {
        Mail::fake();
        $palmas = $this->municipio();
        $resp = $this->usuario(Perfil::Responsavel, $palmas);
        $gestor = $this->usuario(Perfil::Gestor, $palmas);

        $q = $this->rascunho();
        $this->post("/admin/questionarios/{$q->id}/publicar")->assertSessionHasErrors('publicacao'); // sem perguntas

        $this->put("/admin/questionarios/{$q->id}/perguntas", ['perguntas' => Pergunta::pluck('id')->all()]);
        $this->post("/admin/questionarios/{$q->id}/publicar")->assertSessionHas('sucesso');

        $q->refresh();
        $this->assertSame('publicado', $q->status);
        $this->assertNotNull($q->publicado_em);
        $this->assertDatabaseHas('auditorias', ['acao' => 'publicacao', 'entidade_id' => $q->id, 'user_id' => $this->admin()->id]);
        foreach ([$resp, $gestor] as $u) {
            $this->assertDatabaseHas('notificacoes', ['user_id' => $u->id, 'tipo' => 'inicio_preenchimento', 'questionario_id' => $q->id]);
        }
        Mail::assertQueued(NotificacaoMail::class, 2);

        $this->post("/admin/questionarios/{$q->id}/publicar")->assertSessionHasErrors('status'); // já publicado
    }

    public function test_hu0306_publicacao_futura_nao_notifica_antes_do_inicio(): void
    {
        $this->usuario(Perfil::Responsavel, $this->municipio());
        $q = $this->rascunho(['data_inicio' => today()->addDays(5)->toDateString(), 'data_fim' => today()->addDays(20)->toDateString()]);
        $this->put("/admin/questionarios/{$q->id}/perguntas", ['perguntas' => Pergunta::pluck('id')->all()]);
        $this->post("/admin/questionarios/{$q->id}/publicar");
        $this->assertSame('agendado', $q->fresh()->situacao());
        $this->assertSame(0, Notificacao::count());
    }

    public function test_hu0309_prorrogar_prazo_com_auditoria_e_notificacao(): void
    {
        Mail::fake();
        $resp = $this->usuario(Perfil::Responsavel, $this->municipio());
        $q = $this->questionarioAberto();
        $fimAntigo = $q->data_fim->toDateString();

        $this->post("/admin/questionarios/{$q->id}/prorrogar", ['data_fim' => $q->data_fim->copy()->subDay()->toDateString(),
            'data_limite_assinatura' => $q->data_fim->copy()->addDays(3)->toDateString(), 'justificativa' => 'Justificativa válida'])
            ->assertSessionHasErrors('data_fim');

        $novo = $q->data_fim->copy()->addDays(5);
        $this->post("/admin/questionarios/{$q->id}/prorrogar", ['data_fim' => $novo->toDateString(),
            'data_limite_assinatura' => $novo->copy()->addDay()->toDateString(), 'justificativa' => 'Pedido da associação de municípios'])
            ->assertSessionHas('sucesso');

        $this->assertSame($novo->toDateString(), $q->fresh()->data_fim->toDateString());
        $a = Auditoria::where('acao', 'prorrogacao')->firstOrFail();
        $this->assertSame($fimAntigo, $a->dados_anteriores['data_fim']);
        $this->assertSame($novo->toDateString(), $a->dados_novos['data_fim']);
        $this->assertSame('Pedido da associação de municípios', $a->justificativa);
        $this->assertDatabaseHas('notificacoes', ['user_id' => $resp->id, 'tipo' => 'prorrogacao']);
    }

    public function test_hu0309_prorrogacao_reabre_questionario_encerrado(): void
    {
        $q = $this->questionarioAberto(['status' => 'encerrado', 'data_inicio' => today()->subDays(30), 'data_fim' => today()->subDays(15), 'data_limite_assinatura' => today()->subDays(14)]);
        $this->post("/admin/questionarios/{$q->id}/prorrogar", ['data_fim' => today()->addDays(3)->toDateString(),
            'data_limite_assinatura' => today()->addDays(4)->toDateString(), 'justificativa' => 'Reabertura excepcional'])->assertSessionHas('sucesso');
        $this->assertSame('aberto', $q->fresh()->situacao());
    }

    public function test_hu0308_duplicar_questionario(): void
    {
        $q = $this->questionarioAberto(['abrangencia' => 'selecionados']);
        $q->municipios()->attach($this->municipio()->id);
        $this->post("/admin/questionarios/{$q->id}/duplicar")->assertRedirect();
        $copia = Questionario::latest('id')->first();
        $this->assertSame('rascunho', $copia->status);
        $this->assertSame($q->id, $copia->origem_id);
        $this->assertStringContainsString('(cópia)', $copia->titulo);
        $this->assertSame($q->perguntas()->count(), $copia->perguntas()->count());
        $this->assertSame(['Palmas'], $copia->municipios()->pluck('nome')->all());
    }

    public function test_hu0307_excluir_somente_rascunho(): void
    {
        $aberto = $this->questionarioAberto();
        $this->delete("/admin/questionarios/{$aberto->id}")->assertSessionHas('erro');
        $this->assertDatabaseHas('questionarios', ['id' => $aberto->id]);

        $r = $this->rascunho();
        $this->delete("/admin/questionarios/{$r->id}")->assertRedirect('/admin/questionarios');
        $this->assertDatabaseMissing('questionarios', ['id' => $r->id]);
    }

    public function test_questionario_personalizado_so_aparece_para_municipios_selecionados(): void
    {
        $q = $this->questionarioAberto(['abrangencia' => 'selecionados', 'titulo' => 'Questionário personalizado']);
        $q->municipios()->attach($this->municipio('Gurupi')->id);
        $this->actingAs($this->usuario(Perfil::Responsavel, $this->municipio('Gurupi')))
            ->get('/municipio/questionarios')->assertSee('Questionário personalizado');
        $this->actingAs($this->usuario(Perfil::Responsavel, $this->municipio('Palmas')))
            ->get('/municipio/questionarios')->assertDontSee('Questionário personalizado');
    }
}
