@extends('layouts.app')
@section('titulo', 'Registro de auditoria #'.$auditoria->id)
@section('conteudo')
<div class="migalhas"><a href="{{ route('admin.auditoria.index') }}">Auditoria</a> › Registro #{{ $auditoria->id }}</div>
<div class="cabecalho-pagina">
    <div><h1>Registro de auditoria #{{ $auditoria->id }}</h1><div class="subtitulo">{{ $auditoria->acaoLabel() }}</div></div>
    <div class="acoes"><a class="btn btn-neutro" href="{{ route('admin.auditoria.registro-pdf', $auditoria) }}">@icon('download') Exportar PDF</a></div>
</div>
<div class="card">
    <dl class="dl">
        <dt>Data/hora</dt><dd>{{ $auditoria->created_at?->format('d/m/Y H:i:s') }}</dd>
        <dt>Ação</dt><dd>{{ $auditoria->acaoLabel() }}</dd>
        <dt>Objeto</dt><dd>{{ $auditoria->entidade_tipo }} #{{ $auditoria->entidade_id }} — {{ $auditoria->entidade_descricao }}</dd>
        <dt>Usuário</dt><dd>{{ $auditoria->user_nome }}</dd>
        <dt>Endereço IP</dt><dd>{{ $auditoria->ip ?? '—' }}</dd>
        <dt>Navegador</dt><dd class="texto-pequeno">{{ $auditoria->user_agent ?? '—' }}</dd>
        <dt>Justificativa</dt><dd class="texto-pre">{{ $auditoria->justificativa ?: '—' }}</dd>
    </dl>
</div>
<div class="grade grade-2">
    @foreach (['Dados anteriores' => $auditoria->dados_anteriores, 'Dados novos' => $auditoria->dados_novos] as $titulo => $dados)
        <div class="card"><h3>{{ $titulo }}</h3>
            @if ($dados)
                <dl class="dl">@foreach ($dados as $k => $v)<dt>{{ str_replace('_', ' ', $k) }}</dt><dd>{{ is_scalar($v) || $v === null ? ($v ?? '—') : json_encode($v, JSON_UNESCAPED_UNICODE) }}</dd>@endforeach</dl>
            @else <p class="muted">—</p> @endif
        </div>
    @endforeach
</div>
<a href="{{ route('admin.auditoria.index') }}" class="btn btn-neutro">@icon('voltar') Voltar</a>
@endsection
