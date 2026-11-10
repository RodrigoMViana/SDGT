<?php

namespace App\Http\Controllers;

use App\Models\Evidencia;
use App\Models\Preenchimento;
use App\Services\ArquivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** EP06 — Gestão de Evidências (anexar, excluir, baixar) */
class EvidenciaController extends Controller
{
    public function __construct(private ArquivoService $arquivos) {}

    public function store(Request $request, Preenchimento $preenchimento): RedirectResponse
    {
        $this->autorizarMunicipio($request, $preenchimento);
        abort_unless($preenchimento->editavel(), 403, 'O questionário não está aberto para alterações.');

        $dados = $request->validate([
            'pergunta_id' => ['required', 'integer'],
            'arquivo' => ['required', 'file', 'max:'.config('sdgt.evidencias.tamanho_max_kb'),
                'mimes:'.implode(',', config('sdgt.evidencias.extensoes'))],
        ], [], ['arquivo' => 'arquivo']);
        abort_unless($preenchimento->questionario->perguntas()->where('perguntas.id', $dados['pergunta_id'])->exists(), 422);

        $info = $this->arquivos->armazenar($request->file('arquivo'),
            "evidencias/{$preenchimento->questionario_id}/{$preenchimento->municipio_id}", config('sdgt.evidencias.disk'));

        Evidencia::create([
            'preenchimento_id' => $preenchimento->id,
            'pergunta_id' => $dados['pergunta_id'],
            'nome_original' => $info['nome'],
            'caminho' => $info['caminho'],
            'mime' => $info['mime'],
            'tamanho' => $info['tamanho'],
            'hash_sha256' => $info['hash'],
            'enviado_por' => $request->user()->id,
        ]);

        $aviso = str_starts_with((string) $info['mime'], 'image/')
            ? ' Recomendação do Tribunal: prefira documentos em PDF textual (pesquisável) em vez de imagens.' : '';

        return redirect()->to(route('municipio.preenchimentos.edit', $preenchimento).'#pergunta-'.$dados['pergunta_id'])
            ->with('sucesso', 'Documento anexado com sucesso.'.$aviso);
    }

    public function destroy(Request $request, Evidencia $evidencia): RedirectResponse
    {
        $p = $evidencia->preenchimento;
        $this->autorizarMunicipio($request, $p);
        abort_unless($p->editavel(), 403, 'O questionário não está aberto para alterações.');
        $this->arquivos->remover(config('sdgt.evidencias.disk'), $evidencia->caminho);
        $perguntaId = $evidencia->pergunta_id;
        $evidencia->delete();

        return redirect()->to(route('municipio.preenchimentos.edit', $p).'#pergunta-'.$perguntaId)
            ->with('sucesso', 'Documento excluído.');
    }

    public function download(Request $request, Evidencia $evidencia)
    {
        $user = $request->user();
        if (! $user->isTribunal()) {
            $this->autorizarMunicipio($request, $evidencia->preenchimento);
        }

        return $this->arquivos->download(config('sdgt.evidencias.disk'), $evidencia->caminho, $evidencia->nome_original);
    }

    private function autorizarMunicipio(Request $request, Preenchimento $p): void
    {
        abort_unless($request->user()->municipio_id === $p->municipio_id, 403, 'Documento de outro município.');
    }
}
