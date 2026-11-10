<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Mail\NotificacaoMail;
use App\Models\MaterialApoio;
use App\Models\Notificacao;
use App\Models\Preenchimento;
use App\Models\Relatoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** EP09 — Notificações · EP10 — E-mail (rotina de marcos) · EP11 — Materiais · Usuários */
class NotificacoesMateriaisUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seedBase();
    }

    public function test_hu0901_a_0903_indicador_listagem_e_visualizacao(): void
    {
        $u = $this->usuario(Perfil::Responsavel, $this->municipio());
        $q = $this->questionarioAberto();
        $n1 = Notificacao::create(['user_id' => $u->id, 'tipo' => 'inicio_preenchimento', 'titulo' => 'Início X', 'mensagem' => 'Mensagem detalhada', 'questionario_id' => $q->id]);
        Notificacao::create(['user_id' => $u->id, 'tipo' => 'geral', 'titulo' => 'Aviso Y', 'mensagem' => 'Outra']);

        $this->actingAs($u)->get('/municipio/questionarios')->assertSee('data-testid="contador-notificacoes">2<', false);
        $this->get('/notificacoes')->assertOk()->assertSee('Início X')->assertSee('Aviso Y');
        $this->get("/notificacoes/{$n1->id}")->assertOk()->assertSee('Mensagem detalhada');
        $this->assertNotNull($n1->fresh()->lida_em);
        $this->get('/notificacoes?filtro=nao_lidas')->assertDontSee('Início X')->assertSee('Aviso Y');
        $this->post('/notificacoes/marcar-todas')->assertRedirect();
        $this->assertSame(0, $u->naoLidas());

        $outro = $this->usuario(Perfil::Responsavel, $this->municipio('Gurupi'));
        $this->actingAs($outro)->get("/notificacoes/{$n1->id}")->assertForbidden();
    }

    public function test_hu1001_e_1002_rotina_de_marcos_notifica_inicio_lembrete_e_encerramento(): void
    {
        $resp = $this->usuario(Perfil::Responsavel, $this->municipio());
        $gestor = $this->usuario(Perfil::Gestor, $this->municipio());

        // início hoje e fim em 2 dias → notifica início e lembrete (lembrete com 3 dias de antecedência)
        $q = $this->questionarioAberto(['data_inicio' => today(), 'data_fim' => today()->addDays(2), 'data_limite_assinatura' => today()->addDays(3)]);
        $this->artisan('sdgt:processar-marcos')->assertSuccessful();
        foreach ([$resp, $gestor] as $u) {
            $this->assertDatabaseHas('notificacoes', ['user_id' => $u->id, 'tipo' => 'inicio_preenchimento', 'questionario_id' => $q->id]);
            $this->assertDatabaseHas('notificacoes', ['user_id' => $u->id, 'tipo' => 'lembrete_encerramento', 'questionario_id' => $q->id]);
        }
        Mail::assertQueued(NotificacaoMail::class, 4);

        // idempotente: rodar de novo não duplica
        $this->artisan('sdgt:processar-marcos');
        $this->assertSame(4, Notificacao::count());

        // após o fim: notifica encerramento; após prazo de assinatura: encerra
        $q->update(['data_inicio' => today()->subDays(20), 'data_fim' => today()->subDays(2), 'data_limite_assinatura' => today()->subDay()]);
        $this->artisan('sdgt:processar-marcos');
        $this->assertDatabaseHas('notificacoes', ['user_id' => $resp->id, 'tipo' => 'encerramento_preenchimento']);
        $this->assertSame('encerrado', $q->fresh()->status);
        $this->assertDatabaseHas('auditorias', ['acao' => 'encerramento', 'entidade_id' => $q->id]);
    }

    public function test_lembrete_nao_e_enviado_a_municipio_que_ja_finalizou(): void
    {
        $palmas = $this->municipio();
        $resp = $this->usuario(Perfil::Responsavel, $palmas);
        $q = $this->questionarioAberto(['data_inicio' => today()->subDays(10), 'data_fim' => today()->addDay(), 'data_limite_assinatura' => today()->addDays(2)]);
        Preenchimento::create(['questionario_id' => $q->id, 'municipio_id' => $palmas->id, 'status' => 'aguardando_validacao']);
        $this->artisan('sdgt:processar-marcos');
        $this->assertDatabaseMissing('notificacoes', ['user_id' => $resp->id, 'tipo' => 'lembrete_encerramento']);
    }

    public function test_hu1101_a_1105_materiais_de_apoio(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin());
        $this->post('/admin/materiais', ['titulo' => 'Manual do IBS', 'categoria' => 'manual', 'ativo' => 1,
            'arquivo' => UploadedFile::fake()->create('manual.pdf', 300, 'application/pdf')])->assertRedirect('/materiais');
        $m = MaterialApoio::firstOrFail();
        Storage::disk('local')->assertExists($m->arquivo_caminho);

        $this->post('/admin/materiais', ['titulo' => 'Sem arquivo', 'categoria' => 'outro'])->assertSessionHasErrors('arquivo');
        $this->post('/admin/materiais', ['titulo' => 'Link EC 132', 'categoria' => 'normativo', 'url_externa' => 'https://www.planalto.gov.br/x', 'ativo' => 1])->assertRedirect();
        $this->post('/admin/materiais', ['titulo' => 'Rascunho interno', 'categoria' => 'outro', 'url_externa' => 'https://exemplo.gov.br']);

        $this->put("/admin/materiais/{$m->id}", ['titulo' => 'Manual do IBS v2', 'categoria' => 'manual', 'ativo' => 1])->assertRedirect();
        $this->assertSame('Manual do IBS v2', $m->fresh()->titulo);

        $resp = $this->usuario(Perfil::Responsavel, $this->municipio());
        $this->actingAs($resp)->get('/materiais')->assertOk()->assertSee('Manual do IBS v2')->assertSee('Link EC 132')->assertDontSee('Rascunho interno');
        $this->get("/materiais/{$m->id}/download")->assertOk()->assertDownload('manual.pdf');
        $this->get('/admin/materiais/create')->assertForbidden();

        $this->actingAs($this->admin())->delete("/admin/materiais/{$m->id}")->assertRedirect();
        Storage::disk('local')->assertMissing($m->arquivo_caminho);
    }

    public function test_cadastro_provisorio_de_usuarios(): void
    {
        $this->actingAs($this->admin());
        $palmas = $this->municipio();
        $this->post('/admin/usuarios', ['name' => 'Resp Palmas', 'email' => 'r@palmas.to.gov.br', 'perfil' => 'responsavel', 'municipio_id' => $palmas->id, 'ativo' => 1])
            ->assertSessionHasErrors('cpf');
        $this->post('/admin/usuarios', ['name' => 'Resp Palmas', 'email' => 'r@palmas.to.gov.br', 'cpf' => '529.982.247-25', 'perfil' => 'responsavel', 'municipio_id' => $palmas->id, 'ativo' => 1])
            ->assertRedirect('/admin/usuarios');
        $this->assertDatabaseHas('users', ['cpf' => '52998224725', 'municipio_id' => $palmas->id]);
        $this->post('/admin/usuarios', ['name' => 'Dup', 'email' => 'd@x.br', 'cpf' => '52998224725', 'perfil' => 'gestor', 'municipio_id' => $palmas->id])
            ->assertSessionHasErrors('cpf');
        $this->post('/admin/usuarios', ['name' => 'Auditor', 'email' => 'aud@tceto.tc.br', 'perfil' => 'auditor', 'password' => 'Senha1234', 'password_confirmation' => 'Senha1234', 'ativo' => 1])
            ->assertRedirect();

        $admin = $this->admin();
        $this->put("/admin/usuarios/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'perfil' => 'auditor', 'ativo' => 1])->assertSessionHas('erro');
        $this->delete("/admin/usuarios/{$admin->id}")->assertSessionHas('erro');
        $u = User::where('email', 'aud@tceto.tc.br')->first();
        $this->delete("/admin/usuarios/{$u->id}")->assertSessionHas('sucesso');
        $this->assertFalse($u->fresh()->ativo);
    }

    public function test_vinculo_de_municipio_a_relatoria(): void
    {
        $this->actingAs($this->admin());
        $m = $this->municipio('Gurupi');
        $rel = Relatoria::where('nome', '3ª Relatoria')->first();
        $this->get('/admin/municipios')->assertOk()->assertSee('Gurupi');
        $this->put("/admin/municipios/{$m->id}", ['relatoria_id' => $rel->id])->assertRedirect();
        $this->assertSame($rel->id, $m->fresh()->relatoria_id);
    }
}
