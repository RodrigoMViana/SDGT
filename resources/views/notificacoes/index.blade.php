@extends('layouts.app')
@section('titulo', 'Notificações')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Notificações</h1><div class="subtitulo">Avisos sobre início, prazos e encerramento dos questionários.</div></div>
    <form method="post" action="{{ route('notificacoes.marcar-todas') }}" class="acoes">@csrf
        <button class="btn btn-neutro" type="submit">@icon('check') Marcar todas como lidas</button></form>
</div>
<div class="abas">
    <a href="{{ route('notificacoes.index') }}" @class(['ativo' => request('filtro') !== 'nao_lidas'])>Todas</a>
    <a href="{{ route('notificacoes.index', ['filtro' => 'nao_lidas']) }}" @class(['ativo' => request('filtro') === 'nao_lidas'])>Não lidas</a>
</div>
<div class="card" style="padding:0">
    <ul class="lista-notificacoes">
        @forelse ($notificacoes as $n)
            <li @class(['lida' => $n->lida_em])>
                <a href="{{ route('notificacoes.show', $n) }}">
                    <span class="ponto" aria-hidden="true"></span>
                    <span style="flex:1">
                        <strong>{{ $n->titulo }}</strong><br>
                        <span class="muted texto-pequeno">{{ $n->tipoLabel() }} · {{ $n->created_at->format('d/m/Y H:i') }}</span>
                    </span>
                    @unless($n->lida_em)<span class="etiqueta azul">Nova</span>@endunless
                </a>
            </li>
        @empty
            <li class="vazio" style="padding:32px">Nenhuma notificação.</li>
        @endforelse
    </ul>
</div>
{{ $notificacoes->links() }}
@endsection
