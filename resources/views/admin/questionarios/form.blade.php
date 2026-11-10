@extends('layouts.app')
@section('titulo', $questionario->exists ? 'Editar questionário' : 'Cadastrar questionário')
@section('conteudo')
<div class="migalhas"><a href="{{ route('admin.questionarios.index') }}">Questionários</a> › {{ $questionario->exists ? 'Editar' : 'Cadastrar' }}</div>
<h1>{{ $questionario->exists ? 'Editar questionário' : 'Cadastrar questionário' }}</h1>
<form method="post" action="{{ $questionario->exists ? route('admin.questionarios.update', $questionario) : route('admin.questionarios.store') }}" class="card">
    @csrf @if($questionario->exists) @method('put') @endif
    <div class="form-grade">
        <div class="campo c8"><label for="titulo" class="obrigatorio">Título</label>
            <input id="titulo" name="titulo" type="text" maxlength="200" value="{{ old('titulo', $questionario->titulo) }}" required></div>
        <div class="campo c4"><label for="ciclo" class="obrigatorio">Ciclo / exercício</label>
            <input id="ciclo" name="ciclo" type="text" maxlength="20" value="{{ old('ciclo', $questionario->ciclo) }}" required></div>
        <div class="campo"><label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" maxlength="5000">{{ old('descricao', $questionario->descricao) }}</textarea></div>
        <div class="campo"><label for="apresentacao">Apresentação inicial (normativos, portarias e contextualização da obrigatoriedade)</label>
            <textarea id="apresentacao" name="apresentacao" maxlength="20000" style="min-height:160px">{{ old('apresentacao', $questionario->apresentacao) }}</textarea>
            <span class="ajuda-campo">Exibida ao município antes do início do preenchimento.</span></div>
        <div class="campo c4"><label for="data_inicio" class="obrigatorio">Início do preenchimento</label>
            <input id="data_inicio" name="data_inicio" type="date" value="{{ old('data_inicio', $questionario->data_inicio?->format('Y-m-d')) }}" required></div>
        <div class="campo c4"><label for="data_fim" class="obrigatorio">Encerramento do preenchimento</label>
            <input id="data_fim" name="data_fim" type="date" value="{{ old('data_fim', $questionario->data_fim?->format('Y-m-d')) }}" required>
            <span class="ajuda-campo">Padrão: {{ config('sdgt.prazos.dias_preenchimento') }} dias corridos.</span></div>
        <div class="campo c4"><label for="data_limite_assinatura">Prazo final para validação/assinatura</label>
            <input id="data_limite_assinatura" name="data_limite_assinatura" type="date" value="{{ old('data_limite_assinatura', $questionario->data_limite_assinatura?->format('Y-m-d')) }}">
            <span class="ajuda-campo">Em branco: encerramento + {{ config('sdgt.prazos.dias_uteis_assinatura') }} dia(s) útil(eis) (Calendário Oficial).</span></div>
        <div class="campo"><span class="rotulo-campo obrigatorio">Abrangência</span>
            <label class="check"><input type="radio" name="abrangencia" value="todos" data-abrangencia @checked(old('abrangencia', $questionario->abrangencia) === 'todos')> Todos os municípios jurisdicionados</label>
            <label class="check"><input type="radio" name="abrangencia" value="selecionados" data-abrangencia @checked(old('abrangencia', $questionario->abrangencia) === 'selecionados')> Somente municípios selecionados (questionário personalizado)</label>
        </div>
        <div class="campo" id="box-municipios" @if(old('abrangencia', $questionario->abrangencia) !== 'selecionados') hidden @endif>
            <label for="municipios">Municípios (Ctrl/Cmd + clique para selecionar vários)</label>
            <select id="municipios" name="municipios[]" multiple>
                @foreach ($municipios as $m)<option value="{{ $m->id }}" @selected(in_array($m->id, old('municipios', $selecionados)))>{{ $m->nome }}</option>@endforeach
            </select></div>
    </div>
    <div class="acoes" style="margin-top:20px">
        <button class="btn btn-primario" type="submit">{{ $questionario->exists ? 'Salvar' : 'Salvar e associar perguntas' }}</button>
        <a class="btn btn-neutro" href="{{ $questionario->exists ? route('admin.questionarios.show', $questionario) : route('admin.questionarios.index') }}">Cancelar</a>
    </div>
</form>
@endsection
