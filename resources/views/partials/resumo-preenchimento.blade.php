{{-- Cabeçalho com dados do preenchimento, índice e assinatura. Parâmetro: $preenchimento --}}
@php($p = $preenchimento)
@php($faixa = \App\Services\PontuacaoService::faixa($p->indice_aderencia))
<div class="grade grade-2">
    <div class="card">
        <h3>Dados do preenchimento</h3>
        <dl class="dl">
            <dt>Município</dt><dd>{{ $p->municipio->nome }} ({{ $p->municipio->codigo_ibge }})</dd>
            <dt>Situação</dt><dd>@include('partials.status', ['tipo' => 'preenchimento', 'valor' => $p->status])</dd>
            <dt>Responsável</dt><dd>{{ $p->responsavel?->name ?? '—' }}</dd>
            <dt>Finalizado em</dt><dd>{{ $p->finalizado_em?->format('d/m/Y H:i') ?? '—' }}</dd>
            <dt>Gestor (validação)</dt><dd>{{ $p->gestor?->name ?? '—' }}</dd>
            <dt>Validado em</dt><dd>{{ $p->validado_em?->format('d/m/Y H:i') ?? '—' }}</dd>
            @if ($p->observacao_devolucao && $p->status === 'devolvido')<dt>Motivo da devolução</dt><dd class="faixa-baixa">{{ $p->observacao_devolucao }}</dd>@endif
        </dl>
        @if ($p->assinatura_detalhes)
            <div class="alerta info texto-pequeno" style="margin:12px 0 0">@icon('assinar') <div>{{ $p->assinatura_detalhes }}</div></div>
        @endif
    </div>
    <div class="card">
        <div class="card-titulo"><h3>Índice de aderência</h3>@include('partials.status', ['tipo' => 'faixa', 'valor' => $faixa])</div>
        <div style="font-size:var(--fs-xxl);font-weight:800" class="faixa-{{ $faixa }}">@indice($p->indice_aderencia)</div>
        <p class="muted texto-pequeno">{{ number_format($p->pontuacao_obtida, 1, ',', '.') }} de {{ number_format($p->pontuacao_maxima, 1, ',', '.') }} pontos possíveis (perguntas não aplicáveis são desconsideradas).</p>
        <div class="indice-temas">
            @foreach ($estrutura as $bloco)
                @php($temaId = $bloco['itens'][0]['pergunta']->tema_id ?? null)
                @php($v = $p->indices_por_tema[$temaId] ?? null)
                @if ($v !== null)
                    <div class="linha"><span>{{ $bloco['tema'] }}</span>
                        <div class="barra-indice"><div class="progresso"><span style="width:{{ $v }}%"></span></div><b>@indice($v)</b></div></div>
                @endif
            @endforeach
        </div>
    </div>
</div>
