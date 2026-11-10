<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** EP01 — Autenticação e Controle de Acesso */
class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    public function test_hu0101_admin_autentica_com_usuario_e_senha(): void
    {
        $this->post('/login', ['usuario' => 'admin@tceto.tc.br', 'password' => 'Sdgt@2026'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->get('/')->assertRedirect(route('tribunal.painel'));
        $this->assertSame('senha', auth()->user()->ultimo_metodo_login);
    }

    public function test_hu0101_auditor_autentica_pelo_cpf(): void
    {
        $u = User::factory()->perfil(Perfil::Auditor)->create(['cpf' => '12345678909']);
        $this->post('/login', ['usuario' => '123.456.789-09', 'password' => 'Senha123'])->assertRedirect();
        $this->assertAuthenticatedAs($u);
    }

    public function test_senha_invalida_e_rejeitada(): void
    {
        $this->post('/login', ['usuario' => 'admin@tceto.tc.br', 'password' => 'errada'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_usuario_municipal_nao_usa_senha(): void
    {
        $u = $this->usuario(Perfil::Responsavel, $this->municipio());
        $u->update(['password' => 'Senha123']);
        $this->post('/login', ['usuario' => $u->email, 'password' => 'Senha123'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_usuario_inativo_nao_entra(): void
    {
        User::where('email', 'admin@tceto.tc.br')->update(['ativo' => false]);
        $this->post('/login', ['usuario' => 'admin@tceto.tc.br', 'password' => 'Sdgt@2026'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_hu0102_govbr_oidc_redireciona_com_pkce(): void
    {
        config(['sdgt.integracoes.govbr' => array_merge(config('sdgt.integracoes.govbr'), [
            'driver' => 'oidc', 'client_id' => 'abc', 'client_secret' => 's', 'redirect_uri' => 'http://localhost/login/govbr/callback',
        ])]);
        $resp = $this->get('/login/govbr');
        $resp->assertRedirect();
        $url = $resp->headers->get('Location');
        $this->assertStringContainsString('sso.staging.acesso.gov.br/authorize', $url);
        $this->assertStringContainsString('code_challenge_method=S256', $url);
        $this->assertNotNull(session('govbr.state'));
    }

    public function test_hu0102_govbr_callback_autentica_e_provisiona_pelo_cadun(): void
    {
        config(['sdgt.integracoes.govbr' => array_merge(config('sdgt.integracoes.govbr'), [
            'driver' => 'oidc', 'client_id' => 'abc', 'client_secret' => 's', 'redirect_uri' => 'http://localhost/login/govbr/callback',
        ]), 'sdgt.integracoes.cadun.driver' => 'api', 'sdgt.integracoes.cadun.url' => 'https://cadun.test']);
        Http::fake([
            '*/token' => Http::response(['access_token' => 'tok', 'id_token' => 'x']),
            '*/userinfo/' => Http::response(['sub' => '52998224725', 'name' => 'Fulana', 'email' => 'fulana@palmas.to.gov.br']),
            'cadun.test/*' => Http::response(['nome' => 'Fulana de Tal', 'email' => 'fulana@palmas.to.gov.br', 'cpf' => '52998224725',
                'vinculos' => [['codigo_ibge' => '1721000', 'papel' => 'RESPONSAVEL_TRIBUTARIO', 'cargo' => 'Secretária']]]),
        ]);
        $this->withSession(['govbr' => ['state' => 'st', 'verifier' => 'v']])
            ->get('/login/govbr/callback?code=c&state=st')->assertRedirect(route('home'));

        $u = User::where('cpf', '52998224725')->first();
        $this->assertNotNull($u);
        $this->assertSame('Palmas', $u->municipio->nome);
        $this->assertSame(Perfil::Responsavel, $u->perfil);
        $this->assertAuthenticatedAs($u);
        $this->assertSame('govbr', $u->ultimo_metodo_login);
    }

    public function test_hu0102_govbr_callback_com_state_invalido(): void
    {
        $this->withSession(['govbr' => ['state' => 'st', 'verifier' => 'v']])
            ->get('/login/govbr/callback?code=c&state=outro')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_hu0103_certificado_digital_via_cabecalho_do_proxy(): void
    {
        config(['sdgt.integracoes.certificado.driver' => 'header']);
        $m = $this->municipio('Gurupi');
        $gestor = User::factory()->perfil(Perfil::Gestor, $m->id)->create(['cpf' => '11144477735']);

        // Certificado autoassinado de teste no padrão e-CPF (CN = "NOME:CPF"), gerado uma vez em tests/fixtures
        $pem = file_get_contents(base_path('tests/fixtures/ecpf-teste.pem'));

        $this->get('/login/certificado', ['X-SSL-CLIENT-CERT' => urlencode($pem)])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($gestor);
        $this->assertSame('certificado', $gestor->fresh()->ultimo_metodo_login);
    }

    public function test_hu0103_certificado_ausente_e_rejeitado(): void
    {
        config(['sdgt.integracoes.certificado.driver' => 'header']);
        $this->get('/login/certificado')->assertRedirect(route('login'))->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_login_simulado_desabilitado_por_padrao(): void
    {
        config(['sdgt.login_simulado' => false]);
        $this->post('/login/simulado', ['user_id' => 1])->assertNotFound();
    }

    public function test_login_simulado_habilitado(): void
    {
        config(['sdgt.login_simulado' => true]);
        $u = $this->usuario(Perfil::Gestor, $this->municipio());
        $this->post('/login/simulado', ['user_id' => $u->id])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($u);
    }

    public function test_controle_de_acesso_por_perfil(): void
    {
        $resp = $this->usuario(Perfil::Responsavel, $this->municipio());
        $this->actingAs($resp)->get('/admin/questionarios')->assertForbidden();
        $this->actingAs($resp)->get('/tribunal/painel')->assertForbidden();

        $auditor = $this->usuario(Perfil::Auditor);
        $this->actingAs($auditor)->get('/tribunal/painel')->assertOk();
        $this->actingAs($auditor)->get('/admin/temas')->assertForbidden();
        $this->actingAs($auditor)->get('/municipio/questionarios')->assertForbidden();

        $this->post('/logout');
        $this->get('/admin/temas')->assertRedirect('/login');
    }
}
