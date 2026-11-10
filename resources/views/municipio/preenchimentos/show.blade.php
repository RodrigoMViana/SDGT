@extends('layouts.app')
@section('titulo', 'Questionário preenchido')
@section('conteudo')
@php($u = auth()->user())
@php($q = $preenchimento->questionario)
<div class="migalhas"><a href="{{ route('municipio.questionarios.index') }}">Meus questionários</a> › Visualizar</div>
<div class="cabecalho-pagina">
    <div><h1>{{ $q->titulo }}</h1><div class="subtitulo">{{ $preenchimento->municipio->nome }} · {{ $q->situacaoLabel() }}</div></div>
    <div class="acoes">
        @if ($u->temPerfil('responsavel') && $preenchimento->editavel())
            <a class="btn btn-primario" href="{{ route('municipio.preenchimentos.edit', $preenchimento) }}">@icon('editar') Continuar preenchimento</a>
        @endif
        @if ($u->temPerfil('gestor') && $preenchimento->validavel())
            <button class="btn btn-sucesso" data-abrir-modal="modal-validar">@icon('assinar') Validar e assinar</button>
            @if ($q->aceitaPreenchimento())<button class="btn btn-neutro" data-abrir-modal="modal-devolver">Devolver para ajustes</button>@endif
        @endif
        <a class="btn btn-neutro" href="{{ route('municipio.preenchimentos.pdf', $preenchimento) }}">@icon('download') Baixar PDF</a>
    </div>
</div>
@if ($u->temPerfil('gestor') && $preenchimento->validavel())
    <div class="alerta aviso">@icon('assinar') <div>O responsável finalizou o preenchimento. Revise as respostas e realize a <strong>validação/assinatura até {{ $q->data_limite_assinatura->format('d/m/Y') }}</strong>.</div></div>
@endif
@include('partials.resumo-preenchimento')
@include('partials.questoes-leitura', ['estrutura' => $estrutura, 'comRespostas' => true])

@if ($u->temPerfil('gestor') && $preenchimento->validavel())
<dialog class="modal" id="modal-validar">
    <form method="post" action="{{ route('municipio.preenchimentos.validar', $preenchimento) }}">
        @csrf
        <div class="modal-cab"><h3>Validar e assinar o questionário</h3><button class="btn btn-link" data-fechar-modal aria-label="Fechar">✕</button></div>
        <div class="modal-corpo">
            <div class="declaracao">
                <label class="check"><input type="checkbox" name="declaracao" value="1" required>
                    <span>Eu, <strong>{{ $u->name }}</strong>, na qualidade de gestor(a) do município de {{ $preenchimento->municipio->nome }}, declaro, sob as penas da lei, que revisei as informações prestadas neste questionário e atesto sua veracidade e consistência.</span></label>
            </div>
            <p class="muted texto-pequeno" style="margin-top:12px">A assinatura registra sua identificação ({{ strtoupper($u->ultimo_metodo_login ?? 'senha') }}), data/hora e o hash do conteúdo das respostas. Após a validação, o questionário não poderá ser alterado.</p>
        </div>
        <div class="modal-rodape"><button class="btn btn-neutro" data-fechar-modal>Cancelar</button><button class="btn btn-sucesso" type="submit">@icon('assinar') Assinar e validar</button></div>
    </form>
</dialog>
<dialog class="modal" id="modal-devolver">
    <form method="post" action="{{ route('municipio.preenchimentos.devolver', $preenchimento) }}">
        @csrf
        <div class="modal-cab"><h3>Devolver para ajustes</h3><button class="btn btn-link" data-fechar-modal aria-label="Fechar">✕</button></div>
        <div class="modal-corpo"><label for="motivo" class="obrigatorio">Motivo / ajustes necessários</label>
            <textarea id="motivo" name="motivo" minlength="10" required></textarea></div>
        <div class="modal-rodape"><button class="btn btn-neutro" data-fechar-modal>Cancelar</button><button class="btn btn-perigo" type="submit">Devolver</button></div>
    </form>
</dialog>
@endif
@endsection
