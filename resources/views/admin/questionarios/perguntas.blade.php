@extends('layouts.app')
@section('titulo', 'Associar perguntas')
@section('conteudo')
<div class="migalhas"><a href="{{ route('admin.questionarios.index') }}">Questionários</a> › <a href="{{ route('admin.questionarios.show', $questionario) }}">{{ \Illuminate\Support\Str::limit($questionario->titulo, 50) }}</a> › Perguntas</div>
<div class="cabecalho-pagina">
    <div><h1>Associar perguntas ao questionário</h1><div class="subtitulo">Selecione as perguntas do banco. A ordem segue o tema e o código; ajuste a ordem se necessário.</div></div>
</div>
@unless ($questionario->editavel())
    <div class="alerta aviso">@icon('cadeado') <div>Questionário publicado: a associação de perguntas está bloqueada.</div></div>
@endunless
<form method="post" action="{{ route('admin.questionarios.perguntas.salvar', $questionario) }}">
    @csrf @method('put')
    @foreach ($temas as $tema)
        @continue($tema->perguntas->isEmpty())
        <div class="card">
            <div class="card-titulo">
                <h3>{{ $tema->nome }} @unless($tema->ativo)<span class="etiqueta cinza">Tema inativo</span>@endunless</h3>
                <label class="check texto-pequeno"><input type="checkbox" data-marcar-todos=".pt-{{ $tema->id }}" @disabled(! $questionario->editavel())> Marcar todas</label>
            </div>
            <div class="tabela-wrap">
                <table class="tabela">
                    <thead><tr><th style="width:40px"></th><th style="width:90px">Ordem</th><th>Código</th><th>Enunciado</th><th>Tipo</th></tr></thead>
                    <tbody>
                    @foreach ($tema->perguntas as $p)
                        <tr>
                            <td><input type="checkbox" class="pt-{{ $tema->id }}" name="perguntas[]" value="{{ $p->id }}" aria-label="Selecionar {{ $p->codigo }}"
                                    @checked(array_key_exists($p->id, $associadas)) @disabled(! $questionario->editavel())></td>
                            <td><input type="number" name="ordem[{{ $p->id }}]" value="{{ $associadas[$p->id] ?? '' }}" min="1" style="width:70px" aria-label="Ordem de {{ $p->codigo }}" @disabled(! $questionario->editavel())></td>
                            <td><span class="etiqueta azul">{{ $p->codigo }}</span></td>
                            <td>{{ $p->enunciado }}
                                @if($p->perguntaPai)<br><span class="etiqueta amarelo">Depende de {{ $p->perguntaPai->codigo }}</span>@endif</td>
                            <td class="muted">{{ $p->tipoLabel() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
    @if ($questionario->editavel())
        <div class="acoes"><button class="btn btn-primario" type="submit">Salvar associação</button>
            <a class="btn btn-neutro" href="{{ route('admin.questionarios.show', $questionario) }}">Cancelar</a></div>
    @endif
</form>
@endsection
