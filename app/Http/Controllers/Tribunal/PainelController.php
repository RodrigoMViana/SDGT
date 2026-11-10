<?php

namespace App\Http\Controllers\Tribunal;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\Preenchimento;
use App\Models\Questionario;
use App\Services\PainelService;
use App\Services\PreenchimentoService;
use App\Support\Exportador;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** EP07 — Painel Corporativo do Tribunal (HU07.01 a HU07.06) */
class PainelController extends Controller
{
    public function __construct(private PainelService $painel) {}

    /** HU07.01 */
    public function panorama(Request $request): View
    {
        $q = $this->questionario($request);

        return view('tribunal.painel.panorama', [
            'questionario' => $q,
            'questionarios' => $this->painel->questionariosPublicados(),
            'dados' => $q ? $this->painel->panorama($q) : null,
        ]);
    }

    /** HU07.02 */
    public function comparar(Request $request): View
    {
        $q = $this->questionario($request);
        $ids = array_slice(array_filter(array_map('intval', (array) $request->query('municipios', []))), 0, 6);
        $linhas = ($q && $ids) ? $this->painel->linhas($q, ['municipio_ids' => $ids]) : collect();

        return view('tribunal.painel.comparar', [
            'questionario' => $q,
            'questionarios' => $this->painel->questionariosPublicados(),
            'municipios' => Municipio::orderBy('nome')->get(),
            'selecionados' => $ids,
            'linhas' => $linhas,
            'temas' => $q ? $this->painel->temasDoQuestionario($q) : collect(),
        ]);
    }

    /** HU07.03 */
    public function relatorias(Request $request): View
    {
        $q = $this->questionario($request);

        return view('tribunal.painel.relatorias', [
            'questionario' => $q,
            'questionarios' => $this->painel->questionariosPublicados(),
            'relatorias' => $q ? $this->painel->porRelatoria($q) : collect(),
        ]);
    }

    /** HU07.04 */
    public function evolucao(Request $request): View
    {
        $municipioId = $request->integer('municipio_id') ?: null;

        return view('tribunal.painel.evolucao', [
            'serie' => $this->painel->evolucao($municipioId),
            'municipios' => Municipio::orderBy('nome')->get(),
            'municipio' => $municipioId ? Municipio::find($municipioId) : null,
        ]);
    }

    /** HU07.05 */
    public function preenchimento(Preenchimento $preenchimento, PreenchimentoService $service): View
    {
        $preenchimento->load(['questionario', 'municipio.relatoria', 'responsavel', 'gestor']);

        return view('tribunal.preenchimento', [
            'preenchimento' => $preenchimento,
            'estrutura' => $service->estrutura($preenchimento->questionario, $preenchimento),
        ]);
    }

    /** HU07.06 */
    public function preenchimentoPdf(Preenchimento $preenchimento, PreenchimentoService $service)
    {
        return Exportador::pdf('pdf.questionario', [
            'questionario' => $preenchimento->questionario,
            'preenchimento' => $preenchimento->load(['municipio', 'responsavel', 'gestor']),
            'estrutura' => $service->estrutura($preenchimento->questionario, $preenchimento),
        ], 'diagnostico-'.$preenchimento->municipio->codigo_ibge.'-'.$preenchimento->questionario_id.'.pdf');
    }

    private function questionario(Request $request): ?Questionario
    {
        return $request->filled('questionario_id')
            ? Questionario::publicados()->find($request->integer('questionario_id'))
            : $this->painel->questionarioPadrao();
    }
}
