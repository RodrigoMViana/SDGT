@extends('layouts.app')
@section('titulo', $questionario->titulo)
@section('conteudo')
<div class="migalhas"><a href="{{ route('municipio.questionarios.index') }}">Meus questionários</a> › Apresentação</div>
<div class="cabecalho-pagina">
    <div><h1>{{ $questionario->titulo }}</h1>
        <div class="subtitulo">Preenchimento de {{ $questionario->data_inicio->format('d/m/Y') }} a {{ $questionario->data_fim->format('d/m/Y') }} · validação/assinatura até {{ $questionario->data_limite_assinatura->format('d/m/Y') }}</div></div>
</div>
<div class="card">
    <h3>Apresentação</h3>
    <div class="texto-pre">{{ $questionario->apresentacao ?: 'Sem texto de apresentação.' }}</div>
    @if ($materiais->isNotEmpty())
        <h4 style="margin-top:16px">Materiais de apoio</h4>
        <ul>@foreach ($materiais as $m)<li><a href="{{ route('materiais.download', $m) }}" @if($m->url_externa && ! $m->arquivo_caminho) target="_blank" rel="noopener" @endif>{{ $m->titulo }}</a> <span class="muted texto-pequeno">({{ $m->categoriaLabel() }})</span></li>@endforeach</ul>
    @endif
    <div class="acoes" style="margin-top:16px">
        @if ($preenchimento)
            <a class="btn btn-primario" href="{{ $preenchimento->editavel() && auth()->user()->temPerfil('responsavel') ? route('municipio.preenchimentos.edit', $preenchimento) : route('municipio.preenchimentos.show', $preenchimento) }}">Abrir preenchimento</a>
        @elseif (auth()->user()->temPerfil('responsavel') && $questionario->aceitaPreenchimento())
            <form method="post" action="{{ route('municipio.questionarios.iniciar', $questionario) }}">@csrf
                <button class="btn btn-primario" type="submit">Li as orientações — iniciar preenchimento</button></form>
        @elseif ($questionario->situacao() === 'agendado')
            <span class="etiqueta azul">O preenchimento inicia em {{ $questionario->data_inicio->format('d/m/Y') }}</span>
        @endif
        <a class="btn btn-neutro" href="{{ route('municipio.questionarios.index') }}">@icon('voltar') Voltar</a>
    </div>
</div>
<div class="card-titulo"><h2>Perguntas do questionário</h2><span class="muted texto-pequeno">Todas as perguntas são exibidas para que o município prepare previamente os documentos comprobatórios.</span></div>
@include('partials.questoes-leitura', ['estrutura' => $estrutura, 'comRespostas' => false])
@endsection
