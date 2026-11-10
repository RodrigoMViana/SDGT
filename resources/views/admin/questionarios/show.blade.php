@extends('layouts.app')
@section('titulo', $questionario->titulo)
@section('conteudo')
@php($sit = $questionario->situacao())
<div class="migalhas"><a href="{{ route('admin.questionarios.index') }}">Questionários</a> › Visualizar</div>
<div class="cabecalho-pagina">
    <div>
        <h1>{{ $questionario->titulo }}</h1>
        <div class="subtitulo">Ciclo {{ $questionario->ciclo }} · @include('partials.status', ['tipo' => 'questionario', 'valor' => $sit])</div>
    </div>
    <div class="acoes">
        @if ($questionario->editavel())
            <a href="{{ route('admin.questionarios.edit', $questionario) }}" class="btn btn-neutro">@icon('editar') Editar</a>
            <a href="{{ route('admin.questionarios.perguntas', $questionario) }}" class="btn btn-neutro">@icon('pergunta') Associar perguntas</a>
            <form method="post" action="{{ route('admin.questionarios.publicar', $questionario) }}" data-confirm="Publicar o questionário? Após a publicação as perguntas não poderão ser alteradas e os municípios serão notificados.">
                @csrf<button class="btn btn-sucesso" type="submit">@icon('publicar') Publicar</button></form>
        @else
            <button class="btn btn-secundario" data-abrir-modal="modal-prorrogar">@icon('relogio') Prorrogar prazo</button>
        @endif
        <form method="post" action="{{ route('admin.questionarios.duplicar', $questionario) }}" data-confirm="Criar uma cópia deste questionário em rascunho?">
            @csrf<button class="btn btn-neutro" type="submit">@icon('copiar') Duplicar</button></form>
        <a href="{{ route('admin.questionarios.pdf', $questionario) }}" class="btn btn-neutro">@icon('download') Baixar PDF</a>
        @if ($questionario->editavel())
            <form method="post" action="{{ route('admin.questionarios.destroy', $questionario) }}" data-confirm="Excluir definitivamente este questionário?">
                @csrf @method('delete')<button class="btn btn-perigo" type="submit">@icon('lixo') Excluir</button></form>
        @endif
    </div>
</div>

@unless ($questionario->editavel())
<div class="grade grade-4" style="margin-bottom:20px">
    <div class="kpi"><div class="rotulo">Municípios abrangidos</div><div class="valor">{{ $totalMunicipios }}</div></div>
    <div class="kpi amarelo"><div class="rotulo">Em preenchimento</div><div class="valor">{{ ($porStatus['em_preenchimento'] ?? 0) + ($porStatus['devolvido'] ?? 0) }}</div></div>
    <div class="kpi"><div class="rotulo">Aguardando validação</div><div class="valor">{{ $porStatus['aguardando_validacao'] ?? 0 }}</div></div>
    <div class="kpi verde"><div class="rotulo">Validados</div><div class="valor">{{ $porStatus['validado'] ?? 0 }}</div>
        <div class="detalhe"><a href="{{ route('tribunal.relatorios.resultados', ['questionario_id' => $questionario->id]) }}">Ver resultados por município ›</a></div></div>
</div>
@endunless

<div class="card">
    <h3>Informações gerais</h3>
    <dl class="dl">
        <dt>Período de preenchimento</dt><dd>{{ $questionario->data_inicio->format('d/m/Y') }} a {{ $questionario->data_fim->format('d/m/Y') }}</dd>
        <dt>Prazo para validação/assinatura</dt><dd>{{ $questionario->data_limite_assinatura->format('d/m/Y') }}</dd>
        <dt>Abrangência</dt><dd>{{ $questionario->abrangencia === 'todos' ? 'Todos os municípios' : $questionario->municipios->pluck('nome')->implode(', ') }}</dd>
        <dt>Publicação</dt><dd>@if($questionario->publicado_em){{ $questionario->publicado_em->format('d/m/Y H:i') }} por {{ $questionario->publicador?->name }}@else Não publicado @endif</dd>
        @if($questionario->origem)<dt>Duplicado de</dt><dd><a href="{{ route('admin.questionarios.show', $questionario->origem) }}">{{ $questionario->origem->titulo }}</a></dd>@endif
        <dt>Descrição</dt><dd class="texto-pre">{{ $questionario->descricao ?: '—' }}</dd>
        <dt>Apresentação</dt><dd class="texto-pre">{{ $questionario->apresentacao ?: '—' }}</dd>
    </dl>
</div>

<div class="card-titulo"><h2>Perguntas ({{ $estrutura->sum(fn ($b) => count($b['itens'])) }})</h2></div>
@if ($estrutura->isEmpty())
    <div class="alerta aviso">@icon('alerta') <div>Nenhuma pergunta associada. <a href="{{ route('admin.questionarios.perguntas', $questionario) }}">Associar perguntas</a>.</div></div>
@endif
@include('partials.questoes-leitura', ['estrutura' => $estrutura, 'comRespostas' => false])

<dialog class="modal" id="modal-prorrogar">
    <form method="post" action="{{ route('admin.questionarios.prorrogar', $questionario) }}">
        @csrf
        <div class="modal-cab"><h3>Prorrogar prazo de preenchimento</h3><button class="btn btn-link" data-fechar-modal aria-label="Fechar">✕</button></div>
        <div class="modal-corpo form-grade">
            <p class="campo muted">Encerramento atual: <strong>{{ $questionario->data_fim->format('d/m/Y') }}</strong> · Assinatura até <strong>{{ $questionario->data_limite_assinatura->format('d/m/Y') }}</strong>. A operação é registrada em auditoria e os municípios são notificados.</p>
            <div class="campo c6"><label for="p_data_fim" class="obrigatorio">Novo encerramento</label><input id="p_data_fim" type="date" name="data_fim" required min="{{ $questionario->data_fim->copy()->addDay()->format('Y-m-d') }}"></div>
            <div class="campo c6"><label for="p_data_ass" class="obrigatorio">Novo prazo de assinatura</label><input id="p_data_ass" type="date" name="data_limite_assinatura" required></div>
            <div class="campo"><label for="p_just" class="obrigatorio">Justificativa</label><textarea id="p_just" name="justificativa" minlength="10" required></textarea></div>
        </div>
        <div class="modal-rodape"><button class="btn btn-neutro" data-fechar-modal>Cancelar</button><button class="btn btn-primario" type="submit">Prorrogar</button></div>
    </form>
</dialog>
@endsection
