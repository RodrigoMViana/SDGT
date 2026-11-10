<form method="get" class="card filtros">
    <div class="campo"><label for="questionario_id">Questionário / ciclo</label>
        <select id="questionario_id" name="questionario_id" data-auto-submit>
            @foreach ($questionarios as $opt)<option value="{{ $opt->id }}" @selected($questionario?->id === $opt->id)>{{ $opt->titulo }} — {{ $opt->situacaoLabel() }}</option>@endforeach
        </select></div>
    {{ $slot ?? '' }}
    <noscript><button class="btn btn-secundario">Aplicar</button></noscript>
</form>
