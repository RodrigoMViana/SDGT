@extends('pdf.layout')
@section('titulo', 'Resultados')
@section('conteudo')
<h1>Resultados — {{ $questionario->titulo }}</h1>
<p class="muted">Filtros: {{ collect($filtros)->map(fn ($v, $k) => $k.': '.$v)->implode('; ') ?: 'nenhum' }} · {{ $linhas->count() }} município(s) ·
    Concluídos: {{ $linhas->where('concluido', true)->count() }} · Índice médio: {{ ($m = $linhas->where('concluido', true)->avg('indice')) === null ? '—' : number_format($m, 1, ',', '.').'%' }}</p>
<table>
    <thead><tr><th>Município</th><th>Relatoria</th><th>Situação</th><th class="num">Índice</th>@foreach ($temas as $t)<th class="num">{{ \Illuminate\Support\Str::limit($t->nome, 18) }}</th>@endforeach</tr></thead>
    <tbody>
    @foreach ($linhas as $l)
        <tr>
            <td>{{ $l->municipio->nome }}</td><td>{{ $l->municipio->relatoria?->nome }}</td>
            <td>{{ \App\Models\Preenchimento::STATUS[$l->status] ?? $l->status }}</td>
            <td class="num {{ $l->faixa }}"><strong>{{ $l->indice === null ? '—' : number_format($l->indice, 1, ',', '.') }}</strong></td>
            @foreach ($temas as $t)<td class="num">{{ isset($l->por_tema[$t->id]) ? number_format($l->por_tema[$t->id], 1, ',', '.') : '—' }}</td>@endforeach
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
