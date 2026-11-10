<?php

namespace App\Http\Controllers\Municipio;

use App\Http\Controllers\Controller;
use App\Models\MaterialApoio;
use App\Models\Preenchimento;
use App\Models\Questionario;
use App\Services\PreenchimentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** HU05.01 — Listar questionários do município */
class QuestionarioController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $questionarios = Questionario::paraMunicipio($user->municipio_id)
            ->where('data_inicio', '<=', today()->addDays(30))
            ->orderByDesc('data_inicio')->get();
        $preenchimentos = Preenchimento::where('municipio_id', $user->municipio_id)
            ->whereIn('questionario_id', $questionarios->pluck('id'))->get()->keyBy('questionario_id');

        return view('municipio.questionarios.index', compact('questionarios', 'preenchimentos'));
    }

    /** Apresentação inicial: normativos, portarias e contextualização (Memória 03/11/2026, item 1.8). */
    public function apresentacao(Request $request, Questionario $questionario, PreenchimentoService $service): View
    {
        $user = $request->user();
        abort_unless($questionario->status !== 'rascunho' && $questionario->abrangeMunicipio($user->municipio_id), 404);

        return view('municipio.questionarios.apresentacao', [
            'questionario' => $questionario,
            'preenchimento' => Preenchimento::where('questionario_id', $questionario->id)->where('municipio_id', $user->municipio_id)->first(),
            'estrutura' => $service->estrutura($questionario, null),
            'materiais' => MaterialApoio::where('ativo', true)->orderBy('categoria')->limit(10)->get(),
        ]);
    }

    /** HU05.02 — Iniciar preenchimento */
    public function iniciar(Request $request, Questionario $questionario, PreenchimentoService $service): RedirectResponse
    {
        $user = $request->user();
        abort_unless($questionario->status !== 'rascunho' && $questionario->abrangeMunicipio($user->municipio_id), 404);
        if (! $questionario->aceitaPreenchimento()) {
            return back()->with('erro', 'O questionário não está no período de preenchimento.');
        }
        $p = $service->obterOuCriar($questionario, $user->municipio_id, $user);

        return redirect()->route('municipio.preenchimentos.edit', $p);
    }
}
