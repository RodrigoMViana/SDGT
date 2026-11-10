<?php

namespace App\Integracoes\Drivers;

use App\Integracoes\Contracts\CalendarioOficial;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * INTEGRAÇÃO PENDENTE — API do Calendário Oficial do TCE/TO.
 * Contrato presumido (a confirmar com a DINFO/CODES):
 *   GET {CALENDARIO_API_URL}/feriados?ano=YYYY  →  [{"data":"YYYY-MM-DD","descricao":"..."}]
 * Em caso de falha, recorre ao driver local para não bloquear a operação.
 */
class CalendarioApi implements CalendarioOficial
{
    public function __construct(private CalendarioLocal $fallback) {}

    public function diaUtil(CarbonInterface $data): bool
    {
        if ($data->isWeekend()) {
            return false;
        }
        $feriados = $this->feriados((int) $data->format('Y'));

        return $feriados === null ? $this->fallback->diaUtil($data) : ! in_array($data->format('Y-m-d'), $feriados, true);
    }

    public function somarDiasUteis(CarbonInterface $data, int $dias): CarbonInterface
    {
        $d = $data->copy();
        $n = 0;
        while ($n < $dias) {
            $d = $d->addDay();
            if ($this->diaUtil($d)) {
                $n++;
            }
        }

        return $d;
    }

    /** @return array<int, string>|null */
    private function feriados(int $ano): ?array
    {
        return Cache::remember("sdgt.feriados.$ano", 86400, function () use ($ano) {
            try {
                $resp = Http::withToken((string) config('sdgt.integracoes.calendario.token'))
                    ->timeout(10)->get(rtrim((string) config('sdgt.integracoes.calendario.url'), '/').'/feriados', ['ano' => $ano]);

                return $resp->successful() ? collect($resp->json())->pluck('data')->all() : null;
            } catch (\Throwable $e) {
                Log::warning('Calendário Oficial indisponível', ['erro' => $e->getMessage()]);

                return null;
            }
        });
    }
}
