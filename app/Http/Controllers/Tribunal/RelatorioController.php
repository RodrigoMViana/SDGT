<?php

namespace App\Http\Controllers\Tribunal;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\Preenchimento;
use App\Models\Questionario;
use App\Models\Relatoria;
use App\Models\User;
use App\Services\PainelService;
use App\Services\PontuacaoService;
use App\Support\Exportador;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** EP08 — Relatórios Gerenciais (HU08.01 a HU08.03) */
class RelatorioController extends Controller
{
    public function __construct(private PainelService $painel) {}

    public function resultados(Request $request): View
    {
        [$q, $filtros, $linhas] = $this->dados($request);

        return view('tribunal.relatorios.resultados', [
            'questionario' => $q,
            'questionarios' => $this->painel->questionariosPublicados(),
            'relatorias' => Relatoria::orderBy('nome')->get(),
            'regioes' => Municipio::query()->whereNotNull('regiao_imediata')->distinct()->orderBy('regiao_imediata')->pluck('regiao_imediata'),
            'filtros' => $filtros,
            'linhas' => $linhas,
            'temas' => $q ? $this->painel->temasDoQuestionario($q) : collect(),
        ]);
    }

    public function pdf(Request $request)
    {
        [$q, $filtros, $linhas] = $this->dados($request);
        abort_unless($q, 404);

        return Exportador::pdf('pdf.resultados', [
            'questionario' => $q, 'filtros' => $filtros, 'linhas' => $linhas,
            'temas' => $this->painel->temasDoQuestionario($q),
        ], 'resultados-'.$q->id.'-'.now()->format('Ymd-His').'.pdf', 'landscape');
    }

    public function xlsx(Request $request)
    {
        [$q, , $linhas] = $this->dados($request);
        abort_unless($q, 404);
        $temas = $this->painel->temasDoQuestionario($q);
        $cab = array_merge(['Código IBGE', 'Município', 'Relatoria', 'Região imediata', 'Situação', 'Índice de aderência (%)', 'Faixa'],
            $temas->map(fn ($t) => $t->nome.' (%)')->all(), ['Finalizado em', 'Validado em', 'Responsável', 'Gestor']);

        $responsaveis = User::whereIn('id', $linhas->pluck('preenchimento.responsavel_id')->merge($linhas->pluck('preenchimento.gestor_id'))->filter())->pluck('name', 'id');
        $rows = $linhas->map(fn ($l) => array_merge([
            $l->municipio->codigo_ibge, $l->municipio->nome, $l->municipio->relatoria?->nome, $l->municipio->regiao_imediata,
            Preenchimento::STATUS[$l->status] ?? $l->status, $l->indice, PontuacaoService::faixaLabel($l->faixa),
        ], $temas->map(fn ($t) => $l->por_tema[$t->id] ?? null)->all(), [
            $l->preenchimento?->finalizado_em?->format('d/m/Y H:i'), $l->preenchimento?->validado_em?->format('d/m/Y H:i'),
            $responsaveis[$l->preenchimento?->responsavel_id] ?? null, $responsaveis[$l->preenchimento?->gestor_id] ?? null,
        ]));

        return Exportador::xlsx('Resultados - '.$q->ciclo, $cab, $rows, 'resultados-'.$q->id.'-'.now()->format('Ymd-His').'.xlsx');
    }

    private function dados(Request $request): array
    {
        $q = $request->filled('questionario_id')
            ? Questionario::publicados()->find($request->integer('questionario_id'))
            : $this->painel->questionarioPadrao();
        $filtros = array_filter($request->only(['relatoria_id', 'regiao', 'faixa', 'status']));

        return [$q, $filtros, $q ? $this->painel->linhas($q, $filtros) : collect()];
    }
}
