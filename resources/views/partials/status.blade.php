{{-- Uso: @include('partials.status', ['tipo' => 'preenchimento'|'questionario'|'faixa', 'valor' => ...]) --}}
@php
    $mapas = [
        'preenchimento' => [
            'nao_iniciado' => ['cinza', 'Não iniciado'], 'em_preenchimento' => ['azul', 'Em preenchimento'],
            'aguardando_validacao' => ['amarelo', 'Aguardando validação'], 'devolvido' => ['vermelho', 'Devolvido para ajustes'],
            'validado' => ['verde', 'Validado'],
        ],
        'questionario' => [
            'rascunho' => ['cinza', 'Rascunho'], 'agendado' => ['azul', 'Publicado — aguardando início'], 'aberto' => ['verde', 'Em preenchimento'],
            'assinatura' => ['amarelo', 'Período de assinatura'], 'encerrado' => ['vermelho', 'Encerrado'],
        ],
        'faixa' => [
            'baixa' => ['vermelho', 'Baixa aderência'], 'media' => ['amarelo', 'Média aderência'], 'alta' => ['verde', 'Alta aderência'], '' => ['cinza', 'Sem índice'],
        ],
    ];
    [$cor, $texto] = $mapas[$tipo][$valor ?? ''] ?? ['cinza', $valor ?? '—'];
@endphp
<span class="etiqueta {{ $cor }}">{{ $texto }}</span>
