<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Abstração do repositório de arquivos. Usa os "disks" do Laravel (config/filesystems.php):
 * hoje "local"; quando a API de Gerenciamento de Arquivos do TCE/TO estiver disponível,
 * basta registrar um disk customizado (Storage::extend) e apontar SDGT_EVIDENCIAS_DISK /
 * SDGT_MATERIAIS_DISK para ele — sem alterar controllers. (Integração pendente.)
 */
class ArquivoService
{
    /** @return array{caminho: string, nome: string, mime: ?string, tamanho: int, hash: string} */
    public function armazenar(UploadedFile $arquivo, string $pasta, string $disk): array
    {
        $ext = strtolower($arquivo->getClientOriginalExtension() ?: $arquivo->extension() ?: 'bin');
        $nome = now()->format('YmdHis').'_'.Str::random(12).'.'.$ext;
        $caminho = $arquivo->storeAs($pasta, $nome, ['disk' => $disk]);

        return [
            'caminho' => $caminho,
            'nome' => $arquivo->getClientOriginalName(),
            'mime' => $arquivo->getClientMimeType(),
            'tamanho' => (int) $arquivo->getSize(),
            'hash' => hash_file('sha256', $arquivo->getRealPath()),
        ];
    }

    public function download(string $disk, string $caminho, string $nome): StreamedResponse
    {
        abort_unless(Storage::disk($disk)->exists($caminho), 404, 'Arquivo não encontrado no repositório.');

        return Storage::disk($disk)->download($caminho, $nome);
    }

    public function remover(string $disk, ?string $caminho): void
    {
        if ($caminho) {
            Storage::disk($disk)->delete($caminho);
        }
    }
}
