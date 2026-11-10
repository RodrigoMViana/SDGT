{{-- Renderização somente leitura do questionário. Parâmetros: $estrutura, $comRespostas (bool) --}}
@foreach ($estrutura as $bloco)
    <section class="tema-bloco">
        <h2>{{ $bloco['tema'] }}</h2>
        <div class="perguntas">
            @foreach ($bloco['itens'] as $item)
                @php($pg = $item['pergunta'])
                <div class="pergunta @if($comRespostas && ! $item['habilitada']) desabilitada @endif" id="pergunta-{{ $pg->id }}">
                    <div class="enunciado"><span class="codigo">{{ $pg->codigo }}</span><span>{{ $pg->enunciado }}@if($pg->obrigatoria)<span class="obrigatorio"></span>@endif</span></div>
                    @if ($pg->pergunta_pai_id)
                        <div class="condicao">@icon('info') Condicionada à pergunta {{ $pg->perguntaPai?->codigo ?? '' }}
                            ({{ $pg->opcoesHabilitadoras->pluck('texto')->implode(' / ') }})@if($comRespostas && ! $item['habilitada']) — não se aplica a este município @endif</div>
                    @endif
                    @if ($comRespostas)
                        @if ($item['habilitada'])
                            <div class="resposta-lida">{{ $item['texto_resposta'] ?: 'Não respondida' }}</div>
                            @if ($item['evidencias']->isNotEmpty() || $item['evidencia_exigida'])
                                <div class="evidencias">
                                    <h4>@icon('clip') Documentos comprobatórios</h4>
                                    <ul class="lista-arquivos">
                                        @forelse ($item['evidencias'] as $ev)
                                            <li><a href="{{ route('evidencias.download', $ev) }}">@icon('download') {{ $ev->nome_original }}</a> <span class="muted texto-pequeno">{{ $ev->tamanhoFormatado() }} · {{ $ev->created_at->format('d/m/Y H:i') }}</span></li>
                                        @empty
                                            <li class="faixa-baixa">Evidência exigida não anexada.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            @endif
                        @endif
                    @else
                        @if ($pg->temOpcoes())
                            <ul class="texto-pequeno" style="margin:0;padding-left:18px">
                                @foreach ($pg->opcoes as $o)<li>{{ $o->texto }} <span class="muted">({{ number_format($o->pontuacao, 1, ',', '.') }} pts{{ $o->solicita_evidencia ? ' · solicita evidência' : '' }})</span></li>@endforeach
                            </ul>
                        @else
                            <span class="muted texto-pequeno">Resposta: {{ \App\Models\Pergunta::TIPOS[$pg->tipo] }}</span>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endforeach
