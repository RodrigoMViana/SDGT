<?php

namespace App\Integracoes\Contracts;

use Carbon\CarbonInterface;

/** Calendário Oficial do Tribunal (dias úteis / feriados). */
interface CalendarioOficial
{
    /** Retorna a data após somar $dias dias úteis a $data. */
    public function somarDiasUteis(CarbonInterface $data, int $dias): CarbonInterface;

    public function diaUtil(CarbonInterface $data): bool;
}
