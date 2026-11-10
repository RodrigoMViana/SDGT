@extends('layouts.app')
@section('titulo', 'Pergunta '.$pergunta->codigo)
@section('conteudo')
<div class="migalhas"><a href="{{ route('admin.perguntas.index') }}">Perguntas</a> › {{ $pergunta->codigo }}</div>
<div class="cabecalho-pagina">
    <div><h1>Pergunta {{ $pergunta->codigo }}</h1><div class="subtitulo">{{ $pergunta->tema?->nome }} · {{ $pergunta->tipoLabel() }}</div></div>
    <div class="acoes"><a href="{{ route('admin.perguntas.edit', $pergunta) }}" class="btn btn-secundario">@icon('editar') Editar pergunta</a></div>
</div>
@if ($bloqueada)
    <div class="alerta aviso">@icon('cadeado') <div>Pergunta em uso por questionário publicado: respostas e dependência estão bloqueadas para alteração.</div></div>
@endif
<div class="card">
    <p style="font-size:var(--fs-md);font-weight:600">{{ $pergunta->enunciado }}</p>
    <dl class="dl">
        <dt>Obrigatória</dt><dd>{{ $pergunta->obrigatoria ? 'Sim' : 'Não' }}</dd>
        <dt>Peso no índice</dt><dd>{{ number_format($pergunta->peso, 2, ',', '.') }}</dd>
        <dt>Evidência</dt><dd>{{ $pergunta->exige_evidencia ? 'Sempre exigida' : 'Conforme a resposta selecionada' }}</dd>
        <dt>Ajuda contextual</dt><dd>{{ $pergunta->ajuda ?: '—' }}</dd>
        <dt>Link de apoio</dt><dd>@if($pergunta->link_apoio)<a href="{{ $pergunta->link_apoio }}" target="_blank" rel="noopener">{{ $pergunta->link_apoio }}</a>@else — @endif</dd>
        <dt>Questionários</dt><dd>{{ $pergunta->questionarios->pluck('titulo')->implode('; ') ?: 'Nenhum' }}</dd>
    </dl>
</div>

@if ($pergunta->temOpcoes())
<div class="card">
    <div class="card-titulo"><h3>Respostas (opções)</h3><span class="muted texto-pequeno">Pontuação máxima: {{ number_format($pergunta->pontuacaoMaxima(), 2, ',', '.') }}</span></div>
    <div class="tabela-wrap">
        <table class="tabela">
            <thead><tr><th class="num">Ordem</th><th>Texto da resposta</th><th class="num">Pontuação</th><th>Solicita evidência</th><th class="acoes-col">Ações</th></tr></thead>
            <tbody>
            @forelse ($pergunta->opcoes as $o)
                <tr>
                    <td colspan="5" style="padding:6px 8px">
                        <form method="post" action="{{ route('admin.opcoes.update', $o) }}" class="filtros" style="gap:8px;align-items:center">
                            @csrf @method('put')
                            <input aria-label="Ordem" type="number" name="ordem" value="{{ $o->ordem }}" style="width:70px" @disabled($bloqueada)>
                            <input aria-label="Texto" type="text" name="texto" value="{{ $o->texto }}" style="flex:1;min-width:220px" @disabled($bloqueada) required>
                            <input aria-label="Pontuação" type="number" step="0.01" name="pontuacao" value="{{ $o->pontuacao }}" style="width:100px" @disabled($bloqueada) required>
                            <label class="check"><input type="checkbox" name="solicita_evidencia" value="1" @checked($o->solicita_evidencia) @disabled($bloqueada)> Solicita evidência</label>
                            @unless($bloqueada)
                                <button class="btn btn-link" type="submit">Salvar</button>
                            @endunless
                        </form>
                        @unless($bloqueada)
                        <form method="post" action="{{ route('admin.opcoes.destroy', $o) }}" style="display:inline" data-confirm="Excluir a resposta &quot;{{ $o->texto }}&quot;?">
                            @csrf @method('delete')<button class="btn btn-link perigo btn-pequeno">@icon('lixo') Excluir resposta</button></form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="vazio">Nenhuma resposta cadastrada. Cadastre ao menos duas opções.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @unless($bloqueada)
    <h4 style="margin-top:20px">Cadastrar nova resposta</h4>
    <form method="post" action="{{ route('admin.opcoes.store', $pergunta) }}" class="filtros">
        @csrf
        <div class="campo"><label for="nova_texto" class="obrigatorio">Texto</label><input id="nova_texto" type="text" name="texto" maxlength="500" required></div>
        <div class="campo curto"><label for="nova_pontuacao" class="obrigatorio">Pontuação</label><input id="nova_pontuacao" type="number" step="0.01" name="pontuacao" value="0" required></div>
        <div class="campo curto"><label class="check" style="margin-bottom:10px"><input type="checkbox" name="solicita_evidencia" value="1"> Solicita evidência</label></div>
        <button class="btn btn-primario" type="submit">@icon('mais') Adicionar</button>
    </form>
    @endunless
