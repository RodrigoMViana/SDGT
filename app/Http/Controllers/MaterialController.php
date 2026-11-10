<?php

namespace App\Http\Controllers;

use App\Models\MaterialApoio;
use App\Services\ArquivoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** HU11.04 Listar e HU11.05 Baixar materiais de apoio */
class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $q = MaterialApoio::query()->orderBy('categoria')->orderBy('titulo');
        if (! $request->user()->temPerfil('administrador')) {
            $q->where('ativo', true);
        }
        if ($request->filled('categoria')) {
            $q->where('categoria', $request->query('categoria'));
        }
        if ($request->filled('busca')) {
            $q->where('titulo', 'like', '%'.$request->query('busca').'%');
        }

        return view('materiais.index', ['materiais' => $q->paginate(20)->withQueryString()]);
    }

    public function download(Request $request, MaterialApoio $material, ArquivoService $arquivos)
    {
        abort_if(! $material->ativo && ! $request->user()->temPerfil('administrador'), 404);
        if ($material->url_externa && ! $material->arquivo_caminho) {
            return redirect()->away($material->url_externa);
        }

        return $arquivos->download(config('sdgt.materiais.disk'), $material->arquivo_caminho, $material->arquivo_nome);
    }
}
