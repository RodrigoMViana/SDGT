@extends('layouts.app')
@section('titulo', 'Panorama consolidado')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Panorama consolidado</h1><div class="subtitulo">Estágio de preparação dos municípios para a Reforma Tributária.</div></div>
    @if ($questionario)
    <div class="acoes">
        <a class="btn btn-neutro" href="{{ route('tribunal.relatorios.pdf', ['questionario_id' => $questionario->id]) }}">@icon('download') PDF</a>
        <a class="btn btn-neutro" href="{{ route('tribunal.relatorios.xlsx', ['questionario_id' => $questionario->id]) }}">@icon('download') XLS</a>
    </div>
    @endif
</div>
@if (! $questionario)
    <div class="alerta info">@icon('info') <div>Nenhum questionário publicado ainda. Publique um questionário para acompanhar os resultados.</div></div>
@else
@include('tribunal.painel._filtro')
<div class="grade grade-4" style="margin-bottom:20px">
    <div class="kpi"><div class="rotulo">Municípios abrangidos</div><div class="valor">{{ $dados['total'] }}</div><div class="detalhe">{{ $questionario->situacaoLabel() }}</div></div>
    <div class="kpi verde"><div class="rotulo">Participação (finalizados)</div><div class="valor">{{ number_format($dados['participacao'], 1, ',', '.') }}%</div>
        <div class="detalhe">{{ $dados['concluidos'] }} concluídos · {{ $dados['validados'] }} validados</div></div>
    <div class="kpi"><div class="rotulo">Índice médio de aderência</div><div class="valor">@indice($dados['indice_medio'])</div>
        <div class="detalhe">{{ \App\Services\PontuacaoService::faixaLabel(\App\Services\PontuacaoService::faixa($dados['indice_medio'])) }}</div></div>
    <div class="kpi vermelho"><div class="rotulo">Baixa aderência</div><div class="valor">{{ $dados['faixas']['baixa'] }}</div><div class="detalhe">municípios em risco</div></div>
</div>

<div class="grade grade-2">
    <div class="card"><div class="card-titulo"><h3>Situação do preenchimento</h3></div><div class="grafico"><canvas id="gStatus"></canvas></div></div>
    <div class="card"><div class="card-titulo"><h3>Distribuição por faixa de aderência</h3>
        <span class="legenda-faixas">Baixa ≤ {{ number_format(config('sdgt.faixas.baixa_ate'), 0) }}% · Média ≤ {{ number_format(config('sdgt.faixas.media_ate'), 0) }}%</span></div>
        <div class="grafico"><canvas id="gFaixas"></canvas></div></div>
</div>
<div class="card"><div class="card-titulo"><h3>Índice médio por tema de avaliação</h3></div><div class="grafico"><canvas id="gTemas"></canvas></div></div>

<div class="grade grade-2">
    @foreach (['ranking_piores' => 'Menores índices (atenção prioritária)', 'ranking_melhores' => 'Maiores índices'] as $chave => $titulo)
    <div class="card"><h3>{{ $titulo }}</h3>
        <div class="tabela-wrap"><table class="tabela">
            <thead><tr><th>Município</th><th>Relatoria</th><th>Índice</th><th></th></tr></thead>
            <tbody>
            @forelse ($dados[$chave] as $l)
                <tr><td>{{ $l->municipio->nome }}</td><td class="muted">{{ $l->municipio->relatoria?->nome }}</td>
                    <td><div class="barra-indice"><div class="progresso"><span style="width:{{ $l->indice }}%;background:var(--{{ ['baixa' => 'vermelho', 'media' => 'laranja', 'alta' => 'verde'][$l->faixa] }})"></span></div><b>@indice($l->indice)</b></div></td>
                    <td><a class="btn btn-link btn-pequeno" href="{{ route('tribunal.preenchimentos.show', $l->preenchimento) }}">Ver</a></td></tr>
            @empty <tr><td colspan="4" class="vazio">Sem questionários concluídos.</td></tr>
            @endforelse
            </tbody></table></div>
    </div>
    @endforeach
</div>

<div class="card"><h3>Por região imediata (IBGE)</h3>
    <div class="tabela-wrap"><table class="tabela">
        <thead><tr><th>Região</th><th class="num">Municípios</th><th class="num">Concluídos</th><th>Índice médio</th></tr></thead>
        <tbody>@foreach ($dados['por_regiao'] as $r)
            <tr><td>{{ $r['regiao'] }}</td><td class="num">{{ $r['total'] }}</td><td class="num">{{ $r['concluidos'] }}</td>
                <td><div class="barra-indice"><div class="progresso"><span style="width:{{ $r['indice'] ?? 0 }}%"></span></div><b>@indice($r['indice'])</b></div></td></tr>
        @endforeach</tbody></table></div>
</div>
@endif
@endsection
@if ($questionario)
@push('scripts')
<script>
(function () {
    var C = window.SDGT_CORES;
    var st = @json($dados['por_status']->values());
    new Chart(document.getElementById('gStatus'), { type: 'bar', data: { labels: @json(array_values(\App\Models\Preenchimento::STATUS)),
        datasets: [{ data: st, backgroundColor: ['#D3D3D3', C.azulClaro, C.amarelo, '#E77773', C.verde] }] },
        options: { plugins: { legend: { display: false } }, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } } });
    new Chart(document.getElementById('gFaixas'), { type: 'doughnut', data: { labels: ['Baixa', 'Média', 'Alta'],
        datasets: [{ data: @json(array_values($dados['faixas'])), backgroundColor: [C.faixa.baixa, C.faixa.media, C.faixa.alta] }] },
        options: { plugins: { legend: { position: 'right' } } } });
    var temas = @json($dados['por_tema']);
    new Chart(document.getElementById('gTemas'), { type: 'bar', data: { labels: temas.map(function (t) { return t.tema; }),
        datasets: [{ label: 'Índice médio (%)', data: temas.map(function (t) { return t.indice; }), backgroundColor: C.azul, borderRadius: 4 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } } });
})();
</script>
@endpush
@endif
