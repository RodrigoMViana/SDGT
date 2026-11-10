@extends('layouts.app')
@section('titulo', 'Preencher questionário')
@section('conteudo')
@php($q = $preenchimento->questionario)
<div class="migalhas"><a href="{{ route('municipio.questionarios.index') }}">Meus questionários</a> › Preenchimento</div>
<div class="cabecalho-pagina">
    <div><h1>{{ $q->titulo }}</h1>
        <div class="subtitulo">{{ $preenchimento->municipio->nome }} · prazo de preenchimento até <strong>{{ $q->data_fim->format('d/m/Y') }}</strong></div></div>
    <div class="acoes">@include('partials.status', ['tipo' => 'preenchimento', 'valor' => $preenchimento->status])</div>
</div>
@if ($preenchimento->status === 'devolvido')
    <div class="alerta erro">@icon('alerta') <div><strong>Devolvido pelo gestor para ajustes:</strong> {{ $preenchimento->observacao_devolucao }}</div></div>
@endif
<div class="alerta info">@icon('info') <div>As respostas podem ser <strong>salvas parcialmente</strong> e retomadas depois. Perguntas condicionais ficam visíveis, mas só são habilitadas conforme a resposta anterior. Prefira anexar documentos em <strong>PDF textual (pesquisável)</strong>.</div></div>

