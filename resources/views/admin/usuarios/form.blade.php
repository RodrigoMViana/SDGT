@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuário' : 'Cadastrar usuário')
@section('conteudo')
<div class="migalhas"><a href="{{ route('admin.usuarios.index') }}">Usuários</a> › {{ $usuario->exists ? 'Editar' : 'Cadastrar' }}</div>
<h1>{{ $usuario->exists ? 'Editar usuário' : 'Cadastrar usuário' }}</h1>
<div class="alerta info">@icon('info') <div>Usuários municipais acessam via <strong>GOV.BR</strong> ou <strong>Certificado Digital</strong> (identificados pelo CPF). Usuários do Tribunal acessam com e-mail/CPF e senha.</div></div>
<form method="post" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" class="card">
    @csrf @if($usuario->exists) @method('put') @endif
    <div class="form-grade">
        <div class="campo c6"><label for="name" class="obrigatorio">Nome</label><input id="name" name="name" type="text" value="{{ old('name', $usuario->name) }}" required></div>
        <div class="campo c6"><label for="email" class="obrigatorio">E-mail</label><input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required></div>
        <div class="campo c4"><label for="cpf">CPF</label><input id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14" value="{{ old('cpf', $usuario->cpf) }}" placeholder="Somente números"></div>
        <div class="campo c4"><label for="perfil" class="obrigatorio">Perfil</label>
            <select id="perfil" name="perfil" required>@foreach (\App\Enums\Perfil::cases() as $p)<option value="{{ $p->value }}" @selected(old('perfil', $usuario->perfil?->value) === $p->value)>{{ $p->label() }}</option>@endforeach</select></div>
        <div class="campo c4"><label for="municipio_id">Município (perfis municipais)</label>
            <select id="municipio_id" name="municipio_id"><option value="">—</option>@foreach ($municipios as $m)<option value="{{ $m->id }}" @selected(old('municipio_id', $usuario->municipio_id) == $m->id)>{{ $m->nome }}</option>@endforeach</select></div>
        <div class="campo c6"><label for="cargo">Cargo</label><input id="cargo" name="cargo" type="text" value="{{ old('cargo', $usuario->cargo) }}"></div>
        <div class="campo c3"><label for="password">{{ $usuario->exists ? 'Nova senha' : 'Senha' }}</label><input id="password" name="password" type="password" autocomplete="new-password">
            <span class="ajuda-campo">Mín. 8 caracteres, letras e números. Apenas Tribunal.</span></div>
        <div class="campo c3"><label for="password_confirmation">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
        <div class="campo"><label class="check"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $usuario->ativo))> Usuário ativo</label></div>
    </div>
    <div class="acoes" style="margin-top:20px"><button class="btn btn-primario" type="submit">Salvar</button><a class="btn btn-neutro" href="{{ route('admin.usuarios.index') }}">Cancelar</a></div>
</form>
@endsection
