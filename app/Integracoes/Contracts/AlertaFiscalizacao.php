<?php

namespace App\Integracoes\Contracts;

use App\Models\Preenchimento;

/** SFG - Sistema de Fiscalização da Gestão (emissão de alertas). */
interface AlertaFiscalizacao
{
    public function emitirAlerta(Preenchimento $preenchimento, string $faixa): void;
}
