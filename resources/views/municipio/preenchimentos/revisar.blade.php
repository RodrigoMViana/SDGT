@extends('layouts.app')
@section('titulo', 'Revisar e finalizar')
@section('conteudo')
@php($q = $preenchimento->questionario)
<div class="migalhas"><a href="{{ route('municipio.questionarios.index') }}">Meus questionários</a> › <a href="{{ route('municipio.preenchimentos.edit', $preenchimento) }}">Preenchimento</a> › Revisão</div>
<div class="cabecalho-pagina">
    <div><h1>Revisar respostas</h1><div class="subtitulo">{{ $q->titulo }} · {{ $preenchimento->municipio->nome }}</div></div>
    <a class="btn btn-neutro" href="{{ route('municipio.preenchimentos.edit', $preenchimento) }}">@icon('voltar') Voltar ao preenchimento</a>
</div>
@if ($pendencias)
    <div class="alerta erro">@icon('alerta') <div><strong>Há {{ count($pendencias) }} pendência(s) que impedem a finalização:</strong>
        <ul>@foreach ($pendencias as $p)<li>{{ $p }}</li>@endforeach</ul></div></div>
@else
    <div class="alerta sucesso">@icon('check') <div>Todas as respostas obrigatórias e evidências exigidas foram informadas. Revise e finalize.</div></div>
@endif
@include('partials.questoes-leitura', ['estrutura' => $estrutura, 'comRespostas' => true])
<form method="post" action="{{ route('municipio.preenchimentos.finalizar', $preenchimento) }}" class="card">
    @csrf
    <div class="declaracao">
        <label class="check"><input type="checkbox" name="declaracao" value="1" required @disabled((bool) $pendencias)>
            <span>Declaro que as informações prestadas são verdadeiras e estão amparadas pelos documentos anexados, e que estou ciente de que, após a finalização, o questionário será encaminhado ao gestor municipal para validação e assinatura.</span></label>
    </div>
    <div class="acoes" style="margin-top:16px">
        <button class="btn btn-primario" type="submit" @disabled((bool) $pendencias)>@icon('check') Finalizar e encaminhar ao gestor</button>
        <a class="btn btn-neutro" href="{{ route('municipio.preenchimentos.edit', $preenchimento) }}">Continuar editando</a>
    </div>
</form>
@endsection
