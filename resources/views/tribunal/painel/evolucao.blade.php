@extends('layouts.app')
@section('titulo', 'Evolução da adequação')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Evolução da adequação municipal</h1><div class="subtitulo">Tendências ao longo dos ciclos de diagnóstico.</div></div>
</div>
<form method="get" class="card filtros">
    <div class="campo"><label for="municipio_id">Destacar município (opcional)</label>
        <select id="municipio_id" name="municipio_id" data-auto-submit><option value="">— Média estadual —</option>
            @foreach ($municipios as $m)<option value="{{ $m->id }}" @selected($municipio?->id === $m->id)>{{ $m->nome }}</option>@endforeach</select></div>
</form>
@if ($serie->isEmpty())
    <div class="alerta info">@icon('info') <div>Sem ciclos publicados.</div></div>
@else
<div class="grade grade-2">
    <div class="card"><h3>Índice médio de aderência</h3><div class="grafico"><canvas id="gIndice"></canvas></div></div>
    <div class="card"><h3>Distribuição por faixa (% dos concluídos)</h3><div class="grafico"><canvas id="gFaixa"></canvas></div></div>
</div>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Ciclo</th><th>Questionário</th><th class="num">Participação</th><th class="num">Índice médio</th>@if($municipio)<th class="num">{{ $municipio->nome }}</th>@endif<th class="num">Baixa</th><th class="num">Média</th><th class="num">Alta</th></tr></thead>
        <tbody>
        @foreach ($serie as $s)
            <tr><td>{{ $s->questionario->ciclo }}</td><td>{{ $s->questionario->titulo }}</td><td class="num">{{ number_format($s->participacao, 1, ',', '.') }}%</td>
                <td class="num"><strong>@indice($s->indice)</strong></td>@if($municipio)<td class="num">@indice($s->indice_municipio)</td>@endif
                <td class="num faixa-baixa">{{ number_format($s->baixa, 1, ',', '.') }}%</td><td class="num faixa-media">{{ number_format($s->media, 1, ',', '.') }}%</td><td class="num faixa-alta">{{ number_format($s->alta, 1, ',', '.') }}%</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@push('scripts')
<script>
(function () {
    @php($jsSerie = $serie->map(fn ($s) => ['c' => $s->questionario->ciclo, 'i' => $s->indice, 'm' => $s->indice_municipio, 'p' => $s->participacao, 'b' => $s->baixa, 'me' => $s->media, 'a' => $s->alta])->values())
    var C = window.SDGT_CORES, s = {!! json_encode($jsSerie) !!};
    var ds = [{ label: 'Média estadual', data: s.map(function (x) { return x.i; }), borderColor: C.azul, backgroundColor: C.azul, tension: .25 },
              { label: 'Participação (%)', data: s.map(function (x) { return x.p; }), borderColor: '#898989', borderDash: [5, 4], tension: .25 }];
    @if($municipio) ds.push({ label: @json($municipio->nome), data: s.map(function (x) { return x.m; }), borderColor: C.verde, backgroundColor: C.verde, tension: .25 }); @endif
    new Chart(document.getElementById('gIndice'), { type: 'line', data: { labels: s.map(function (x) { return x.c; }), datasets: ds }, options: { scales: { y: { beginAtZero: true, max: 100 } } } });
    new Chart(document.getElementById('gFaixa'), { type: 'bar', data: { labels: s.map(function (x) { return x.c; }), datasets: [
        { label: 'Baixa', data: s.map(function (x) { return x.b; }), backgroundColor: C.faixa.baixa },
        { label: 'Média', data: s.map(function (x) { return x.me; }), backgroundColor: C.faixa.media },
        { label: 'Alta', data: s.map(function (x) { return x.a; }), backgroundColor: C.faixa.alta }] },
        options: { scales: { x: { stacked: true }, y: { stacked: true, max: 100 } } } });
})();
</script>
@endpush
@endif
@endsection
