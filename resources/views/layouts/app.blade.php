<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Início') · SDGT — {{ config('sdgt.orgao.sigla') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/sdgt.css') }}?v=1">
    @stack('head')
</head>
<body>
@php($u = auth()->user())
@if (config('sdgt.login_simulado') && ! app()->isProduction())
    <div class="faixa-demo">Ambiente de {{ app()->environment() }} — integrações em modo simulado.</div>
@endif
<header class="topo">
    <button class="acao-topo btn-menu" data-menu aria-label="Abrir menu">@icon('menu')</button>
    <a href="{{ route('home') }}" class="marca">
        <span class="brasao" aria-hidden="true">{{ str_replace('/', '', config('sdgt.orgao.sigla')) }}</span>
        <span class="orgao">{!! str_replace(' do Estado', '<br>do Estado', e(config('sdgt.orgao.nome'))) !!}</span>
        <span class="sistema">{{ config('sdgt.orgao.sistema') }}</span>
    </a>
    <span class="espaco"></span>
    <a href="{{ route('notificacoes.index') }}" class="acao-topo" title="Notificações" aria-label="Notificações ({{ $notificacoesNaoLidas }} não lidas)">
        @icon('sino')
        @if ($notificacoesNaoLidas > 0)<span class="contador" data-testid="contador-notificacoes">{{ $notificacoesNaoLidas > 99 ? '99+' : $notificacoesNaoLidas }}</span>@endif
    </a>
    <div class="usuario">
        <strong>{{ $u->name }}</strong>
        <span>{{ $u->perfil->label() }}@if($u->municipio) · {{ $u->municipio->nome }}@endif</span>
    </div>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="sair" type="submit">Sair</button></form>
</header>
<div class="estrutura">
    <nav class="lateral" aria-label="Menu principal">
        @if ($u->isTribunal())
            <div class="grupo">Painel corporativo</div>
            <a href="{{ route('tribunal.painel') }}" @class(['ativo' => request()->routeIs('tribunal.painel')])>@icon('painel') Panorama consolidado</a>
            <a href="{{ route('tribunal.painel.comparar') }}" @class(['ativo' => request()->routeIs('tribunal.painel.comparar')])>@icon('comparar') Comparar municípios</a>
            <a href="{{ route('tribunal.painel.relatorias') }}" @class(['ativo' => request()->routeIs('tribunal.painel.relatorias')])>@icon('relatoria') Desempenho por relatoria</a>
            <a href="{{ route('tribunal.painel.evolucao') }}" @class(['ativo' => request()->routeIs('tribunal.painel.evolucao')])>@icon('evolucao') Evolução da adequação</a>
            <a href="{{ route('tribunal.relatorios.resultados') }}" @class(['ativo' => request()->routeIs('tribunal.relatorios.*')])>@icon('relatorio') Relatório de resultados</a>
        @endif
        @if ($u->temPerfil('administrador'))
            <div class="grupo">Diagnóstico</div>
            <a href="{{ route('admin.questionarios.index') }}" @class(['ativo' => request()->routeIs('admin.questionarios.*')])>@icon('questionario') Questionários</a>
            <a href="{{ route('admin.perguntas.index') }}" @class(['ativo' => request()->routeIs('admin.perguntas.*')])>@icon('pergunta') Perguntas e respostas</a>
            <a href="{{ route('admin.temas.index') }}" @class(['ativo' => request()->routeIs('admin.temas.*')])>@icon('tema') Temas de avaliação</a>
            <div class="grupo">Administração</div>
            <a href="{{ route('admin.auditoria.index') }}" @class(['ativo' => request()->routeIs('admin.auditoria.*')])>@icon('auditoria') Auditoria</a>
            <a href="{{ route('admin.usuarios.index') }}" @class(['ativo' => request()->routeIs('admin.usuarios.*')])>@icon('usuarios') Usuários</a>
            <a href="{{ route('admin.municipios.index') }}" @class(['ativo' => request()->routeIs('admin.municipios.*')])>@icon('municipio') Municípios e relatorias</a>
        @endif
        @if ($u->isMunicipal())
            <div class="grupo">{{ $u->municipio?->nome }}</div>
            <a href="{{ route('municipio.questionarios.index') }}" @class(['ativo' => request()->routeIs('municipio.*')])>@icon('questionario') Meus questionários</a>
        @endif
        <div class="grupo">Geral</div>
        <a href="{{ route('materiais.index') }}" @class(['ativo' => request()->routeIs('materiais.*') || request()->routeIs('admin.materiais.*')])>@icon('material') Materiais de apoio</a>
        <a href="{{ route('notificacoes.index') }}" @class(['ativo' => request()->routeIs('notificacoes.*')])>@icon('sino') Notificações
            @if ($notificacoesNaoLidas > 0)<span class="badge-menu">{{ $notificacoesNaoLidas }}</span>@endif</a>
    </nav>
    <main class="conteudo" id="conteudo">
        @include('partials.alertas')
        @yield('conteudo')
        <div class="rodape">{{ config('sdgt.orgao.nome') }} · SDGT v{{ config('app.versao', '1.0.0') }}</div>
    </main>
</div>
<script src="{{ asset('vendor/chart.umd.min.js') }}"></script>
<script src="{{ asset('js/sdgt.js') }}?v=1"></script>
@stack('scripts')
</body>
</html>
