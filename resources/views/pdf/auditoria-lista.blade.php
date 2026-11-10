@extends('pdf.layout')
@section('titulo', 'Auditoria')
@section('conteudo')
<h1>Registros de auditoria — publicação e prorrogação</h1>
<p class="muted">Filtros: {{ collect($filtros)->filter()->map(fn ($v, $k) => $k.': '.$v)->implode('; ') ?: 'nenhum' }} · {{ $registros->count() }} registro(s)</p>
<table>
    <thead><tr><th>#</th><th>Data/hora</th><th>Ação</th><th>Questionário</th><th>Usuário</th><th>Justificativa</th><th>Alteração</th></tr></thead>
    <tbody>
    @foreach ($registros as $a)
        <tr><td>{{ $a->id }}</td><td>{{ $a->created_at?->format('d/m/Y H:i') }}</td><td>{{ $a->acaoLabel() }}</td><td>{{ $a->entidade_descricao }}</td>
            <td>{{ $a->user_nome }}</td><td>{{ $a->justificativa }}</td>
            <td style="font-size:7.5pt">@foreach (($a->dados_novos ?? []) as $k => $v){{ $k }}: {{ is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}<br>@endforeach</td></tr>
    @endforeach
    </tbody>
</table>
@endsection