<form method="post" action="{{ route('municipio.preenchimentos.update', $preenchimento) }}" id="form-preenchimento" data-sujo>
    @csrf @method('put')
    @foreach ($estrutura as $bloco)
        <section class="tema-bloco">
            <h2>{{ $bloco['tema'] }}</h2>
            <div class="perguntas">
            @foreach ($bloco['itens'] as $item)
                @php($pg = $item['pergunta'])
                @php($temEvid = $pg->exige_evidencia || $pg->opcoes->contains(fn ($o) => $o->solicita_evidencia))
                <div class="pergunta @unless($item['habilitada']) desabilitada @endunless" id="pergunta-{{ $pg->id }}"
                     @if($pg->pergunta_pai_id) data-pai="{{ $pg->pergunta_pai_id }}" data-habilitadoras="{{ $pg->opcoesHabilitadoras->pluck('id')->implode(',') }}" @endif>
                    <input type="hidden" name="respostas[{{ $pg->id }}][presente]" value="1">
                    <div class="enunciado"><span class="codigo">{{ $pg->codigo }}</span>
                        <span>{{ $pg->enunciado }}@if($pg->obrigatoria)<span class="obrigatorio"></span>@endif</span></div>
                    @if ($pg->pergunta_pai_id)
                        <div class="condicao">@icon('info') Responda somente se {{ $pg->perguntaPai?->codigo }} = “{{ $pg->opcoesHabilitadoras->pluck('texto')->implode('” ou “') }}”.</div>
                    @endif
                    @if ($pg->ajuda || $pg->link_apoio)
                        <div class="ajuda">@icon('info') <span>{{ $pg->ajuda }} @if($pg->link_apoio)<a href="{{ $pg->link_apoio }}" target="_blank" rel="noopener">Material de apoio ›</a>@endif</span></div>
                    @endif
                    <div class="opcoes">
                        @switch($pg->tipo)
                            @case('unica')
                                @foreach ($pg->opcoes as $o)
                                    <label class="check"><input type="radio" name="respostas[{{ $pg->id }}][opcao]" value="{{ $o->id }}" @checked(in_array($o->id, $item['opcoes_ids'])) @disabled(! $item['habilitada'])>
                                        {{ $o->texto }}@if($o->solicita_evidencia) <span class="muted texto-pequeno">(requer documento)</span>@endif</label>
                                @endforeach
                                @break
                            @case('multipla')
                                @foreach ($pg->opcoes as $o)
                                    <label class="check"><input type="checkbox" name="respostas[{{ $pg->id }}][opcoes][]" value="{{ $o->id }}" @checked(in_array($o->id, $item['opcoes_ids'])) @disabled(! $item['habilitada'])>
                                        {{ $o->texto }}@if($o->solicita_evidencia) <span class="muted texto-pequeno">(requer documento)</span>@endif</label>
                                @endforeach
                                @break
                            @case('numero')
                                <input type="text" inputmode="decimal" name="respostas[{{ $pg->id }}][valor]" value="{{ $item['valor'] }}" style="max-width:220px" aria-label="Resposta {{ $pg->codigo }}" @disabled(! $item['habilitada'])>
                                @break
                            @case('data')
                                <input type="date" name="respostas[{{ $pg->id }}][valor]" value="{{ $item['valor'] }}" style="max-width:220px" aria-label="Resposta {{ $pg->codigo }}" @disabled(! $item['habilitada'])>
                                @break
                            @default
                                <textarea name="respostas[{{ $pg->id }}][valor]" maxlength="10000" aria-label="Resposta {{ $pg->codigo }}" @disabled(! $item['habilitada'])>{{ $item['valor'] }}</textarea>
                        @endswitch
                    </div>
                    @if ($temEvid)
                        <div class="evidencias @if($item['evidencia_exigida'] && $item['evidencias']->isEmpty()) exigida @endif">
                            <h4>@icon('clip') Documentos comprobatórios @if($item['evidencia_exigida'] && $item['evidencias']->isEmpty())<span class="etiqueta amarelo">Obrigatório para a resposta selecionada</span>@endif</h4>
                            <ul class="lista-arquivos">
                                @foreach ($item['evidencias'] as $ev)
                                    <li><a href="{{ route('evidencias.download', $ev) }}">@icon('download') {{ $ev->nome_original }}</a>
                                        <span class="muted texto-pequeno">{{ $ev->tamanhoFormatado() }}</span>
                                        <button class="btn btn-link perigo btn-pequeno" type="submit" form="del-ev-{{ $ev->id }}">@icon('lixo') Excluir</button></li>
                                @endforeach
                            </ul>
                            <div class="upload-linha">
                                <input type="file" name="arquivo" form="up-{{ $pg->id }}" accept=".{{ implode(',.', config('sdgt.evidencias.extensoes')) }}" aria-label="Arquivo para {{ $pg->codigo }}" required>
                                <button class="btn btn-secundario btn-pequeno" type="submit" form="up-{{ $pg->id }}">@icon('upload') Anexar</button>
                                <span class="muted texto-pequeno">Até {{ (int) (config('sdgt.evidencias.tamanho_max_kb') / 1024) }} MB</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
            </div>
        </section>
    @endforeach

    <div class="barra-fixa">
        <div class="resumo-progresso">
            <span class="texto-pequeno"><strong>{{ $respondidas }}</strong> de {{ $total }} respondidas</span>
            <div class="progresso"><span style="width:{{ $total ? round($respondidas / $total * 100) : 0 }}%"></span></div>
        </div>
        <div class="acoes">
            <button class="btn btn-neutro" type="submit" name="acao" value="salvar">Salvar e continuar depois</button>
            <button class="btn btn-primario" type="submit" name="acao" value="revisar">Salvar e revisar para finalizar ›</button>
        </div>
    </div>
</form>

{{-- Formulários de evidência fora do formulário principal (não é permitido aninhar formulários) --}}
@foreach ($estrutura as $bloco)
    @foreach ($bloco['itens'] as $item)
        <form method="post" enctype="multipart/form-data" action="{{ route('municipio.evidencias.store', $preenchimento) }}" id="up-{{ $item['pergunta']->id }}" data-upload hidden>
            @csrf <input type="hidden" name="pergunta_id" value="{{ $item['pergunta']->id }}"></form>
        @foreach ($item['evidencias'] as $ev)
            <form method="post" action="{{ route('municipio.evidencias.destroy', $ev) }}" id="del-ev-{{ $ev->id }}" data-upload data-confirm="Excluir o documento &quot;{{ $ev->nome_original }}&quot;?" hidden>@csrf @method('delete')</form>
        @endforeach
    @endforeach
@endforeach
@endsection
