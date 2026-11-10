@extends('layouts.app')
@section('titulo', 'Relatório de resultados')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Resultados dos municípios</h1><div class="subtitulo">Consulta com filtros e exportação em PDF e XLS.</div></div>
    @if ($questionario)
    <div class="acoes">
        <a class="btn btn-neutro" href="{{ route('tribunal.relatorios.pdf', request()->query() + ['questionario_id' => $questionario->id]) }}">@icon('download') Exportar PDF</a>
        <a class="btn btn-neutro" href="{{ route('tribunal.relatorios.xlsx', request()->query() + ['questionario_id' => $questionario->id]) }}">@icon('download') Exportar XLS</a>
    </div>
    @endif
</div>
<form method="get" class="card filtros">
    <div class="campo"><label for="questionario_id">Questionário</label>
        <select id="questionario_id" name="questionario_id">@foreach ($questionarios as $opt)<option value="{{ $opt->id }}" @selected($questionario?->id === $opt->id)>{{ $opt->titulo }}</option>@endforeach</select></div>
    <div class="campo curto"><label for="relatoria_id">Relatoria</label>
        <select id="relatoria_id" name="relatoria_id"><option value="">Todas</option>@foreach ($relatorias as $r)<option value="{{ $r->id }}" @selected(($filtros['relatoria_id'] ?? '') == $r->id)>{{ $r->nome }}</option>@endforeach</select></div>
    <div class="campo curto"><label for="regiao">Região</label>
        <select id="regiao" name="regiao"><option value="">Todas</option>@foreach ($regioes as $r)<option @selected(($filtros['regiao'] ?? '') === $r)>{{ $r }}</option>@endforeach</select></div>
    <div class="campo curto"><label for="faixa">Faixa</label>
        <select id="faixa" name="faixa"><option value="">Todas</option><option value="baixa" @selected(($filtros['faixa'] ?? '') === 'baixa')>Baixa</option><option value="media" @selected(($filtros['faixa'] ?? '') === 'media')>Média</option><option value="alta" @selected(($filtros['faixa'] ?? '') === 'alta')>Alta</option></select></div>
    <div class="campo curto"><label for="status">Situação</label>
        <select id="status" name="status"><option value="">Todas</option>@foreach (\App\Models\Preenchimento::STATUS as $k => $v)<option value="{{ $k }}" @selected(($filtros['status'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
    <button class="btn btn-secundario" type="submit">@icon('filtro') Consultar</button>
</form>
<p class="muted">{{ $linhas->count() }} município(s).</p>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Município</th><th>Relatoria</th><th>Situação</th><th>Índice</th>@foreach ($temas as $t)<th class="num" title="{{ $t->nome }}">{{ \Illuminate\Support\Str::limit($t->nome, 14) }}</th>@endforeach<th></th></tr></thead>
        <tbody>
        @forelse ($linhas as $l)
            <tr>
                <td><strong>{{ $l->municipio->nome }}</strong><br><span class="muted texto-pequeno">{{ $l->municipio->regiao_imediata }}</span></td>
                <td>{{ $l->municipio->relatoria?->nome }}</td>
                <td>@include('partials.status', ['tipo' => 'preenchimento', 'valor' => $l->status])</td>
                <td><strong class="faixa-{{ $l->faixa }}">@indice($l->indice)</strong></td>
                @foreach ($temas as $t)<td class="num">@indice($l->por_tema[$t->id] ?? null)</td>@endforeach
                <td>@if($l->preenchimento)<a class="btn btn-link btn-pequeno" href="{{ route('tribunal.preenchimentos.show', $l->preenchimento) }}">@icon('olho') Ver</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="{{ 5 + $temas->count() }}" class="vazio">Nenhum resultado para os filtros informados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
