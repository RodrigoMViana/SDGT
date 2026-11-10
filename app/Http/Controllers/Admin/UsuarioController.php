<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Perfil;
use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Cadastro PROVISÓRIO de usuários. Pela Memória de Reunião de 03/11/2026, o cadastro
 * de responsáveis e gestores deve permanecer centralizado no CADUN; esta tela atende
 * à operação enquanto a integração não estiver disponível (ver docs/02).
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $usuarios = User::with('municipio')
            ->when($request->filled('perfil'), fn ($q) => $q->where('perfil', $request->query('perfil')))
            ->when($request->filled('busca'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('busca').'%')
                ->orWhere('email', 'like', '%'.$request->query('busca').'%')
                ->orWhere('cpf', 'like', '%'.preg_replace('/\D/', '', (string) $request->query('busca')).'%')))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => new User(['ativo' => true]), 'municipios' => Municipio::orderBy('nome')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validar($request));

        return redirect()->route('admin.usuarios.index')->with('sucesso', 'Usuário cadastrado.');
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.form', ['usuario' => $usuario, 'municipios' => Municipio::orderBy('nome')->get()]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $dados = $this->validar($request, $usuario);
        if ($usuario->id === $request->user()->id && ($dados['perfil'] !== Perfil::Administrador->value || ! $dados['ativo'])) {
            return back()->with('erro', 'Você não pode remover seu próprio perfil de administrador nem se inativar.');
        }
        $usuario->update($dados);

        return redirect()->route('admin.usuarios.index')->with('sucesso', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->id === $request->user()->id) {
            return back()->with('erro', 'Você não pode excluir o próprio usuário.');
        }
        $usuario->update(['ativo' => false]);

        return back()->with('sucesso', 'Usuário inativado (o histórico é preservado).');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $request->merge(['cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')) ?: null]);
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'cpf' => ['nullable', 'digits:11', Rule::unique('users', 'cpf')->ignore($usuario?->id)],
            'perfil' => ['required', new Enum(Perfil::class)],
            'municipio_id' => ['nullable', 'required_if:perfil,responsavel,gestor', 'exists:municipios,id'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'password' => [$usuario ? 'nullable' : 'nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['municipio_id.required_if' => 'Informe o município para perfis municipais.']);

        if (in_array($dados['perfil'], ['responsavel', 'gestor'], true) && empty($dados['cpf'])) {
            throw ValidationException::withMessages(['cpf' => 'CPF é obrigatório para usuários municipais (login GOV.BR/Certificado).']);
        }
        if (in_array($dados['perfil'], ['administrador', 'auditor'], true)) {
            $dados['municipio_id'] = null;
            if (! $usuario && empty($dados['password'])) {
                throw ValidationException::withMessages(['password' => 'Defina uma senha para usuários do Tribunal.']);
            }
        }
        if (empty($dados['password'])) {
            unset($dados['password']);
        }
        $dados['ativo'] = $request->boolean('ativo');

        return $dados;
    }
}
