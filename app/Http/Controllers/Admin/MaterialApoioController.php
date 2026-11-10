<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialApoio;
use App\Services\ArquivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** EP11 — Gestão de Materiais de Apoio (HU11.01 a HU11.03) */
class MaterialApoioController extends Controller
{
    public function __construct(private ArquivoService $arquivos) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('materiais.index');
    }

    public function create(): View
    {
        return view('admin.materiais.form', ['material' => new MaterialApoio(['ativo' => true, 'categoria' => 'cartilha'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request, true);
        MaterialApoio::create($dados + ['criado_por' => $request->user()->id]);

        return redirect()->route('materiais.index')->with('sucesso', 'Material de apoio cadastrado.');
    }

    public function edit(MaterialApoio $material): View
    {
        return view('admin.materiais.form', compact('material'));
    }

    public function update(Request $request, MaterialApoio $material): RedirectResponse
    {
        $dados = $this->validar($request, false, $material);
        if (isset($dados['arquivo_caminho'])) {
            $this->arquivos->remover(config('sdgt.materiais.disk'), $material->arquivo_caminho);
        }
        $material->update($dados);

        return redirect()->route('materiais.index')->with('sucesso', 'Material de apoio atualizado.');
    }

    public function destroy(MaterialApoio $material): RedirectResponse
    {
        $this->arquivos->remover(config('sdgt.materiais.disk'), $material->arquivo_caminho);
        $material->delete();

        return redirect()->route('materiais.index')->with('sucesso', 'Material de apoio excluído.');
    }

    private function validar(Request $request, bool $novo, ?MaterialApoio $material = null): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'categoria' => ['required', Rule::in(array_keys(MaterialApoio::CATEGORIAS))],
            'url_externa' => ['nullable', 'url', 'max:500'],
            'arquivo' => ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,odt,xls,xlsx,ods,ppt,pptx,zip'],
        ]);
        $temArquivo = $request->hasFile('arquivo') || ($material && $material->arquivo_caminho);
        if (! $temArquivo && empty($dados['url_externa'])) {
            throw ValidationException::withMessages(['arquivo' => 'Envie um arquivo ou informe um link externo.']);
        }
        if ($request->hasFile('arquivo')) {
            $info = $this->arquivos->armazenar($request->file('arquivo'), 'materiais', config('sdgt.materiais.disk'));
            $dados += [
                'arquivo_caminho' => $info['caminho'], 'arquivo_nome' => $info['nome'],
                'arquivo_mime' => $info['mime'], 'arquivo_tamanho' => $info['tamanho'],
            ];
        }
        unset($dados['arquivo']);
        $dados['ativo'] = $request->boolean('ativo');

        return $dados;
    }
}
