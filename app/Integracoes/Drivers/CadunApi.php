<?php

namespace App\Integracoes\Drivers;

use App\Integracoes\Contracts\CadastroUnico;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * INTEGRAÇÃO PENDENTE — API do CADUN (Cadastro Único) do TCE/TO.
 * Contrato presumido (a confirmar com a DINFO/CODES):
 *   GET {CADUN_API_URL}/pessoas/{cpf}
 *   → {"nome","email","cpf","vinculos":[{"codigo_ibge","papel":"RESPONSAVEL_TRIBUTARIO|GESTOR","cargo"}]}
 */
class CadunApi implements CadastroUnico
{
    public function buscarPorCpf(string $cpf): ?array
    {
        try {
            $resp = Http::withToken((string) config('sdgt.integracoes.cadun.token'))->timeout(10)
                ->get(rtrim((string) config('sdgt.integracoes.cadun.url'), '/').'/pessoas/'.preg_replace('/\D/', '', $cpf));
            if (! $resp->successful()) {
                return null;
            }
            $p = $resp->json();
            $vinculo = collect($p['vinculos'] ?? [])->first();

            return [
                'nome' => $p['nome'],
                'email' => $p['email'],
                'cpf' => $p['cpf'],
                'perfil' => ($vinculo['papel'] ?? '') === 'GESTOR' ? 'gestor' : 'responsavel',
                'codigo_ibge' => $vinculo['codigo_ibge'] ?? null,
                'cargo' => $vinculo['cargo'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('CADUN indisponível', ['erro' => $e->getMessage()]);

            return null;
        }
    }
}
