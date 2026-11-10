@extends('layouts.app')
@section('titulo', 'Temas de avaliação')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Temas de avaliação</h1><div class="subtitulo">Dimensões temáticas que organizam as perguntas dos questionários.</div></div>
    <a href="{{ route('admin.temas.create') }}" class="btn btn-primario">@icon('mais') Cadastrar tema</a>
</div>
<form class="card filtros" method="get">
    <div class="campo"><label for="busca">Buscar</label><input id="busca" type="search" name="busca" value="{{ request('busca') }}" placeholder="Nome do tema"></div>
    <div class="campo curto"><label for="ativo">Situação</label>
        <select id="ativo" name="ativo"><option value="">Todas</option><option value="1" @selected(request('ativo') === '1')>Ativos</option><option value="0" @selected(request('ativo') === '0')>Inativos</option></select></div>
    <button class="btn btn-secundario" type="submit">@icon('filtro') Filtrar</button>
</form>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th class="num">Ordem</th><th>Tema</th><th class="num">Perguntas</th><th>Situação</th><th class="acoes-col">Ações</th></tr></thead>
        <tbody>
        @forelse ($temas as $t)
            <tr>
                <td class="num">{{ $t->ordem }}</td>
                <td><strong>{{ $t->nome }}</strong><br><span class="muted texto-pequeno">{{ \Illuminate\Support\Str::limit($t->descricao, 140) }}</span></td>
                <td class="num">{{ $t->perguntas_count }}</td>
                <td>@if($t->ativo)<span class="etiqueta verde">Ativo</span>@else<span class="etiqueta cinza">Inativo</span>@endif</td>
                <td class="acoes-col">
                    <a class="btn btn-link" href="{{ route('admin.temas.edit', $t) }}">@icon('editar') Editar</a>
                    <form method="post" action="{{ route('admin.temas.status', $t) }}" style="display:inline" data-confirm="Deseja realmente {{ $t->ativo ? 'inativar' : 'ativar' }} o tema?">
                        @csrf @method('patch')<button class="btn btn-link">{{ $t->ativo ? 'Inativar' : 'Ativar' }}</button></form>
                    <form method="post" action="{{ route('admin.temas.destroy', $t) }}" style="display:inline" data-confirm="Excluir o tema &quot;{{ $t->nome }}&quot;? Esta ação não pode ser desfeita.">
                        @csrf @method('delete')<button class="btn btn-link perigo">@icon('lixo') Excluir</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="vazio">Nenhum tema encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $temas->links() }}
@endsection
