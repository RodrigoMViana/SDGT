<?php

namespace App\Integracoes\Drivers;

use App\Integracoes\Contracts\AlertaFiscalizacao;
use App\Models\Preenchimento;
use Illuminate\Support\Facades\Log;

/** Driver provisório: registra em log o alerta que seria enviado ao SFG. */
class SfgLog implements AlertaFiscalizacao
{
    public function emitirAlerta(Preenchimento $preenchimento, string $faixa): void
    {
        Log::channel(config('logging.default'))->info('[SFG] Alerta de aderência (simulado)', [
            'municipio' => $preenchimento->municipio?->nome,
            'codigo_ibge' => $preenchimento->municipio?->codigo_ibge,
            'questionario' => $preenchimento->questionario?->titulo,
            'indice' => $preenchimento->indice_aderencia,
            'faixa' => $faixa,
        ]);
    }
}
