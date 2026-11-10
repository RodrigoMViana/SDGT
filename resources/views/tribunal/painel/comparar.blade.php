@extends('layouts.app')
@section('titulo', 'Comparar municípios')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Comparar municípios</h1><div class="subtitulo">Compare até 6 municípios por tema de avaliação.</div></div>
</div>
<form method="get" class="card">
    <div class="form-grade">
        <div class="campo c6"><label for="questionario_id">Questionário / ciclo</label>
            <select id="questionario_id" name="questionario_id">@foreach ($questionarios as $opt)<option value="{{ $opt->id }}" @selected($questionario?->id === $opt->id)>{{ $opt->titulo }}</option>@endforeach</select></div>
        <div class="campo c6"><label for="municipios">Municípios (Ctrl/Cmd + clique; até 6)</label>
            <select id="municipios" name="municipios[]" multiple style="min-height:120px">
                @foreach ($municipios as $m)<option value="{{ $m->id }}" @selected(in_array($m->id, $selecionados))>{{ $m->nome }}</option>@endforeach
            </select></div>
    </div>
    <div class="acoes" style="margin-top:12px"><button class="btn btn-primario" type="submit">@icon('comparar') Comparar</button></div>
</form>
@if ($linhas->isEmpty())
    <div class="alerta info">@icon('info') <div>Selecione o questionário e os municípios para comparar.</div></div>
@else
<div class="card"><div class="grafico alto"><canvas id="gComp"></canvas></div></div>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Tema</th>@foreach ($linhas as $l)<th class="num">{{ $l->municipio->nome }}</th>@endforeach</tr></thead>
        <tbody>
            <tr><td><strong>Situação</strong></td>@foreach ($linhas as $l)<td class="num">@include('partials.status', ['tipo' => 'preenchimento', 'valor' => $l->status])</td>@endforeach</tr>
            <tr><td><strong>Índice geral</strong></td>@foreach ($linhas as $l)<td class="num"><strong class="faixa-{{ $l->faixa }}">@indice($l->indice)</strong></td>@endforeach</tr>
            @foreach ($temas as $t)
                <tr><td>{{ $t->nome }}</td>@foreach ($linhas as $l)<td class="num">@indice($l->por_tema[$t->id] ?? null)</td>@endforeach</tr>
            @endforeach
            <tr><td></td>@foreach ($linhas as $l)<td class="num">@if($l->preenchimento)<a class="btn btn-link btn-pequeno" href="{{ route('tribunal.preenchimentos.show', $l->preenchimento) }}">Ver respostas</a>@endif</td>@endforeach</tr>
        </tbody>
    </table>
</div>
@push('scripts')
<script>
(function () {
    var C = window.SDGT_CORES;
    var temas = @json($temas->pluck('nome'));
    var temaIds = @json($temas->pluck('id'));
    @php($jsLinhas = $linhas->map(fn ($l) => ['nome' => $l->municipio->nome, 'por_tema' => (object) $l->por_tema])->values())
    var linhas = {!! json_encode($jsLinhas) !!};
    new Chart(document.getElementById('gComp'), { type: 'bar', data: { labels: temas,
        datasets: linhas.map(function (l, i) { return { label: l.nome, backgroundColor: C.serie[i % C.serie.length], borderRadius: 3,
            data: temaIds.map(function (id) { return l.por_tema[id] ?? null; }) }; }) },
        options: { scales: { y: { beginAtZero: true, max: 100 } } } });
})();
</script>
@endpush
@endif
@endsection
