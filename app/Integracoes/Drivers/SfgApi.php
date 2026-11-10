<?php

namespace App\Integracoes\Drivers;

use App\Integracoes\Contracts\AlertaFiscalizacao;
use App\Models\Preenchimento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * INTEGRAÇÃO PENDENTE — SFG (Sistema de Fiscalização da Gestão) para emissão de alertas.
 * Contrato presumido (a confirmar):
 *   POST {SFG_API_URL}/alertas {"codigo_ibge","origem":"SDGT","tipo":"BAIXA_ADERENCIA_REFORMA_TRIBUTARIA","indice","referencia"}
 */
class SfgApi implements AlertaFiscalizacao
{
    public function emitirAlerta(Preenchimento $preenchimento, string $faixa): void
    {
        try {
            Http::withToken((string) config('sdgt.integracoes.sfg.token'))->timeout(15)
                ->post(rtrim((string) config('sdgt.integracoes.sfg.url'), '/').'/alertas', [
                    'codigo_ibge' => $preenchimento->municipio->codigo_ibge,
                    'origem' => 'SDGT',
                    'tipo' => 'ADERENCIA_'.strtoupper($faixa),
                    'indice' => $preenchimento->indice_aderencia,
                    'referencia' => $preenchimento->questionario->titulo,
                ])->throw();
        } catch (\Throwable $e) {
            Log::error('Falha ao emitir alerta no SFG', ['preenchimento' => $preenchimento->id, 'erro' => $e->getMessage()]);
        }
    }
}