</div>
@endif

<div class="card">
    <div class="card-titulo"><h3>Dependência entre perguntas</h3></div>
    <p class="muted">A pergunta permanece <strong>visível</strong> ao município, mas só fica <strong>habilitada para resposta</strong> quando a pergunta condicionante recebe uma das respostas selecionadas abaixo.</p>
    @if ($pergunta->perguntaPai)
        <div class="alerta info">@icon('info') <div>Atualmente condicionada a <strong>{{ $pergunta->perguntaPai->codigo }}</strong> = {{ $pergunta->opcoesHabilitadoras->pluck('texto')->implode(' ou ') }}.</div></div>
    @endif
    <form method="post" action="{{ route('admin.perguntas.dependencia', $pergunta) }}">
        @csrf @method('put')
        <div class="form-grade">
            <div class="campo c6"><label for="pergunta_pai_id">Pergunta condicionante</label>
                <select id="pergunta_pai_id" name="pergunta_pai_id" @disabled($bloqueada)>
                    <option value="">— Sem dependência —</option>
                    @foreach ($candidatasPai as $c)
                        <option value="{{ $c->id }}" @selected($pergunta->pergunta_pai_id === $c->id)>{{ $c->codigo }} — {{ \Illuminate\Support\Str::limit($c->enunciado, 80) }}</option>
                    @endforeach
                </select></div>
            <div class="campo c6"><span class="rotulo-campo">Respostas que habilitam esta pergunta</span>
                @foreach ($candidatasPai as $c)
                    <div class="opcoes-pai" data-pai="{{ $c->id }}" @if($pergunta->pergunta_pai_id !== $c->id) hidden @endif>
                        @foreach ($c->opcoes as $o)
                            <label class="check"><input type="checkbox" name="opcoes[]" value="{{ $o->id }}" @checked($pergunta->opcoesHabilitadoras->contains('id', $o->id)) @disabled($bloqueada || $pergunta->pergunta_pai_id !== $c->id)> {{ $o->texto }}</label>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @unless($bloqueada)<div class="acoes" style="margin-top:16px"><button class="btn btn-primario" type="submit">Salvar dependência</button></div>@endunless
    </form>
    @if ($pergunta->dependentes->isNotEmpty())
        <p class="muted texto-pequeno" style="margin-top:12px">Perguntas que dependem desta: {{ $pergunta->dependentes->pluck('codigo')->implode(', ') }}</p>
    @endif
</div>
@endsection
@push('scripts')
<script>
document.getElementById('pergunta_pai_id').addEventListener('change', function () {
    var v = this.value;
    document.querySelectorAll('.opcoes-pai').forEach(function (d) {
        var on = d.getAttribute('data-pai') === v;
        d.hidden = !on;
        d.querySelectorAll('input').forEach(function (i) { i.disabled = !on; if (!on) i.checked = false; });
    });
});
</script>
@endpush
