<?php

namespace App\Integracoes\Drivers;

use App\Integracoes\Contracts\AssinaturaDigital;
use App\Models\Preenchimento;
use App\Models\User;

/**
 * Assinatura eletrônica simples (Lei 14.063/2020, art. 4º, I): o gestor autenticado
 * (GOV.BR / certificado / senha) confirma a declaração e o sistema gera um hash SHA-256 do
 * conteúdo + identificação do signatário + carimbo de tempo.
 * Para assinatura qualificada ICP-Brasil usar o driver "icp_tce" (integração pendente).
 */
class AssinaturaSimples implements AssinaturaDigital
{
    public function assinar(Preenchimento $preenchimento, User $signatario, string $conteudo): array
    {
        $carimbo = now()->toIso8601String();
        $hash = hash('sha256', $conteudo.'|'.$signatario->id.'|'.$signatario->cpf.'|'.$carimbo);

        return [
            'metodo' => 'eletronica_simples',
            'hash' => $hash,
            'detalhes' => sprintf('Assinado eletronicamente por %s (CPF %s) em %s via %s. Hash SHA-256 do conteúdo: %s',
                $signatario->name, $signatario->cpfFormatado() ?? '-', now()->format('d/m/Y H:i:s'),
                strtoupper((string) ($signatario->ultimo_metodo_login ?? 'senha')), hash('sha256', $conteudo)),
        ];
    }
}
