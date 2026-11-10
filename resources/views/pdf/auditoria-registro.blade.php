@extends('pdf.layout')
@section('titulo', 'Registro de auditoria')
@section('conteudo')
<h1>Registro de auditoria #{{ $auditoria->id }}</h1>
<table class="dl">
    <tr><td>Data/hora</td><td>{{ $auditoria->created_at?->format('d/m/Y H:i:s') }}</td></tr>
    <tr><td>Ação</td><td>{{ $auditoria->acaoLabel() }}</td></tr>
    <tr><td>Objeto</td><td>{{ $auditoria->entidade_tipo }} #{{ $auditoria->entidade_id }} — {{ $auditoria->entidade_descricao }}</td></tr>
    <tr><td>Usuário</td><td>{{ $auditoria->user_nome }}</td></tr>
    <tr><td>IP</td><td>{{ $auditoria->ip ?? '—' }}</td></tr>
    <tr><td>Navegador</td><td>{{ $auditoria->user_agent ?? '—' }}</td></tr>
    <tr><td>Justificativa</td><td>{{ $auditoria->justificativa ?? '—' }}</td></tr>
</table>
@foreach (['Dados anteriores' => $auditoria->dados_anteriores, 'Dados novos' => $auditoria->dados_novos] as $titulo => $dados)
    <h3>{{ $titulo }}</h3>
    <table class="dl">@forelse (($dados ?? []) as $k => $v)<tr><td>{{ str_replace('_', ' ', $k) }}</td><td>{{ is_scalar($v) || $v === null ? ($v ?? '—') : json_encode($v, JSON_UNESCAPED_UNICODE) }}</td></tr>@empty<tr><td colspan="2">—</td></tr>@endforelse</table>
@endforeach
@endsection
