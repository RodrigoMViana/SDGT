<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** EP02 — Gestão dos Temas de Avaliação (HU02.01 a HU02.05) */
class TemaController extends Controller
{
    public function index(Request $request): View
    {
        $temas = Tema::withCount('perguntas')
            ->when($request->filled('busca'), fn ($q) => $q->where('nome', 'like', '%'.$request->query('busca').'%'))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->query('ativo') === '1'))
            ->orderBy('ordem')->orderBy('nome')->paginate(20)->withQueryString();

        return view('admin.temas.index', compact('temas'));
    }

    public function create(): View
    {
        return view('admin.temas.form', ['tema' => new Tema(['ativo' => true, 'ordem' => (int) Tema::max('ordem') + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Tema::create($this->validar($request));

        return redirect()->route('admin.temas.index')->with('sucesso', 'Tema de avaliação cadastrado.');
    }

    public function edit(Tema $tema): View
    {
        return view('admin.temas.form', compact('tema'));
    }

    public function update(Request $request, Tema $tema): RedirectResponse
    {
        $tema->update($this->validar($request, $tema));

        return redirect()->route('admin.temas.index')->with('sucesso', 'Tema de avaliação atualizado.');
    }

    public function alternarStatus(Tema $tema): RedirectResponse
    {
        $tema->update(['ativo' => ! $tema->ativo]);

        return back()->with('sucesso', 'Tema '.($tema->ativo ? 'ativado' : 'inativado').'.');
    }

    public function destroy(Tema $tema): RedirectResponse
    {
        if ($tema->perguntas()->exists()) {
            return back()->with('erro', 'Não é possível excluir: o tema possui perguntas vinculadas. Inative-o ou mova as perguntas.');
        }
        $tema->delete();

        return redirect()->route('admin.temas.index')->with('sucesso', 'Tema excluído.');
    }

    private function validar(Request $request, ?Tema $tema = null): array
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150', Rule::unique('temas', 'nome')->ignore($tema?->id)],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'ordem' => ['required', 'integer', 'min:0', 'max:999'],
        ]);
        $dados['ativo'] = $request->boolean('ativo');

        return $dados;
    }
}
