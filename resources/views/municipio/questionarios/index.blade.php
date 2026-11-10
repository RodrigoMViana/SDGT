@extends('layouts.app')
@section('titulo', 'Meus questionários')
@section('conteudo')
@php($u = auth()->user())
<div class="cabecalho-pagina">
    <div><h1>Questionários de diagnóstico</h1><div class="subtitulo">{{ $u->municipio->nome }} · {{ $u->perfil->label() }}</div></div>
</div>
@forelse ($questionarios as $q)
    @php($p = $preenchimentos->get($q->id))
    @php($sit = $q->situacao())
    @php($status = $p?->status ?? 'nao_iniciado')
    <div class="card">
        <div class="cartao-questionario">
            <div class="info">
                <h3 style="margin-bottom:4px">{{ $q->titulo }}</h3>
                <div class="acoes" style="gap:6px">
                    @include('partials.status', ['tipo' => 'questionario', 'valor' => $sit])
                    @include('partials.status', ['tipo' => 'preenchimento', 'valor' => $status])
                    @if ($p && in_array($status, ['aguardando_validacao', 'validado']) && $p->indice_aderencia !== null)
                        <span class="etiqueta contorno faixa-{{ \App\Services\PontuacaoService::faixa($p->indice_aderencia) }}">Índice @indice($p->indice_aderencia)</span>
                    @endif
                </div>
                <div class="linha-tempo">
                    <div class="{{ $sit === 'agendado' ? 'atual' : 'feito' }}">Início<br>{{ $q->data_inicio->format('d/m/Y') }}</div>
                    <div class="{{ $sit === 'aberto' ? 'atual' : (in_array($sit, ['assinatura', 'encerrado']) ? 'feito' : '') }}">Fim do preenchimento<br>{{ $q->data_fim->format('d/m/Y') }}</div>
                    <div class="{{ $sit === 'assinatura' ? 'atual' : ($sit === 'encerrado' ? 'feito' : '') }}">Assinatura até<br>{{ $q->data_limite_assinatura->format('d/m/Y') }}</div>
                </div>
                @if ($status === 'devolvido')<div class="alerta erro texto-pequeno" style="margin:8px 0 0">@icon('alerta') <div>Devolvido pelo gestor: {{ $p->observacao_devolucao }}</div></div>@endif
            </div>
            <div class="acoes">
                @if ($p)
                    @if ($u->temPerfil('responsavel') && $p->editavel())
                        <a class="btn btn-primario" href="{{ route('municipio.preenchimentos.edit', $p) }}">@icon('editar') Continuar preenchimento</a>
                    @endif
                    @if ($u->temPerfil('gestor') && $p->validavel())
                        <a class="btn btn-sucesso" href="{{ route('municipio.preenchimentos.show', $p) }}">@icon('assinar') Revisar e validar</a>
                    @endif
                    <a class="btn btn-neutro" href="{{ route('municipio.preenchimentos.show', $p) }}">@icon('olho') Visualizar</a>
                    <a class="btn btn-neutro" href="{{ route('municipio.preenchimentos.pdf', $p) }}">@icon('download') PDF</a>
                @else
                    @if ($u->temPerfil('responsavel') && $q->aceitaPreenchimento())
                        <a class="btn btn-primario" href="{{ route('municipio.questionarios.apresentacao', $q) }}">Iniciar preenchimento</a>
                    @else
                        <a class="btn btn-neutro" href="{{ route('municipio.questionarios.apresentacao', $q) }}">@icon('olho') Ver questionário</a>
                    @endif
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="card vazio">Nenhum questionário disponível para o município no momento.</div>
@endforelse
@endsection
