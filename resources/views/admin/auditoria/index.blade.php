@extends('layouts.app')
@section('titulo', 'Auditoria')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Auditoria</h1><div class="subtitulo">Registros de publicação, prorrogação, validação e encerramento de questionários.</div></div>
    <div class="acoes">
        <a class="btn btn-neutro" href="{{ route('admin.auditoria.pdf', request()->query()) }}">@icon('download') Exportar PDF</a>
        <a class="btn btn-neutro" href="{{ route('admin.auditoria.xlsx', request()->query()) }}">@icon('download') Exportar XLS</a>
    </div>
</div>
<form class="card filtros" method="get">
    <div class="campo"><label for="busca">Questionário ou usuário</label><input id="busca" type="search" name="busca" value="{{ request('busca') }}"></div>
    <div class="campo curto"><label for="acao">Ação</label>
        <select id="acao" name="acao"><option value="">Todas</option>@foreach (\App\Models\Auditoria::ACOES as $k => $v)<option value="{{ $k }}" @selected(request('acao') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="campo curto"><label for="de">De</label><input id="de" type="date" name="de" value="{{ request('de') }}"></div>
    <div class="campo curto"><label for="ate">Até</label><input id="ate" type="date" name="ate" value="{{ request('ate') }}"></div>
    <button class="btn btn-secundario" type="submit">@icon('filtro') Filtrar</button>
</form>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Data/hora</th><th>Ação</th><th>Questionário / objeto</th><th>Usuário</th><th>Justificativa</th><th class="acoes-col">Ações</th></tr></thead>
        <tbody>
        @forelse ($registros as $a)
            <tr>
                <td>{{ $a->created_at?->format('d/m/Y H:i:s') }}</td>
                <td><span class="etiqueta {{ ['publicacao' => 'verde', 'prorrogacao' => 'amarelo', 'validacao' => 'azul'][$a->acao] ?? 'cinza' }}">{{ $a->acaoLabel() }}</span></td>
                <td>{{ $a->entidade_descricao }}</td>
                <td>{{ $a->user_nome }}</td>
                <td class="muted">{{ \Illuminate\Support\Str::limit($a->justificativa, 80) ?: '—' }}</td>
                <td class="acoes-col"><a class="btn btn-link" href="{{ route('admin.auditoria.show', $a) }}">@icon('olho') Detalhes</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="vazio">Nenhum registro de auditoria.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $registros->links() }}
@endsection
