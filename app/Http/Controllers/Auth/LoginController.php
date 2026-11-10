<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Perfil;
use App\Http\Controllers\Controller;
use App\Integracoes\CertificadoDigital;
use App\Integracoes\Contracts\CadastroUnico;
use App\Integracoes\GovBrOidc;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * EP01 — Autenticação e Controle de Acesso.
 * HU01.01 usuário/senha (Tribunal) · HU01.02 GOV.BR (município) · HU01.03 Certificado Digital (município)
 */
class LoginController extends Controller
{
    public function form(): View
    {
        $simulados = $this->simuladoHabilitado()
            ? User::with('municipio')->where('ativo', true)->orderBy('perfil')->orderBy('name')->limit(60)->get()
            : collect();

        return view('auth.login', [
            'simulados' => $simulados,
            'govbrDriver' => config('sdgt.integracoes.govbr.driver'),
            'certDriver' => config('sdgt.integracoes.certificado.driver'),
        ]);
    }

    /** HU01.01 — Autenticar com usuário (e-mail ou CPF) e senha. */
    public function senha(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'usuario' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [], ['usuario' => 'usuário', 'password' => 'senha']);

        $login = trim($dados['usuario']);
        $cpf = preg_replace('/\D/', '', $login);
        $user = User::where('email', $login)->when(strlen($cpf) === 11, fn ($q) => $q->orWhere('cpf', $cpf))->first();

        if (! $user || ! $user->password || ! Auth::validate(['email' => $user->email, 'password' => $dados['password']])) {
            throw ValidationException::withMessages(['usuario' => 'Usuário ou senha inválidos.']);
        }
        if ($user->isMunicipal()) {
            throw ValidationException::withMessages(['usuario' => 'Usuários municipais devem acessar via GOV.BR ou Certificado Digital.']);
        }

        return $this->entrar($request, $user, 'senha', $request->boolean('lembrar'));
    }

    /** HU01.02 — Inicia o fluxo GOV.BR (OIDC) ou exibe o simulador. */
    public function govbr(Request $request, GovBrOidc $govbr): RedirectResponse
    {
        if (config('sdgt.integracoes.govbr.driver') !== 'oidc') {
            return redirect()->route('login')->with('aviso', 'GOV.BR em modo simulado: selecione o usuário na lista "Acesso simulado".');
        }
        $ini = $govbr->iniciar();
        $request->session()->put('govbr', ['state' => $ini['state'], 'verifier' => $ini['verifier']]);

        return redirect()->away($ini['url']);
    }

    public function govbrCallback(Request $request, GovBrOidc $govbr, CadastroUnico $cadun): RedirectResponse
    {
        $sess = $request->session()->pull('govbr');
        if (! $sess || ! hash_equals((string) $sess['state'], (string) $request->query('state'))) {
            return redirect()->route('login')->withErrors(['usuario' => 'Sessão de autenticação GOV.BR inválida. Tente novamente.']);
        }
        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors(['usuario' => 'Autenticação GOV.BR cancelada.']);
        }
        try {
            $dados = $govbr->concluir((string) $request->query('code'), $sess['verifier']);
        } catch (\Throwable $e) {
            Log::error('Falha GOV.BR', ['erro' => $e->getMessage()]);

            return redirect()->route('login')->withErrors(['usuario' => 'Não foi possível concluir a autenticação GOV.BR.']);
        }

        return $this->entrarPorCpf($request, $dados['cpf'], 'govbr', $cadun);
    }

    /** HU01.03 — Certificado digital (mTLS no proxy reverso). */
    public function certificado(Request $request, CertificadoDigital $cert, CadastroUnico $cadun): RedirectResponse
    {
        if (config('sdgt.integracoes.certificado.driver') !== 'header') {
            return redirect()->route('login')->with('aviso', 'Certificado Digital em modo simulado: selecione o usuário na lista "Acesso simulado".');
        }
        $cpf = $cert->extrairCpf($request);
        if (! $cpf) {
            return redirect()->route('login')->withErrors(['usuario' => 'Certificado digital ICP-Brasil não apresentado, inválido ou expirado.']);
        }

        return $this->entrarPorCpf($request, $cpf, 'certificado', $cadun);
    }

    /** Acesso simulado (somente com SDGT_LOGIN_SIMULADO=true — desenvolvimento/homologação). */
    public function simulado(Request $request): RedirectResponse
    {
        abort_unless($this->simuladoHabilitado(), 404);
        $dados = $request->validate(['user_id' => 'required|integer|exists:users,id', 'metodo' => 'nullable|in:senha,govbr,certificado']);
        $user = User::findOrFail($dados['user_id']);
        $metodo = $dados['metodo'] ?? ($user->isMunicipal() ? 'govbr' : 'senha');

        return $this->entrar($request, $user, $metodo);
    }

    public function logout(Request $request): RedirectResponse
    {
        $metodo = $request->user()?->ultimo_metodo_login;
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($metodo === 'govbr' && config('sdgt.integracoes.govbr.driver') === 'oidc') {
            return redirect()->away(app(GovBrOidc::class)->urlLogout());
        }

        return redirect()->route('login');
    }

    private function entrarPorCpf(Request $request, string $cpf, string $metodo, CadastroUnico $cadun): RedirectResponse
    {
        $user = User::where('cpf', $cpf)->first();
        if (! $user) {
            // Provisionamento a partir do CADUN (fonte oficial dos responsáveis/gestores)
            $pessoa = $cadun->buscarPorCpf($cpf);
            $municipio = $pessoa && $pessoa['codigo_ibge'] ? Municipio::where('codigo_ibge', $pessoa['codigo_ibge'])->first() : null;
            if (! $pessoa || ! $municipio) {
                return redirect()->route('login')->withErrors(['usuario' => 'CPF não habilitado no Cadastro Único (CADUN) do Tribunal para este sistema.']);
            }
            $user = User::create([
                'name' => $pessoa['nome'], 'email' => $pessoa['email'], 'cpf' => $cpf,
                'perfil' => $pessoa['perfil'] === 'gestor' ? Perfil::Gestor : Perfil::Responsavel,
                'municipio_id' => $municipio->id, 'cargo' => $pessoa['cargo'], 'ativo' => true,
            ]);
        }
        if (! $user->isMunicipal()) {
            return redirect()->route('login')->withErrors(['usuario' => 'Usuários do Tribunal devem acessar com usuário e senha.']);
        }

        return $this->entrar($request, $user, $metodo);
    }

    private function entrar(Request $request, User $user, string $metodo, bool $lembrar = false): RedirectResponse
    {
        if (! $user->ativo) {
            throw ValidationException::withMessages(['usuario' => 'Usuário inativo. Procure o administrador do sistema.']);
        }
        if ($user->isMunicipal() && ! $user->municipio_id) {
            throw ValidationException::withMessages(['usuario' => 'Usuário municipal sem município vinculado.']);
        }
        Auth::login($user, $lembrar);
        $request->session()->regenerate();
        $user->forceFill(['ultimo_metodo_login' => $metodo, 'ultimo_acesso_em' => now()])->save();

        return redirect()->intended(route('home'));
    }

    private function simuladoHabilitado(): bool
    {
        return (bool) config('sdgt.login_simulado') && ! app()->isProduction();
    }
}
