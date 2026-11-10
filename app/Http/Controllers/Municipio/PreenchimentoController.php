<?php

namespace App\Http\Controllers\Municipio;

use App\Http\Controllers\Controller;
use App\Models\Preenchimento;
use App\Services\PreenchimentoService;
use App\Support\Exportador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** EP05 — Preenchimento e Validação (HU05.02 a HU05.07) */
class PreenchimentoController extends Controller
{
    public function __construct(private PreenchimentoService $service) {}

    /** HU05.05 — Visualizar */
    public function show(Request $request, Preenchimento $preenchimento): View
    {
        $this->autorizar($request, $preenchimento);
        $preenchimento->load(['questionario', 'municipio', 'responsavel', 'gestor']);

        return view('municipio.preenchimentos.show', [
            'preenchimento' => $preenchimento,
            'estrutura' => $this->service->estrutura($preenchimento->questionario, $preenchimento),
        ]);
    }

    /** HU05.02 / HU05.03 — Preencher / retomar */
    public function edit(Request $request, Preenchimento $preenchimento): View|RedirectResponse
    {
        $this->autorizar($request, $preenchimento);
        if (! $preenchimento->editavel()) {
            return redirect()->route('municipio.preenchimentos.show', $preenchimento)
                ->with('aviso', 'Este questionário não está disponível para edição (prazo encerrado ou já finalizado).');
        }
        $preenchimento->load(['questionario', 'municipio']);
        $estrutura = $this->service->estrutura($preenchimento->questionario, $preenchimento);
        $total = $estrutura->sum(fn ($t) => count(array_filter($t['itens'], fn ($i) => $i['habilitada'])));
        $respondidas = $estrutura->sum(fn ($t) => count(array_filter($t['itens'], fn ($i) => $i['habilitada']
            && (! empty($i['opcoes_ids']) || ($i['valor'] ?? '') !== ''))));

        return view('municipio.preenchimentos.edit', compact('preenchimento', 'estrutura', 'total', 'respondidas'));
    }

    /** Salvamento parcial */
    public function update(Request $request, Preenchimento $preenchimento): RedirectResponse
    {
        $this->autorizar($request, $preenchimento);
        $this->service->salvar($preenchimento, (array) $request->input('respostas', []), $request->user());

        if ($request->input('acao') === 'revisar') {
            return redirect()->route('municipio.preenchimentos.revisar', $preenchimento);
        }

        return redirect()->to(route('municipio.preenchimentos.edit', $preenchimento).($request->input('ancora') ? '#'.$request->input('ancora') : ''))
            ->with('sucesso', 'Respostas salvas. Você pode continuar depois — o progresso fica gravado.');
    }

    /** HU05.04 — Revisar antes de finalizar */
    public function revisar(Request $request, Preenchimento $preenchimento): View|RedirectResponse
    {
        $this->autorizar($request, $preenchimento);
        if (! $preenchimento->editavel()) {
            return redirect()->route('municipio.preenchimentos.show', $preenchimento);
        }
        $preenchimento->load(['questionario', 'municipio']);

        return view('municipio.preenchimentos.revisar', [
            'preenchimento' => $preenchimento,
            'estrutura' => $this->service->estrutura($preenchimento->questionario, $preenchimento),
            'pendencias' => $this->service->pendencias($preenchimento),
        ]);
    }

    public function finalizar(Request $request, Preenchimento $preenchimento): RedirectResponse
    {
        $this->autorizar($request, $preenchimento);
        $request->validate(['declaracao' => 'accepted'], ['declaracao.accepted' => 'Confirme a declaração de veracidade das informações.']);
        $this->service->finalizar($preenchimento, $request->user());

        return redirect()->route('municipio.preenchimentos.show', $preenchimento)
            ->with('sucesso', 'Preenchimento finalizado e encaminhado ao gestor municipal para validação.');
    }

    /** HU05.07 — Validar (assinatura do gestor) */
    public function validar(Request $request, Preenchimento $preenchimento): RedirectResponse
    {
        $this->autorizar($request, $preenchimento);
        $request->validate(['declaracao' => 'accepted'], ['declaracao.accepted' => 'Confirme a declaração para assinar.']);
        $this->service->validar($preenchimento, $request->user());

        return redirect()->route('municipio.preenchimentos.show', $preenchimento)
            ->with('sucesso', 'Questionário validado e assinado. Obrigado!');
    }

    public function devolver(Request $request, Preenchimento $preenchimento): RedirectResponse
    {
        $this->autorizar($request, $preenchimento);
        $dados = $request->validate(['motivo' => 'required|string|min:10|max:2000']);
        $this->service->devolver($preenchimento, $request->user(), $dados['motivo']);

        return redirect()->route('municipio.preenchimentos.show', $preenchimento)
            ->with('sucesso', 'Questionário devolvido ao responsável para ajustes.');
    }

    /** HU05.06 — Download */
    public function pdf(Request $request, Preenchimento $preenchimento)
    {
        $this->autorizar($request, $preenchimento);

        return Exportador::pdf('pdf.questionario', [
            'questionario' => $preenchimento->questionario,
            'preenchimento' => $preenchimento->load(['municipio', 'responsavel', 'gestor']),
            'estrutura' => $this->service->estrutura($preenchimento->questionario, $preenchimento),
        ], 'diagnostico-'.$preenchimento->municipio->codigo_ibge.'-'.$preenchimento->questionario_id.'.pdf');
    }

    private function autorizar(Request $request, Preenchimento $p): void
    {
        abort_unless($request->user()->municipio_id === $p->municipio_id, 403, 'Questionário de outro município.');
    }
}
