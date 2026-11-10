<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpcaoResposta;
use App\Models\Pergunta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** EP04 — Gestão das Respostas (HU04.06 listar na tela da pergunta, HU04.07 a HU04.09) */
class OpcaoRespostaController extends Controller
{
    public function store(Request $request, Pergunta $pergunta): RedirectResponse
    {
        if ($r = $this->bloqueio($pergunta)) {
            return $r;
        }
        abort_unless($pergunta->temOpcoes(), 422, 'Este tipo de pergunta não possui opções de resposta.');
        $dados = $this->validar($request);
        $dados['ordem'] ??= (int) $pergunta->opcoes()->max('ordem') + 1;
        $pergunta->opcoes()->create($dados);

        return back()->with('sucesso', 'Resposta cadastrada.');
    }

    public function update(Request $request, OpcaoResposta $opcao): RedirectResponse
    {
        if ($r = $this->bloqueio($opcao->pergunta)) {
            return $r;
        }
        $opcao->update($this->validar($request));

        return back()->with('sucesso', 'Resposta atualizada.');
    }

    public function destroy(OpcaoResposta $opcao): RedirectResponse
    {
        if ($r = $this->bloqueio($opcao->pergunta)) {
            return $r;
        }
        DB::transaction(function () use ($opcao) {
            DB::table('pergunta_dependencias')->where('opcao_resposta_id', $opcao->id)->delete();
            $opcao->delete();
        });

        return back()->with('sucesso', 'Resposta excluída.');
    }

    private function bloqueio(Pergunta $pergunta): ?RedirectResponse
    {
        return $pergunta->bloqueadaParaEdicao()
            ? back()->with('erro', 'A pergunta está em questionário publicado; as respostas não podem ser alteradas.')
            : null;
    }

    private function validar(Request $request): array
    {
        $dados = $request->validate([
            'texto' => ['required', 'string', 'max:500'],
            'pontuacao' => ['required', 'numeric', 'min:-100', 'max:100'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        $dados['solicita_evidencia'] = $request->boolean('solicita_evidencia');

        return $dados;
    }
}
