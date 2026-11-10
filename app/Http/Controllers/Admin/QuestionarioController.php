<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Integracoes\Contracts\CalendarioOficial;
use App\Models\Municipio;
use App\Models\Pergunta;
use App\Models\Preenchimento;
use App\Models\Questionario;
use App\Models\Tema;
use App\Services\PreenchimentoService;
use App\Services\QuestionarioService;
use App\Support\Exportador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** EP03 — Gestão dos Questionários de Diagnóstico (HU03.01 a HU03.10) */
class QuestionarioController extends Controller
{
    public function __construct(private QuestionarioService $service) {}

    public function index(Request $request): View
    {
        $questionarios = Questionario::withCount(['perguntas', 'preenchimentos'])
            ->when($request->filled('busca'), fn ($q) => $q->where('titulo', 'like', '%'.$request->query('busca').'%'))
            ->when($request->filled('ciclo'), fn ($q) => $q->where('ciclo', $request->query('ciclo')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('data_inicio')->paginate(15)->withQueryString();

        $ids = $questionarios->pluck('id');
        $finalizados = Preenchimento::whereIn('questionario_id', $ids)->whereIn('status', ['aguardando_validacao', 'validado'])
            ->selectRaw('questionario_id, count(*) as total')->groupBy('questionario_id')->pluck('total', 'questionario_id');

        return view('admin.questionarios.index', [
            'questionarios' => $questionarios,
            'finalizados' => $finalizados,
            'ciclos' => Questionario::query()->distinct()->orderByDesc('ciclo')->pluck('ciclo'),
        ]);
    }

    public function create(CalendarioOficial $calendario): View
    {
        $inicio = today()->addDay();
        $fim = $inicio->copy()->addDays(config('sdgt.prazos.dias_preenchimento') - 1);

        return view('admin.questionarios.form', [
            'questionario' => new Questionario([
                'ciclo' => (string) now()->year,
                'data_inicio' => $inicio,
                'data_fim' => $fim,
                'data_limite_assinatura' => $calendario->somarDiasUteis($fim, config('sdgt.prazos.dias_uteis_assinatura')),
                'abrangencia' => 'todos',
            ]),
            'municipios' => Municipio::orderBy('nome')->get(),
            'selecionados' => [],
        ]);
    }

    public function store(Request $request, CalendarioOficial $calendario): RedirectResponse
    {
        $dados = $this->validar($request, $calendario);
        $q = DB::transaction(function () use ($dados, $request) {
            $q = Questionario::create($dados + ['status' => 'rascunho', 'criado_por' => $request->user()->id]);
            $q->municipios()->sync($dados['abrangencia'] === 'selecionados' ? $request->input('municipios', []) : []);

            return $q;
        });

        return redirect()->route('admin.questionarios.perguntas', $q)->with('sucesso', 'Questionário cadastrado. Associe agora as perguntas.');
    }

    public function show(Questionario $questionario, PreenchimentoService $preenchimentos): View
    {
        $questionario->load(['municipios', 'publicador', 'origem']);
        $porStatus = Preenchimento::where('questionario_id', $questionario->id)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.questionarios.show', [
            'questionario' => $questionario,
            'estrutura' => $preenchimentos->estrutura($questionario, null),
            'porStatus' => $porStatus,
            'totalMunicipios' => $questionario->municipiosAlvoQuery()->count(),
        ]);
    }

    public function edit(Questionario $questionario): View|RedirectResponse
    {
        if (! $questionario->editavel()) {
            return redirect()->route('admin.questionarios.show', $questionario)
                ->with('erro', 'Questionário publicado não pode ser editado. Utilize "Prorrogar prazo" ou "Duplicar".');
        }

        return view('admin.questionarios.form', [
            'questionario' => $questionario,
            'municipios' => Municipio::orderBy('nome')->get(),
            'selecionados' => $questionario->municipios()->pluck('municipios.id')->all(),
        ]);
    }

    public function update(Request $request, Questionario $questionario, CalendarioOficial $calendario): RedirectResponse
    {
        abort_unless($questionario->editavel(), 403, 'Questionário publicado não pode ser editado.');
        $dados = $this->validar($request, $calendario);
        DB::transaction(function () use ($questionario, $dados, $request) {
            $questionario->update($dados);
            $questionario->municipios()->sync($dados['abrangencia'] === 'selecionados' ? $request->input('municipios', []) : []);
        });

        return redirect()->route('admin.questionarios.show', $questionario)->with('sucesso', 'Questionário atualizado.');
    }

    public function destroy(Questionario $questionario): RedirectResponse
    {
        if (! $questionario->editavel() || $questionario->preenchimentos()->exists()) {
            return back()->with('erro', 'Somente questionários em rascunho e sem preenchimentos podem ser excluídos.');
        }
        DB::transaction(function () use ($questionario) {
            Questionario::where('origem_id', $questionario->id)->update(['origem_id' => null]);
            $questionario->delete();
        });

        return redirect()->route('admin.questionarios.index')->with('sucesso', 'Questionário excluído.');
    }

