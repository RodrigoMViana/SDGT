@extends('layouts.app')
@section('titulo', $material->exists ? 'Editar material' : 'Cadastrar material')
@section('conteudo')
<div class="migalhas"><a href="{{ route('materiais.index') }}">Materiais de apoio</a> › {{ $material->exists ? 'Editar' : 'Cadastrar' }}</div>
<h1>{{ $material->exists ? 'Editar material de apoio' : 'Cadastrar material de apoio' }}</h1>
<form method="post" enctype="multipart/form-data" action="{{ $material->exists ? route('admin.materiais.update', $material) : route('admin.materiais.store') }}" class="card">
    @csrf @if($material->exists) @method('put') @endif
    <div class="form-grade">
        <div class="campo c8"><label for="titulo" class="obrigatorio">Título</label>
            <input id="titulo" name="titulo" type="text" maxlength="200" value="{{ old('titulo', $material->titulo) }}" required></div>
        <div class="campo c4"><label for="categoria" class="obrigatorio">Categoria</label>
            <select id="categoria" name="categoria">@foreach (\App\Models\MaterialApoio::CATEGORIAS as $k => $v)<option value="{{ $k }}" @selected(old('categoria', $material->categoria) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="campo"><label for="descricao">Descrição</label><textarea id="descricao" name="descricao" maxlength="2000">{{ old('descricao', $material->descricao) }}</textarea></div>
        <div class="campo c6"><label for="arquivo">Arquivo (PDF, DOC, XLS, PPT, ZIP — até 50 MB)</label>
            <input id="arquivo" name="arquivo" type="file" accept=".pdf,.doc,.docx,.odt,.xls,.xlsx,.ods,.ppt,.pptx,.zip">
            @if($material->arquivo_nome)<span class="ajuda-campo">Atual: {{ $material->arquivo_nome }} (envie outro para substituir)</span>@endif</div>
        <div class="campo c6"><label for="url_externa">ou link externo</label>
            <input id="url_externa" name="url_externa" type="url" maxlength="500" value="{{ old('url_externa', $material->url_externa) }}" placeholder="https://"></div>
        <div class="campo"><label class="check"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $material->ativo))> Disponível para os municípios</label></div>
    </div>
    <div class="acoes" style="margin-top:20px"><button class="btn btn-primario" type="submit">Salvar</button><a class="btn btn-neutro" href="{{ route('materiais.index') }}">Cancelar</a></div>
</form>
@endsection
