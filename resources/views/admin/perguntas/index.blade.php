@extends('layouts.app')
@section('titulo', 'Perguntas')
@section('conteudo')
<div class="cabecalho-pagina">
    <div><h1>Perguntas e respostas</h1><div class="subtitulo">Banco de perguntas reutilizáveis entre questionários.</div></div>
    <a href="{{ route('admin.perguntas.create') }}" class="btn btn-primario">@icon('mais') Cadastrar pergunta</a>
</div>
<form class="card filtros" method="get">
    <div class="campo"><label for="busca">Buscar</label><input id="busca" type="search" name="busca" value="{{ request('busca') }}" placeholder="Código ou enunciado"></div>
    <div class="campo"><label for="tema_id">Tema</label>
        <select id="tema_id" name="tema_id"><option value="">Todos</option>
            @foreach ($temas as $t)<option value="{{ $t->id }}" @selected(request('tema_id') == $t->id)>{{ $t->nome }}</option>@endforeach</select></div>
    <div class="campo curto"><label for="tipo">Tipo</label>
        <select id="tipo" name="tipo"><option value="">Todos</option>
            @foreach (\App\Models\Pergunta::TIPOS as $k => $v)<option value="{{ $k }}" @selected(request('tipo') === $k)>{{ $v }}</option>@endforeach</select></div>
    <button class="btn btn-secundario" type="submit">@icon('filtro') Filtrar</button>
</form>
<div class="tabela-wrap">
    <table class="tabela">
        <thead><tr><th>Código</th><th>Enunciado</th><th>Tema</th><th>Tipo</th><th class="num">Respostas</th><th>Regras</th><th class="acoes-col">Ações</th></tr></thead>
        <tbody>
        @forelse ($perguntas as $p)
            <tr>
                <td><span class="etiqueta azul">{{ $p->codigo }}</span></td>
                <td>{{ \Illuminate\Support\Str::limit($p->enunciado, 120) }}</td>
                <td class="muted">{{ $p->tema?->nome }}</td>
                <td>{{ $p->tipoLabel() }}</td>
                <td class="num">{{ $p->temOpcoes() ? $p->opcoes_count : '—' }}</td>
                <td>
                    @if($p->obrigatoria)<span class="etiqueta cinza">Obrigatória</span>@endif
                    @if($p->pergunta_pai_id)<span class="etiqueta amarelo">Condicional</span>@endif
                    @unless($p->ativo)<span class="etiqueta vermelho">Inativa</span>@endunless
                </td>
                <td class="acoes-col">
                    <a class="btn btn-link" href="{{ route('admin.perguntas.show', $p) }}">@icon('olho') Respostas</a>
                    <a class="btn btn-link" href="{{ route('admin.perguntas.edit', $p) }}">@icon('editar') Editar</a>
                    <form method="post" action="{{ route('admin.perguntas.destroy', $p) }}" style="display:inline" data-confirm="Excluir a pergunta {{ $p->codigo }}?">
                        @csrf @method('delete')<button class="btn btn-link perigo">@icon('lixo') Excluir</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="vazio">Nenhuma pergunta encontrada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $perguntas->links() }}
@endsection
