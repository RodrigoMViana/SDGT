@extends('layouts.app')
@section('titulo', $pergunta->exists ? 'Editar pergunta' : 'Cadastrar pergunta')
@section('conteudo')
<div class="migalhas"><a href="{{ route('admin.perguntas.index') }}">Perguntas</a> › {{ $pergunta->exists ? $pergunta->codigo : 'Cadastrar' }}</div>
<h1>{{ $pergunta->exists ? 'Editar pergunta '.$pergunta->codigo : 'Cadastrar pergunta' }}</h1>
@if ($bloqueada)
    <div class="alerta aviso">@icon('cadeado') <div>Esta pergunta integra questionário publicado. Para preservar a consistência das respostas já coletadas, somente a <strong>ajuda contextual</strong> e o <strong>link de apoio</strong> podem ser alterados. Para mudar o conteúdo, cadastre uma nova pergunta.</div></div>
@endif
<form method="post" action="{{ $pergunta->exists ? route('admin.perguntas.update', $pergunta) : route('admin.perguntas.store') }}" class="card">
    @csrf @if($pergunta->exists) @method('put') @endif
    <div class="form-grade">
        <div class="campo c3"><label for="codigo" class="obrigatorio">Código</label>
            <input id="codigo" name="codigo" type="text" maxlength="20" value="{{ old('codigo', $pergunta->codigo) }}" @disabled($bloqueada) required placeholder="Ex.: LT07"></div>
        <div class="campo c6"><label for="tema_id" class="obrigatorio">Tema de avaliação</label>
            <select id="tema_id" name="tema_id" @disabled($bloqueada) required>
                <option value="">Selecione…</option>
                @foreach ($temas as $t)<option value="{{ $t->id }}" @selected(old('tema_id', $pergunta->tema_id) == $t->id)>{{ $t->nome }}</option>@endforeach
            </select></div>
        <div class="campo c3"><label for="tipo" class="obrigatorio">Tipo de resposta</label>
            <select id="tipo" name="tipo" @disabled($bloqueada) required>
                @foreach (\App\Models\Pergunta::TIPOS as $k => $v)<option value="{{ $k }}" @selected(old('tipo', $pergunta->tipo) === $k)>{{ $v }}</option>@endforeach
            </select></div>
        <div class="campo"><label for="enunciado" class="obrigatorio">Enunciado</label>
            <textarea id="enunciado" name="enunciado" maxlength="2000" @disabled($bloqueada) required>{{ old('enunciado', $pergunta->enunciado) }}</textarea></div>
        <div class="campo"><label for="ajuda">Ajuda contextual (orientação ao município)</label>
            <textarea id="ajuda" name="ajuda" maxlength="5000">{{ old('ajuda', $pergunta->ajuda) }}</textarea>
            <span class="ajuda-campo">Exibida junto à pergunta durante o preenchimento.</span></div>
        <div class="campo c8"><label for="link_apoio">Link para cartilha/material de apoio</label>
            <input id="link_apoio" name="link_apoio" type="url" maxlength="500" value="{{ old('link_apoio', $pergunta->link_apoio) }}" placeholder="https://"></div>
        <div class="campo c4"><label for="peso" class="obrigatorio">Peso no índice</label>
            <input id="peso" name="peso" type="number" step="0.01" min="0" max="100" value="{{ old('peso', $pergunta->peso) }}" @disabled($bloqueada) required>
            <span class="ajuda-campo">0 = informativa (não pontua).</span></div>
        <div class="campo c4"><label class="check"><input type="checkbox" name="obrigatoria" value="1" @checked(old('obrigatoria', $pergunta->obrigatoria)) @disabled($bloqueada)> Resposta obrigatória</label></div>
        <div class="campo c4"><label class="check"><input type="checkbox" name="exige_evidencia" value="1" @checked(old('exige_evidencia', $pergunta->exige_evidencia)) @disabled($bloqueada)> Sempre exigir evidência</label>
            <span class="ajuda-campo">Ou marque "solicita evidência" em respostas específicas.</span></div>
        <div class="campo c4"><label class="check"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $pergunta->ativo)) @disabled($bloqueada)> Pergunta ativa</label></div>
    </div>
    <div class="acoes" style="margin-top:20px">
        <button class="btn btn-primario" type="submit">Salvar</button>
        <a class="btn btn-neutro" href="{{ $pergunta->exists ? route('admin.perguntas.show', $pergunta) : route('admin.perguntas.index') }}">Cancelar</a>
    </div>
</form>
@endsection
