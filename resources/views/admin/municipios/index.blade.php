@extends('layouts.app')
@section('titulo', 'Municípios e relatorias')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Municípios e relatorias</h1><div class="subtitulo">Municípios jurisdicionados (fonte IBGE) e vinculação à relatoria responsável.</div></div>
</div>
<div class="alerta aviso">@icon('alerta') <div>A distribuição inicial dos municípios por relatoria é <strong>provisória</strong>. Ajuste conforme a distribuição oficial do Tribunal.</div></div>
<form class="card filtros" method="get">
    <div class="campo"><label for="busca">Município</label><input id="busca" type="search" name="busca" value="{{ request('busca') }}"></div>
    <div class="campo curto"><label for="relatoria_id">Relatoria</label>
        <select id="relatoria_id" name="relatoria_id"><option value="">Todas</option>@foreach ($relatorias as $r)<option value="{{ $r->id }}" @selected(request('relatoria_id') == $r->id)>{{ $r->nome }}</option>@endforeach</select></div>
    <button class="btn btn-secundario" type="submit">@icon('filtro') Filtrar</button>
</form>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Código IBGE</th><th>Município</th><th>Região imediata</th><th class="num">Usuários</th><th>Relatoria</th></tr></thead>
        <tbody>
        @foreach ($municipios as $m)
            <tr>
                <td>{{ $m->codigo_ibge }}</td>
                <td><strong>{{ $m->nome }}</strong></td>
                <td>{{ $m->regiao_imediata }}</td>
                <td class="num">{{ $m->usuarios_count }}</td>
                <td>
                    <form method="post" action="{{ route('admin.municipios.update', $m) }}" class="filtros" style="gap:6px;flex-wrap:nowrap">
                        @csrf @method('put')
                        <select name="relatoria_id" aria-label="Relatoria de {{ $m->nome }}" data-auto-submit style="min-width:150px">
                            <option value="">—</option>@foreach ($relatorias as $r)<option value="{{ $r->id }}" @selected($m->relatoria_id === $r->id)>{{ $r->nome }}</option>@endforeach
                        </select>
                        <noscript><button class="btn btn-link">Salvar</button></noscript>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $municipios->links() }}
@endsection
