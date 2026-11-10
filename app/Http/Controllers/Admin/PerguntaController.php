<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpcaoResposta;
use App\Models\Pergunta;
use App\Models\PreenchimentoResposta;
use App\Models\Tema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** EP04 — Gestão das Perguntas (HU04.01 a HU04.05) */
class PerguntaController extends Controller
{
    public function index(Request $request): View
    {
        $perguntas = Pergunta::with('tema')->withCount('opcoes')
            ->when($request->filled('tema_id'), fn ($q) => $q->where('tema_id', $request->query('tema_id')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->query('tipo')))
            ->when($request->filled('busca'), fn ($q) => $q->where(fn ($w) => $w
                ->where('enunciado', 'like', '%'.$request->query('busca').'%')
                ->orWhere('codigo', 'like', '%'.$request->query('busca').'%')))
            ->orderBy('tema_id')->orderBy('codigo')->paginate(25)->withQueryString();

        return view('admin.perguntas.index', ['perguntas' => $perguntas, 'temas' => Tema::orderBy('ordem')->get()]);
    }

    public function create(): View
    {
        return view('admin.perguntas.form', [
            'pergunta' => new Pergunta(['obrigatoria' => true, 'ativo' => true, 'peso' => 1, 'tipo' => 'unica']),
            'temas' => Tema::ativos()->orderBy('ordem')->get(),
            'bloqueada' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pergunta = Pergunta::create($this->validar($request));

        return redirect()->route('admin.perguntas.show', $pergunta)
            ->with('sucesso', 'Pergunta cadastrada.'.($pergunta->temOpcoes() ? ' Cadastre agora as respostas (opções).' : ''));
    }

    public function show(Pergunta $pergunta): View
    {
        $pergunta->load(['tema', 'opcoes', 'perguntaPai.opcoes', 'opcoesHabilitadoras', 'dependentes', 'questionarios']);
        $candidatasPai = Pergunta::whereIn('tipo', ['unica', 'multipla'])->where('id', '!=', $pergunta->id)
            ->with('opcoes')->orderBy('codigo')->get();

        return view('admin.perguntas.show', [
            'pergunta' => $pergunta,
            'candidatasPai' => $candidatasPai,
            'bloqueada' => $pergunta->bloqueadaParaEdicao(),
        ]);
    }

    public function edit(Pergunta $pergunta): View
    {
        return view('admin.perguntas.form', [
            'pergunta' => $pergunta,
            'temas' => Tema::orderBy('ordem')->get(),
            'bloqueada' => $pergunta->bloqueadaParaEdicao(),
        ]);
    }

    public function update(Request $request, Pergunta $pergunta): RedirectResponse
    {
        if ($pergunta->bloqueadaParaEdicao()) {
            // Em uso por questionário publicado: apenas textos de orientação podem ser ajustados.
            $pergunta->update($request->validate([
                'ajuda' => ['nullable', 'string', 'max:5000'],
                'link_apoio' => ['nullable', 'url', 'max:500'],
            ]));

            return redirect()->route('admin.perguntas.show', $pergunta)
                ->with('sucesso', 'Orientações atualizadas. Demais campos bloqueados: a pergunta está em questionário publicado.');
        }
        $dados = $this->validar($request, $pergunta);
        if (! in_array($dados['tipo'], ['unica', 'multipla'], true) && $pergunta->opcoes()->exists()) {
            return back()->withInput()->with('erro', 'Exclua as respostas (opções) antes de alterar para um tipo sem opções.');
        }
        $pergunta->update($dados);

        return redirect()->route('admin.perguntas.show', $pergunta)->with('sucesso', 'Pergunta atualizada.');
    }

    public function destroy(Pergunta $pergunta): RedirectResponse
    {
        if ($pergunta->questionarios()->exists()) {
            return back()->with('erro', 'Não é possível excluir: a pergunta está associada a questionário(s). Remova a associação ou inative-a.');
        }
        if ($pergunta->dependentes()->exists()) {
            return back()->with('erro', 'Não é possível excluir: outras perguntas dependem desta. Remova as dependências antes.');
        }
        if (PreenchimentoResposta::where('pergunta_id', $pergunta->id)->exists()) {
            return back()->with('erro', 'Não é possível excluir: a pergunta já possui respostas de municípios.');
        }
        DB::transaction(function () use ($pergunta) {
            DB::table('pergunta_dependencias')->whereIn('opcao_resposta_id', $pergunta->opcoes()->pluck('id'))->delete();
            $pergunta->delete();
        });

        return redirect()->route('admin.perguntas.index')->with('sucesso', 'Pergunta excluída.');
    }

    /** HU04.05 — Configurar dependência entre perguntas */
    public function dependencia(Request $request, Pergunta $pergunta): RedirectResponse
    {
        if ($pergunta->bloqueadaParaEdicao()) {
            return back()->with('erro', 'A pergunta está em questionário publicado; a dependência não pode ser alterada.');
        }
        if (! $request->filled('pergunta_pai_id')) {
            $pergunta->opcoesHabilitadoras()->detach();
            $pergunta->update(['pergunta_pai_id' => null]);

            return back()->with('sucesso', 'Dependência removida.');
        }
        $dados = $request->validate([
            'pergunta_pai_id' => ['required', 'integer', Rule::exists('perguntas', 'id')->whereIn('tipo', ['unica', 'multipla']), Rule::notIn([$pergunta->id])],
            'opcoes' => ['required', 'array', 'min:1'],
            'opcoes.*' => ['integer'],
        ], ['opcoes.required' => 'Selecione ao menos uma resposta que habilita esta pergunta.']);

        // evita ciclos (A depende de B que depende de A)
        $atual = Pergunta::find($dados['pergunta_pai_id']);
        for ($i = 0; $atual && $i < 20; $i++) {
            if ($atual->id === $pergunta->id) {
                return back()->with('erro', 'Dependência circular não permitida.');
            }
            $atual = $atual->perguntaPai;
        }
        $validas = OpcaoResposta::where('pergunta_id', $dados['pergunta_pai_id'])->whereIn('id', $dados['opcoes'])->pluck('id');
        if ($validas->isEmpty()) {
            return back()->with('erro', 'As respostas selecionadas não pertencem à pergunta condicionante.');
        }
        DB::transaction(function () use ($pergunta, $dados, $validas) {
            $pergunta->update(['pergunta_pai_id' => $dados['pergunta_pai_id']]);
            $pergunta->opcoesHabilitadoras()->sync($validas);
        });

        return back()->with('sucesso', 'Dependência configurada: a pergunta ficará visível, mas só poderá ser respondida quando a condição for atendida.');
    }

    private function validar(Request $request, ?Pergunta $pergunta = null): array
    {
        $dados = $request->validate([
            'tema_id' => ['required', 'exists:temas,id'],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('perguntas', 'codigo')->ignore($pergunta?->id)],
            'enunciado' => ['required', 'string', 'max:2000'],
            'ajuda' => ['nullable', 'string', 'max:5000'],
            'link_apoio' => ['nullable', 'url', 'max:500'],
            'tipo' => ['required', Rule::in(array_keys(Pergunta::TIPOS))],
            'peso' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $dados['obrigatoria'] = $request->boolean('obrigatoria');
        $dados['exige_evidencia'] = $request->boolean('exige_evidencia');
        $dados['ativo'] = $request->boolean('ativo');

        return $dados;
    }
}
