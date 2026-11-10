@extends('layouts.app')
@section('titulo', 'Usuários')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Usuários</h1><div class="subtitulo">Cadastro provisório até a integração com o CADUN (Cadastro Único do Tribunal).</div></div>
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primario">@icon('mais') Cadastrar usuário</a>
</div>
<form class="card filtros" method="get">
    <div class="campo"><label for="busca">Buscar</label><input id="busca" type="search" name="busca" value="{{ request('busca') }}" placeholder="Nome, e-mail ou CPF"></div>
    <div class="campo curto"><label for="perfil">Perfil</label>
        <select id="perfil" name="perfil"><option value="">Todos</option>@foreach (\App\Enums\Perfil::cases() as $p)<option value="{{ $p->value }}" @selected(request('perfil') === $p->value)>{{ $p->label() }}</option>@endforeach</select></div>
    <button class="btn btn-secundario" type="submit">@icon('filtro') Filtrar</button>
</form>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Nome</th><th>E-mail</th><th>CPF</th><th>Perfil</th><th>Município</th><th>Último acesso</th><th>Situação</th><th class="acoes-col">Ações</th></tr></thead>
        <tbody>
        @forelse ($usuarios as $u)
            <tr>
                <td><strong>{{ $u->name }}</strong>@if($u->cargo)<br><span class="muted texto-pequeno">{{ $u->cargo }}</span>@endif</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->cpfFormatado() ?? '—' }}</td>
                <td>{{ $u->perfil->label() }}</td>
                <td>{{ $u->municipio?->nome ?? '—' }}</td>
                <td>{{ $u->ultimo_acesso_em?->format('d/m/Y H:i') ?? '—' }}@if($u->ultimo_metodo_login)<br><span class="muted texto-pequeno">via {{ $u->ultimo_metodo_login }}</span>@endif</td>
                <td>@if($u->ativo)<span class="etiqueta verde">Ativo</span>@else<span class="etiqueta cinza">Inativo</span>@endif</td>
                <td class="acoes-col">
                    <a class="btn btn-link" href="{{ route('admin.usuarios.edit', $u) }}">@icon('editar') Editar</a>
                    @if($u->ativo && $u->id !== auth()->id())
                        <form method="post" action="{{ route('admin.usuarios.destroy', $u) }}" style="display:inline" data-confirm="Inativar o usuário {{ $u->name }}?">
                            @csrf @method('delete')<button class="btn btn-link perigo">Inativar</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="vazio">Nenhum usuário encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $usuarios->links() }}
@endsection
