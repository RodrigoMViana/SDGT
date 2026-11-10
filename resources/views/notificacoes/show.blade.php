@extends('layouts.app')
@section('titulo', $notificacao->titulo)
@section('conteudo')
<div class="migalhas"><a href="{{ route('notificacoes.index') }}">Notificações</a> › Detalhe</div>
<div class="card">
    <div class="card-titulo"><h2>{{ $notificacao->titulo }}</h2><span class="etiqueta azul">{{ $notificacao->tipoLabel() }}</span></div>
    <p class="muted texto-pequeno">Recebida em {{ $notificacao->created_at->format('d/m/Y \à\s H:i') }}
        @if($notificacao->email_enviado_em) · também enviada por e-mail @endif</p>
    <p class="texto-pre">{{ $notificacao->mensagem }}</p>
    <div class="acoes">
        <a href="{{ route('notificacoes.index') }}" class="btn btn-neutro">@icon('voltar') Voltar</a>
        @if ($notificacao->link)<a href="{{ $notificacao->link }}" class="btn btn-primario">Ir para o questionário</a>@endif
    </div>
</div>
@endsection
