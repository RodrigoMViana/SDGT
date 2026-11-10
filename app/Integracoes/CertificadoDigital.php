<?php

namespace App\Integracoes;

use Illuminate\Http\Request;

/**
 * Autenticação por Certificado Digital ICP-Brasil (e-CPF).
 *
 * Driver "header": a negociação TLS mútua (mTLS) é feita pelo proxy reverso
 * (Nginx/Apache/WAF do Tribunal), que repassa o certificado do cliente em PEM
 * (URL-encoded) no cabeçalho configurado em CERTIFICADO_HEADER.
 * Exemplo Nginx:  proxy_set_header X-SSL-CLIENT-CERT $ssl_client_escaped_cert;
 *
 * INTEGRAÇÃO PENDENTE: configuração do proxy com a cadeia de ACs ICP-Brasil e
 * verificação de revogação (OCSP/LCR) na infraestrutura do TCE/TO.
 */
class CertificadoDigital
{
    public function extrairCpf(Request $request): ?string
    {
        $pem = urldecode((string) $request->header(config('sdgt.integracoes.certificado.header')));
        if (! str_contains($pem, 'BEGIN CERTIFICATE')) {
            return null;
        }

        return $this->cpfDoPem($pem);
    }

    public function cpfDoPem(string $pem): ?string
    {
        $cert = @openssl_x509_parse($pem);
        if (! $cert) {
            return null;
        }
        if (isset($cert['validTo_time_t']) && $cert['validTo_time_t'] < time()) {
            return null; // certificado expirado
        }
        // Padrão ICP-Brasil e-CPF: CN = "NOME DO TITULAR:CPF"
        $cn = $cert['subject']['CN'] ?? '';
        if (preg_match('/:(\d{11})$/', is_array($cn) ? end($cn) : $cn, $m)) {
            return $m[1];
        }
        // Alternativa: OID 2.16.76.1.3.1 (dados do titular PF) no subjectAltName — posições 9..19 = CPF
        $san = $cert['extensions']['subjectAltName'] ?? '';
        if (preg_match('/2\.16\.76\.1\.3\.1[^:]*:\s*(?:\S{0,4})?(\d{8})(\d{11})/', $san, $m)) {
            return $m[2];
        }

        return null;
    }
}
