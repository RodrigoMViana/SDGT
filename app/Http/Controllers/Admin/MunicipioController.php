<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\Relatoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Municípios jurisdicionados e sua vinculação às Relatorias. */
class MunicipioController extends Controller
{
    public function index(Request $request): View
    {
        $municipios = Municipio::with('relatoria')->withCount('usuarios')
            ->when($request->filled('busca'), fn ($q) => $q->where('nome', 'like', '%'.$request->query('busca').'%'))
            ->when($request->filled('relatoria_id'), fn ($q) => $q->where('relatoria_id', $request->query('relatoria_id')))
            ->orderBy('nome')->paginate(30)->withQueryString();

        return view('admin.municipios.index', ['municipios' => $municipios, 'relatorias' => Relatoria::orderBy('nome')->get()]);
    }

    public function update(Request $request, Municipio $municipio): RedirectResponse
    {
        $municipio->update($request->validate(['relatoria_id' => ['nullable', 'exists:relatorias,id']]));

        return back()->with('sucesso', "Relatoria de {$municipio->nome} atualizada.");
    }
}
