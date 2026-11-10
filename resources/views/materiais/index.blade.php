@extends('layouts.app')
@section('titulo', 'Materiais de apoio')
@section('conteudo')
@php($admin = auth()->user()->temPerfil('administrador'))
<div class="cabecalho-pagina">
    <div><h1>Materiais de apoio</h1><div class="subtitulo">Cartilhas, manuais e normativos para auxiliar no preenchimento dos questionários.</div></div>
    @if ($admin)<a href="{{ route('admin.materiais.create') }}" class="btn btn-primario">@icon('mais') Cadastrar material</a>@endif
</div>
<form class="card filtros" method="get">
    <div class="campo"><label for="busca">Buscar</label><input id="busca" type="search" name="busca" value="{{ request('busca') }}" placeholder="Título"></div>
    <div class="campo curto"><label for="categoria">Categoria</label>
        <select id="categoria" name="categoria"><option value="">Todas</option>
            @foreach (\App\Models\MaterialApoio::CATEGORIAS as $k => $v)<option value="{{ $k }}" @selected(request('categoria') === $k)>{{ $v }}</option>@endforeach
        </select></div>
    <button class="btn btn-secundario" type="submit">@icon('filtro') Filtrar</button>
</form>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Título</th><th>Categoria</th><th>Descrição</th>@if($admin)<th>Situação</th>@endif<th class="acoes-col">Ações</th></tr></thead>
        <tbody>
        @forelse ($materiais as $m)
            <tr>
                <td><strong>{{ $m->titulo }}</strong></td>
                <td><span class="etiqueta azul">{{ $m->categoriaLabel() }}</span></td>
                <td class="muted">{{ $m->descricao }}</td>
                @if($admin)<td>@if($m->ativo)<span class="etiqueta verde">Ativo</span>@else<span class="etiqueta cinza">Inativo</span>@endif</td>@endif
                <td class="acoes-col">
                    <a class="btn btn-secundario btn-pequeno" href="{{ route('materiais.download', $m) }}" @if($m->url_externa && !$m->arquivo_caminho) target="_blank" rel="noopener" @endif>
                        @if($m->arquivo_caminho) @icon('download') Baixar @else @icon('link') Abrir link @endif</a>
                    @if ($admin)
                        <a class="btn btn-link" href="{{ route('admin.materiais.edit', $m) }}">@icon('editar') Editar</a>
                        <form method="post" action="{{ route('admin.materiais.destroy', $m) }}" style="display:inline" data-confirm="Excluir o material &quot;{{ $m->titulo }}&quot;?">
                            @csrf @method('delete')<button class="btn btn-link perigo">@icon('lixo') Excluir</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="vazio">Nenhum material disponível.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $materiais->links() }}
@endsection
