<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Models\Evidencia;
use App\Models\Pergunta;
use App\Models\Preenchimento;
use App\Models\Questionario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** EP05 — Preenchimento e Validação · EP06 — Evidências */
class PreenchimentoValidacaoTest extends TestCase
{
    use RefreshDatabase;

    private Questionario $q;

    private User $resp;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->seedBase();
        $this->q = $this->questionarioAberto();
        $m = $this->municipio('Palmas');
        $this->resp = $this->usuario(Perfil::Responsavel, $m);
        $this->gestor = $this->usuario(Perfil::Gestor, $m);
    }

    private function iniciar(): Preenchimento
    {
        $this->actingAs($this->resp)->post("/municipio/questionarios/{$this->q->id}/iniciar")->assertRedirect();

        return Preenchimento::firstOrFail();
    }

    /** Respostas completas com a melhor opção (sem pendências de evidência para opções que não solicitam). */
    private function respostasCompletas(): array
    {
        $r = [];
        foreach ($this->q->perguntas()->with('opcoes')->get() as $p) {
            $r[$p->id] = match ($p->tipo) {
                'unica' => ['opcao' => $p->opcoes->sortBy([['solicita_evidencia', 'asc'], ['pontuacao', 'desc']])->first()->id],
                'multipla' => ['opcoes' => $p->opcoes->where('pontuacao', '>', 0)->pluck('id')->all()],
                'numero' => ['valor' => '12'],
                'data' => ['valor' => '2025-03-01'],
                default => ['valor' => 'Observação'],
            };
        }

        return $r;
    }

    public function test_hu0501_listar_questionarios_do_municipio(): void
    {
        $this->actingAs($this->resp)->get('/municipio/questionarios')->assertOk()
            ->assertSee($this->q->titulo)->assertSee('Iniciar preenchimento');
        $this->actingAs($this->gestor)->get('/municipio/questionarios')->assertOk()->assertDontSee('Iniciar preenchimento');
    }

    public function test_apresentacao_inicial_exibe_normativos_e_perguntas(): void
    {
        $this->q->update(['apresentacao' => 'Conforme Portaria nº 123/2026']);
        $this->actingAs($this->resp)->get("/municipio/questionarios/{$this->q->id}")->assertOk()
            ->assertSee('Conforme Portaria nº 123/2026')->assertSee('LT02');
    }

    public function test_hu0502_e_0503_salvar_parcialmente_e_retomar(): void
    {
        $p = $this->iniciar();
        $lt01 = $this->pergunta('LT01');
        $rh01 = $this->pergunta('RH01');
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => [
            $lt01->id => ['opcao' => $this->opcao('LT01', 'Não')], $rh01->id => ['valor' => '7'],
        ]])->assertRedirect()->assertSessionHas('sucesso');

        $this->assertSame('em_preenchimento', $p->fresh()->status);
        $this->assertDatabaseHas('preenchimento_respostas', ['preenchimento_id' => $p->id, 'pergunta_id' => $rh01->id, 'valor_texto' => '7']);

        // retomar: a tela traz a resposta gravada
        $this->get("/municipio/preenchimentos/{$p->id}/editar")->assertOk()->assertSee('value="7"', false)->assertSeeText('2 de');
    }

    public function test_hu0502_numero_invalido_e_rejeitado(): void
    {
        $p = $this->iniciar();
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => [$this->pergunta('RH01')->id => ['valor' => 'abc']]])
            ->assertSessionHasErrors();
    }

    public function test_dependencia_descarta_resposta_de_pergunta_desabilitada(): void
    {
        $p = $this->iniciar();
        $lt01 = $this->pergunta('LT01');
        $lt02 = $this->pergunta('LT02');
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => [
            $lt01->id => ['opcao' => $this->opcao('LT01', 'Não')], $lt02->id => ['opcao' => $this->opcao('LT02', 'Sim')],
        ]]);
        $this->assertDatabaseHas('preenchimento_respostas', ['preenchimento_id' => $p->id, 'pergunta_id' => $lt02->id]);

        // muda LT01 para "Sim, integralmente" → LT02 deixa de se aplicar e sua resposta é removida
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => [$lt01->id => ['opcao' => $this->opcao('LT01', 'Sim, integralmente')]]]);
        $this->assertDatabaseMissing('preenchimento_respostas', ['preenchimento_id' => $p->id, 'pergunta_id' => $lt02->id]);
    }

    public function test_hu0504_finalizacao_bloqueada_por_pendencias(): void
    {
        $p = $this->iniciar();
        $this->get("/municipio/preenchimentos/{$p->id}/revisar")->assertOk()->assertSee('pendência');
        $this->post("/municipio/preenchimentos/{$p->id}/finalizar", ['declaracao' => 1])->assertSessionHasErrors('pendencias');
        $this->assertSame('em_preenchimento', $p->fresh()->status);
    }

    public function test_hu0601_evidencia_exigida_pela_resposta(): void
    {
        $p = $this->iniciar();
        $dados = $this->respostasCompletas();
        $gt01 = $this->pergunta('GT01');
        $dados[$gt01->id] = ['opcao' => $this->opcao('GT01', 'Sim')]; // "Sim" solicita evidência
        $dados[$this->pergunta('GT02')->id] = ['opcao' => $this->opcao('GT02', 'Mensal')];
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => $dados]);

        $this->post("/municipio/preenchimentos/{$p->id}/finalizar", ['declaracao' => 1])->assertSessionHasErrors('pendencias');

        $this->post("/municipio/preenchimentos/{$p->id}/evidencias", [
            'pergunta_id' => $gt01->id, 'arquivo' => UploadedFile::fake()->create('decreto-gt.pdf', 120, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('sucesso');
        $ev = Evidencia::firstOrFail();
        Storage::disk('local')->assertExists($ev->caminho);
        $this->assertNotEmpty($ev->hash_sha256);

        $this->post("/municipio/preenchimentos/{$p->id}/finalizar", ['declaracao' => 1])->assertRedirect();
        $this->assertSame('aguardando_validacao', $p->fresh()->status);
    }

    public function test_hu0601_tipo_de_arquivo_nao_permitido(): void
    {
        $p = $this->iniciar();
        $this->post("/municipio/preenchimentos/{$p->id}/evidencias", [
            'pergunta_id' => $this->pergunta('GT01')->id, 'arquivo' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('arquivo');
    }

    public function test_hu0602_e_0603_excluir_e_baixar_evidencia(): void
    {
        $p = $this->iniciar();
        $gt01 = $this->pergunta('GT01');
        $this->post("/municipio/preenchimentos/{$p->id}/evidencias", ['pergunta_id' => $gt01->id, 'arquivo' => UploadedFile::fake()->create('ato.pdf', 50, 'application/pdf')]);
        $ev = Evidencia::firstOrFail();

        $this->get("/evidencias/{$ev->id}/download")->assertOk()->assertDownload('ato.pdf');
        $this->actingAs($this->gestor)->get("/evidencias/{$ev->id}/download")->assertOk();
        $this->actingAs($this->admin())->get("/evidencias/{$ev->id}/download")->assertOk();
        $outro = $this->usuario(Perfil::Responsavel, $this->municipio('Gurupi'));
        $this->actingAs($outro)->get("/evidencias/{$ev->id}/download")->assertForbidden();

        $this->actingAs($this->resp)->delete("/municipio/evidencias/{$ev->id}")->assertRedirect();
        $this->assertDatabaseMissing('evidencias', ['id' => $ev->id]);
        Storage::disk('local')->assertMissing($ev->caminho);
    }

    public function test_fluxo_completo_finalizar_validar_com_assinatura(): void
    {
        $p = $this->iniciar();
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => $this->respostasCompletas()]);
        $this->post("/municipio/preenchimentos/{$p->id}/finalizar")->assertSessionHasErrors('declaracao');
        $this->post("/municipio/preenchimentos/{$p->id}/finalizar", ['declaracao' => 1])->assertRedirect();

        $p->refresh();
        $this->assertSame('aguardando_validacao', $p->status);
        $this->assertNotNull($p->indice_aderencia);
        $this->assertDatabaseHas('notificacoes', ['user_id' => $this->gestor->id, 'tipo' => 'aguardando_validacao']);

        // responsável não edita mais
        $this->get("/municipio/preenchimentos/{$p->id}/editar")->assertRedirect("/municipio/preenchimentos/{$p->id}");
        // responsável não valida
        $this->post("/municipio/preenchimentos/{$p->id}/validar", ['declaracao' => 1])->assertForbidden();

        // HU05.07 — gestor valida
        $this->actingAs($this->gestor)->get("/municipio/preenchimentos/{$p->id}")->assertOk()->assertSee('Validar e assinar');
        $this->post("/municipio/preenchimentos/{$p->id}/validar", ['declaracao' => 1])->assertRedirect();
        $p->refresh();
        $this->assertSame('validado', $p->status);
        $this->assertSame($this->gestor->id, $p->gestor_id);
        $this->assertSame(64, strlen($p->assinatura_hash));
        $this->assertSame('eletronica_simples', $p->assinatura_metodo);
        $this->assertDatabaseHas('auditorias', ['acao' => 'validacao', 'entidade_id' => $p->id]);
        $this->assertDatabaseHas('notificacoes', ['user_id' => $this->resp->id, 'tipo' => 'validacao']);

        // HU05.05/HU05.06 — visualizar e baixar
        $this->get("/municipio/preenchimentos/{$p->id}")->assertOk()->assertSee('Validado');
        $this->assertSame('application/pdf', $this->get("/municipio/preenchimentos/{$p->id}/pdf")->headers->get('content-type'));
    }

    public function test_gestor_devolve_para_ajustes(): void
    {
        $p = $this->iniciar();
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => $this->respostasCompletas()]);
        $this->post("/municipio/preenchimentos/{$p->id}/finalizar", ['declaracao' => 1]);

        $this->actingAs($this->gestor)->post("/municipio/preenchimentos/{$p->id}/devolver", ['motivo' => 'curto'])->assertSessionHasErrors('motivo');
        $this->post("/municipio/preenchimentos/{$p->id}/devolver", ['motivo' => 'Revisar a resposta TI01 e anexar convênio'])->assertRedirect();
        $this->assertSame('devolvido', $p->fresh()->status);
        $this->assertDatabaseHas('notificacoes', ['user_id' => $this->resp->id, 'tipo' => 'devolucao']);

        $this->actingAs($this->resp)->get("/municipio/preenchimentos/{$p->id}/editar")->assertOk()->assertSee('Revisar a resposta TI01');
    }

    public function test_encerramento_do_prazo_bloqueia_alteracoes(): void
    {
        $p = $this->iniciar();
        $this->q->update(['data_fim' => today()->subDay(), 'data_limite_assinatura' => today()->addDay()]);
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => []])->assertSessionHasErrors('preenchimento');
        $this->get("/municipio/preenchimentos/{$p->id}/editar")->assertRedirect();
        $this->post("/municipio/preenchimentos/{$p->id}/evidencias", ['pergunta_id' => $this->pergunta('GT01')->id,
            'arquivo' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf')])->assertForbidden();
    }

    public function test_nao_inicia_fora_do_periodo(): void
    {
        $this->q->update(['data_inicio' => today()->addDays(3), 'data_fim' => today()->addDays(10)]);
        $this->actingAs($this->resp)->post("/municipio/questionarios/{$this->q->id}/iniciar")->assertSessionHas('erro');
        $this->assertSame(0, Preenchimento::count());
    }

    public function test_isolamento_entre_municipios(): void
    {
        $p = $this->iniciar();
        $outro = $this->usuario(Perfil::Responsavel, $this->municipio('Gurupi'));
        $this->actingAs($outro)->get("/municipio/preenchimentos/{$p->id}")->assertForbidden();
        $this->actingAs($outro)->put("/municipio/preenchimentos/{$p->id}", ['respostas' => []])->assertForbidden();
        $this->actingAs($this->gestor)->get("/municipio/preenchimentos/{$p->id}/editar")->assertForbidden();
    }

    public function test_opcao_de_outra_pergunta_e_rejeitada(): void
    {
        $p = $this->iniciar();
        $this->put("/municipio/preenchimentos/{$p->id}", ['respostas' => [
            $this->pergunta('LT01')->id => ['opcao' => $this->opcao('RH02', 'Não')],
        ]])->assertSessionHasErrors();
        $this->assertSame(0, $p->respostas()->count());
        $this->assertNotNull(Pergunta::first());
    }
}
