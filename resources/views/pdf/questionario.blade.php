@extends('pdf.layout')
@section('titulo', $questionario->titulo)
@section('conteudo')
<h1>{{ $questionario->titulo }}</h1>
<table class="dl">
    <tr><td>Ciclo</td><td>{{ $questionario->ciclo }}</td></tr>
    <tr><td>Período de preenchimento</td><td>{{ $questionario->data_inicio->format('d/m/Y') }} a {{ $questionario->data_fim->format('d/m/Y') }}</td></tr>
    <tr><td>Prazo de validação/assinatura</td><td>{{ $questionario->data_limite_assinatura->format('d/m/Y') }}</td></tr>
    @if ($preenchimento)
        <tr><td>Município</td><td>{{ $preenchimento->municipio->nome }} (IBGE {{ $preenchimento->municipio->codigo_ibge }})</td></tr>
        <tr><td>Situação</td><td>{{ $preenchimento->statusLabel() }}</td></tr>
        <tr><td>Responsável</td><td>{{ $preenchimento->responsavel?->name ?? '—' }} @if($preenchimento->finalizado_em)— finalizado em {{ $preenchimento->finalizado_em->format('d/m/Y H:i') }}@endif</td></tr>
        <tr><td>Gestor</td><td>{{ $preenchimento->gestor?->name ?? '—' }} @if($preenchimento->validado_em)— validado em {{ $preenchimento->validado_em->format('d/m/Y H:i') }}@endif</td></tr>
        @php($f = \App\Services\PontuacaoService::faixa($preenchimento->indice_aderencia))
        <tr><td>Índice de aderência</td><td class="{{ $f }}"><strong>{{ $preenchimento->indice_aderencia === null ? '—' : number_format($preenchimento->indice_aderencia, 1, ',', '.').'%' }}</strong> {{ \App\Services\PontuacaoService::faixaLabel($f) }}</td></tr>
    @endif
</table>
@if (! $preenchimento && $questionario->apresentacao)
    <div class="caixa" style="white-space: pre-line">{{ $questionario->apresentacao }}</div>
@endif
@foreach ($estrutura as $bloco)
    <h2>{{ $bloco['tema'] }}</h2>
    @foreach ($bloco['itens'] as $item)
        @php($pg = $item['pergunta'])
        <div class="pergunta">
            <span class="codigo">{{ $pg->codigo }}</span> <strong>{{ $pg->enunciado }}</strong>{{ $pg->obrigatoria ? ' *' : '' }}
            @if ($pg->pergunta_pai_id)<div class="muted" style="font-size:7.5pt">Condicionada a {{ $pg->perguntaPai?->codigo }} = {{ $pg->opcoesHabilitadoras->pluck('texto')->implode(' / ') }}</div>@endif
            @if ($preenchimento)
                @if (! $item['habilitada'])<div class="resposta na">Não se aplica (condição não atendida)</div>
                @else
                    <div class="resposta">{{ $item['texto_resposta'] ?: 'Não respondida' }}</div>
                    @if ($item['evidencias']->isNotEmpty())<div class="muted" style="font-size:7.5pt">Evidências: {{ $item['evidencias']->pluck('nome_original')->implode('; ') }}</div>@endif
                @endif
            @else
                @if ($pg->temOpcoes())
                    <div style="margin-top:3px">@foreach ($pg->opcoes as $o)( &nbsp; ) {{ $o->texto }}<br>@endforeach</div>
                @else
                    <div class="resposta" style="height:{{ $pg->tipo === 'texto' ? 40 : 14 }}px"></div>
                @endif
            @endif
        </div>
    @endforeach
@endforeach
@if ($preenchimento?->assinatura_detalhes)
    <div class="caixa"><strong>Assinatura:</strong> {{ $preenchimento->assinatura_detalhes }}</div>
@endif
@endsection
