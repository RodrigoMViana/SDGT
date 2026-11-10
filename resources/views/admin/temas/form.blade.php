@extends('layouts.app')
@section('titulo', $tema->exists ? 'Editar tema' : 'Cadastrar tema')
@section('conteudo')
<div class="migalhas"><a href="{{ route('admin.temas.index') }}">Temas de avaliação</a> › {{ $tema->exists ? 'Editar' : 'Cadastrar' }}</div>
<h1>{{ $tema->exists ? 'Editar tema de avaliação' : 'Cadastrar tema de avaliação' }}</h1>
<form method="post" action="{{ $tema->exists ? route('admin.temas.update', $tema) : route('admin.temas.store') }}" class="card">
    @csrf @if($tema->exists) @method('put') @endif
    <div class="form-grade">
        <div class="campo c8 @error('nome') com-erro @enderror"><label for="nome" class="obrigatorio">Nome</label>
            <input id="nome" name="nome" type="text" maxlength="150" value="{{ old('nome', $tema->nome) }}" required></div>
        <div class="campo c4"><label for="ordem" class="obrigatorio">Ordem de exibição</label>
            <input id="ordem" name="ordem" type="number" min="0" max="999" value="{{ old('ordem', $tema->ordem) }}" required></div>
        <div class="campo"><label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" maxlength="2000">{{ old('descricao', $tema->descricao) }}</textarea></div>
        <div class="campo"><label class="check"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $tema->ativo))> Tema ativo (disponível para novas perguntas)</label></div>
    </div>
    <div class="acoes" style="margin-top:20px">
        <button class="btn btn-primario" type="submit">Salvar</button>
        <a class="btn btn-neutro" href="{{ route('admin.temas.index') }}">Cancelar</a>
    </div>
</form>
@endsection
