@extends('layouts.app')
@section('titulo', $preenchimento->municipio->nome)
@section('conteudo')
<div class="migalhas"><a href="{{ route('tribunal.painel', ['questionario_id' => $preenchimento->questionario_id]) }}">Painel</a> ›
    <a href="{{ route('tribunal.relatorios.resultados', ['questionario_id' => $preenchimento->questionario_id]) }}">Resultados</a> › {{ $preenchimento->municipio->nome }}</div>
<div class="cabecalho-pagina">
    <div><h1>{{ $preenchimento->municipio->nome }}</h1><div class="subtitulo">{{ $preenchimento->questionario->titulo }} · {{ $preenchimento->municipio->relatoria?->nome }}</div></div>
    <div class="acoes"><a class="btn btn-secundario" href="{{ route('tribunal.preenchimentos.pdf', $preenchimento) }}">@icon('download') Baixar PDF</a></div>
</div>
@include('partials.resumo-preenchimento')
@include('partials.questoes-leitura', ['estrutura' => $estrutura, 'comRespostas' => true])
@endsection
