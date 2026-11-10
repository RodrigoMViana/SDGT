@extends('layouts.app')
@section('titulo', 'Questionários')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Questionários de diagnóstico</h1><div class="subtitulo">Cadastro, publicação, prorrogação e acompanhamento dos ciclos.</div></div>
    <a href="{{ route('admin.questionarios.create') }}" class="btn btn-primario">@icon('mais') Cadastrar questionário</a>
</div>
<form class="card filtros" method="get">
    <div class="campo"><label for="busca">Buscar</label><input id="busca" type="search" name="busca" value="{{ request('busca') }}" placeholder="Título"></div>
    <div class="campo curto"><label for="ciclo">Ciclo</label>
        <select id="ciclo" name="ciclo"><option value="">Todos</option>@foreach ($ciclos as $c)<option @selected(request('ciclo') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="campo curto"><label for="status">Status</label>
        <select id="status" name="status"><option value="">Todos</option>
            <option value="rascunho" @selected(request('status') === 'rascunho')>Rascunho</option>
            <option value="publicado" @selected(request('status') === 'publicado')>Publicado</option>
            <option value="encerrado" @selected(request('status') === 'encerrado')>Encerrado</option></select></div>
    <button class="btn btn-secundario" type="submit">@icon('filtro') Filtrar</button>
</form>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Questionário</th><th>Ciclo</th><th>Período de preenchimento</th><th>Assinatura até</th><th class="num">Perguntas</th><th class="num">Concluídos</th><th>Situação</th><th class="acoes-col">Ações</th></tr></thead>
        <tbody>
        @forelse ($questionarios as $q)
            <tr>
                <td><a href="{{ route('admin.questionarios.show', $q) }}"><strong>{{ $q->titulo }}</strong></a></td>
                <td>{{ $q->ciclo }}</td>
                <td>{{ $q->data_inicio->format('d/m/Y') }} a {{ $q->data_fim->format('d/m/Y') }}</td>
                <td>{{ $q->data_limite_assinatura->format('d/m/Y') }}</td>
                <td class="num">{{ $q->perguntas_count }}</td>
                <td class="num">{{ $finalizados[$q->id] ?? 0 }}</td>
                <td>@include('partials.status', ['tipo' => 'questionario', 'valor' => $q->situacao()])</td>
                <td class="acoes-col">
                    <a class="btn btn-link" href="{{ route('admin.questionarios.show', $q) }}">@icon('olho') Visualizar</a>
                    <a class="btn btn-link" href="{{ route('admin.questionarios.pdf', $q) }}">@icon('download') PDF</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="vazio">Nenhum questionário cadastrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $questionarios->links() }}
@endsection
