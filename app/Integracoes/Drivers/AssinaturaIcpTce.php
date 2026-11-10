<?php

namespace App\Integracoes\Drivers;

use App\Integracoes\Contracts\AssinaturaDigital;
use App\Models\Preenchimento;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * INTEGRAÇÃO PENDENTE — Serviço de assinatura digital ICP-Brasil disponibilizado pelo TCE/TO.
 * (Manutenção do serviço está fora do escopo — Documento de Visão, item 7.)
 * Contrato presumido (a confirmar):
 *   POST {ASSINATURA_ICP_URL}/assinaturas  {"cpf","documento_base64","referencia"}
 *   → {"id","hash","certificado_titular","certificado_emissor","assinado_em"}
 */
class AssinaturaIcpTce implements AssinaturaDigital
{
    public function assinar(Preenchimento $preenchimento, User $signatario, string $conteudo): array
    {
        $url = config('sdgt.integracoes.assinatura.url');
        if (! $url) {
            throw new RuntimeException('Serviço de assinatura ICP-Brasil não configurado (ASSINATURA_ICP_URL).');
        }
        $resp = Http::withToken((string) config('sdgt.integracoes.assinatura.token'))->timeout(30)
            ->post(rtrim($url, '/').'/assinaturas', [
                'cpf' => $signatario->cpf,
                'documento_base64' => base64_encode($conteudo),
                'referencia' => 'SDGT-PREENCHIMENTO-'.$preenchimento->id,
            ])->throw()->json();

        return [
            'metodo' => 'icp_brasil',
            'hash' => $resp['hash'],
            'detalhes' => sprintf('Assinatura ICP-Brasil id %s — titular %s — emissor %s — %s',
                $resp['id'], $resp['certificado_titular'] ?? '-', $resp['certificado_emissor'] ?? '-', $resp['assinado_em'] ?? now()),
        ];
    }
}
