@extends('layouts.app')
@section('titulo', 'Desempenho por relatoria')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Desempenho dos municípios por relatoria</h1><div class="subtitulo">Subsídio para ações preventivas e colaborativas de cada relatoria.</div></div>
</div>
@if (! $questionario)
    <div class="alerta info">@icon('info') <div>Nenhum questionário publicado.</div></div>
@else
@include('tribunal.painel._filtro')
<div class="card"><div class="grafico"><canvas id="gRel"></canvas></div></div>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Relatoria</th><th class="num">Municípios</th><th class="num">Participação</th><th>Índice médio</th><th class="num">Baixa</th><th class="num">Média</th><th class="num">Alta</th><th>Menores índices</th><th></th></tr></thead>
        <tbody>
        @foreach ($relatorias as $r)
            <tr>
                <td><strong>{{ $r->relatoria->nome }}</strong>@if($r->relatoria->conselheiro)<br><span class="muted texto-pequeno">{{ $r->relatoria->conselheiro }}</span>@endif</td>
                <td class="num">{{ $r->total }}</td>
                <td class="num">{{ number_format($r->participacao, 1, ',', '.') }}%</td>
                <td><div class="barra-indice"><div class="progresso"><span style="width:{{ $r->indice ?? 0 }}%"></span></div><b>@indice($r->indice)</b></div></td>
                <td class="num faixa-baixa">{{ $r->baixa }}</td><td class="num faixa-media">{{ $r->media }}</td><td class="num faixa-alta">{{ $r->alta }}</td>
                <td class="texto-pequeno">@foreach ($r->piores as $l){{ $l->municipio->nome }} (@indice($l->indice))@if(! $loop->last), @endif @endforeach</td>
                <td><a class="btn btn-link btn-pequeno" href="{{ route('tribunal.relatorios.resultados', ['questionario_id' => $questionario->id, 'relatoria_id' => $r->relatoria->id]) }}">Municípios ›</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@push('scripts')
<script>
(function () {
    @php($jsRel = $relatorias->map(fn ($r) => ['n' => $r->relatoria->nome, 'b' => $r->baixa, 'm' => $r->media, 'a' => $r->alta])->values())
    var C = window.SDGT_CORES, rel = {!! json_encode($jsRel) !!};
    new Chart(document.getElementById('gRel'), { type: 'bar', data: { labels: rel.map(function (r) { return r.n; }), datasets: [
        { label: 'Baixa', data: rel.map(function (r) { return r.b; }), backgroundColor: C.faixa.baixa },
        { label: 'Média', data: rel.map(function (r) { return r.m; }), backgroundColor: C.faixa.media },
        { label: 'Alta', data: rel.map(function (r) { return r.a; }), backgroundColor: C.faixa.alta }] },
        options: { scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } } });
})();
</script>
@endpush
@endif
@endsection