    /** HU03.10 — Associar perguntas ao questionário */
    public function perguntas(Questionario $questionario): View
    {
        $temas = Tema::with(['perguntas' => fn ($q) => $q->where('ativo', true)->orderBy('codigo')->with('perguntaPai')])
            ->orderBy('ordem')->get();

        return view('admin.questionarios.perguntas', [
            'questionario' => $questionario,
            'temas' => $temas,
            'associadas' => $questionario->perguntas()->pluck('questionario_pergunta.ordem', 'perguntas.id')->all(),
        ]);
    }

    public function associarPerguntas(Request $request, Questionario $questionario): RedirectResponse
    {
        abort_unless($questionario->editavel(), 403, 'Questionário publicado não pode ter perguntas alteradas.');
        $dados = $request->validate(['perguntas' => ['array'], 'perguntas.*' => ['integer', 'exists:perguntas,id'], 'ordem' => ['array']]);
        $ids = array_map('intval', $dados['perguntas'] ?? []);

        // garante que perguntas condicionantes (pai) também estejam no questionário
        $faltantes = Pergunta::whereIn('id', $ids)->whereNotNull('pergunta_pai_id')->whereNotIn('pergunta_pai_id', $ids)->pluck('codigo');
        if ($faltantes->isNotEmpty()) {
            return back()->withInput()->with('erro', 'As perguntas '.$faltantes->implode(', ').' dependem de outras perguntas que não foram selecionadas.');
        }

        $ordem = $request->input('ordem', []);
        $sync = [];
        $temaOrdem = Tema::pluck('ordem', 'id');
        $perguntas = Pergunta::whereIn('id', $ids)->get()->sortBy(fn ($p) => [
            (int) ($ordem[$p->id] ?? 9999), $temaOrdem[$p->tema_id] ?? 0, $p->codigo,
        ])->values();
        foreach ($perguntas as $i => $p) {
            $sync[$p->id] = ['ordem' => $i + 1];
        }
        $questionario->perguntas()->sync($sync);

        return redirect()->route('admin.questionarios.show', $questionario)->with('sucesso', count($sync).' pergunta(s) associada(s) ao questionário.');
    }

    /** HU03.06 — Publicar */
    public function publicar(Request $request, Questionario $questionario): RedirectResponse
    {
        $this->service->publicar($questionario, $request->user());

        return back()->with('sucesso', 'Questionário publicado. Os municípios foram/serão notificados no início do período.');
    }

    /** HU03.09 — Prorrogar prazo */
    public function prorrogar(Request $request, Questionario $questionario): RedirectResponse
    {
        $dados = $request->validate([
            'data_fim' => ['required', 'date'],
            'data_limite_assinatura' => ['required', 'date'],
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $this->service->prorrogar($questionario, $request->user(), Carbon::parse($dados['data_fim']),
            Carbon::parse($dados['data_limite_assinatura']), $dados['justificativa']);

        return back()->with('sucesso', 'Prazo prorrogado e registrado em auditoria.');
    }

    /** HU03.08 — Duplicar */
    public function duplicar(Request $request, Questionario $questionario): RedirectResponse
    {
        $novo = $this->service->duplicar($questionario, $request->user());

        return redirect()->route('admin.questionarios.edit', $novo)->with('sucesso', 'Questionário duplicado. Ajuste título, datas e abrangência.');
    }

    /** HU03.04 — Baixar questionário (PDF) */
    public function pdf(Questionario $questionario, PreenchimentoService $preenchimentos)
    {
        return Exportador::pdf('pdf.questionario', [
            'questionario' => $questionario,
            'estrutura' => $preenchimentos->estrutura($questionario, null),
            'preenchimento' => null,
        ], 'questionario-'.$questionario->id.'.pdf');
    }

    private function validar(Request $request, CalendarioOficial $calendario): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'apresentacao' => ['nullable', 'string', 'max:20000'],
            'ciclo' => ['required', 'string', 'max:20'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'data_limite_assinatura' => ['nullable', 'date', 'after_or_equal:data_fim'],
            'abrangencia' => ['required', Rule::in(['todos', 'selecionados'])],
            'municipios' => ['array', 'required_if:abrangencia,selecionados'],
            'municipios.*' => ['integer', 'exists:municipios,id'],
        ], ['municipios.required_if' => 'Selecione os municípios quando a abrangência for "municípios selecionados".']);

        if (empty($dados['data_limite_assinatura'])) {
            $dados['data_limite_assinatura'] = $calendario
                ->somarDiasUteis(Carbon::parse($dados['data_fim']), config('sdgt.prazos.dias_uteis_assinatura'))->toDateString();
        }
        unset($dados['municipios']);

        return $dados;
    }
}
