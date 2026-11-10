<?php

namespace App\Integracoes\Drivers;

use App\Integracoes\Contracts\CalendarioOficial;
use Carbon\CarbonInterface;

/**
 * Driver local: considera sábados, domingos e os feriados configurados em
 * CALENDARIO_FERIADOS (MM-DD fixos ou YYYY-MM-DD específicos).
 * Substituir por CalendarioApi quando a API do Calendário Oficial do TCE/TO estiver disponível.
 */
class CalendarioLocal implements CalendarioOficial
{
    public function diaUtil(CarbonInterface $data): bool
    {
        if ($data->isWeekend()) {
            return false;
        }
        $feriados = (array) config('sdgt.integracoes.calendario.feriados', []);

        return ! in_array($data->format('m-d'), $feriados, true) && ! in_array($data->format('Y-m-d'), $feriados, true);
    }

    public function somarDiasUteis(CarbonInterface $data, int $dias): CarbonInterface
    {
        $d = $data->copy();
        $somados = 0;
        while ($somados < $dias) {
            $d = $d->addDay();
            if ($this->diaUtil($d)) {
                $somados++;
            }
        }

        return $d;
    }
}
