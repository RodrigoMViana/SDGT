<?php

namespace App\Services;

use App\Models\Pergunta;
use App\Models\Preenchimento;
use App\Models\Questionario;
use Illuminate\Support\Collection;

/**
 * Regras de cálculo do índice de aderência e de habilitação de perguntas condicionais.
 *
 * - Somente perguntas de escolha (única/múltipla) pontuam.
 * - Pontuação da pergunta = pontuação da(s) opção(ões) marcada(s) × peso.
 * - Máximo da pergunta = maior opção (única) ou soma das opções positivas (múltipla) × peso.
 * - Perguntas desabilitadas por dependência NÃO entram no máximo (não penalizam o município).
 * - Índice = obtido / máximo × 100.
 */
class PontuacaoService
{
    /**
     * @param  Collection<int, Pergunta>  $perguntas  perguntas do questionário (com opcoes e opcoesHabilitadoras carregadas)
     * @param  array<int, array{opcoes_ids?: array|null, valor_texto?: string|null}>  $respostas  indexado por pergunta_id
     */
    public function perguntaHabilitada(Pergunta $pergunta, Collection $perguntas, array $respostas, int $profundidade = 0): bool
    {
        if (! $pergunta->pergunta_pai_id || $profundidade > 10) {
            return true;
        }
        $pai = $perguntas->firstWhere('id', $pergunta->pergunta_pai_id);
        if (! $pai) {
            // pai não faz parte do questionário: dependência é ignorada
            return true;
        }
        if (! $this->perguntaHabilitada($pai, $perguntas, $respostas, $profundidade + 1)) {
            return false;
        }
        $marcadas = array_map('intval', (array) ($respostas[$pai->id]['opcoes_ids'] ?? []));
        $habilitadoras = $pergunta->opcoesHabilitadoras->pluck('id')->map(fn ($i) => (int) $i)->all();

        return count(array_intersect($marcadas, $habilitadoras)) > 0;
    }

    public function pontuacaoPergunta(Pergunta $pergunta, array $opcoesIds): float
    {
        if (! $pergunta->temOpcoes() || empty($opcoesIds)) {
            return 0.0;
        }
        $ids = array_map('intval', $opcoesIds);
        $soma = (float) $pergunta->opcoes->whereIn('id', $ids)->sum('pontuacao');
        $max = $pergunta->pontuacaoMaxima();
        if ($pergunta->tipo === 'multipla') {
            $soma = min($soma, $max);
        }

        return round(max($soma, 0) * (float) $pergunta->peso, 2);
    }

    /**
     * @return array{obtida: float, maxima: float, indice: float|null, por_tema: array<int, array{tema: string, obtida: float, maxima: float, indice: float|null}>}
     */
    public function calcular(Questionario $questionario, array $respostas): array
    {
        $perguntas = $this->perguntasDoQuestionario($questionario);
        $obtida = 0.0;
        $maxima = 0.0;
        $porTema = [];

        foreach ($perguntas as $p) {
            if (! $p->temOpcoes() || ! $this->perguntaHabilitada($p, $perguntas, $respostas)) {
                continue;
            }
            $max = round($p->pontuacaoMaxima() * (float) $p->peso, 2);
            $pts = $this->pontuacaoPergunta($p, (array) ($respostas[$p->id]['opcoes_ids'] ?? []));
            $obtida += $pts;
            $maxima += $max;
            $porTema[$p->tema_id] ??= ['tema' => $p->tema?->nome ?? '-', 'obtida' => 0.0, 'maxima' => 0.0];
            $porTema[$p->tema_id]['obtida'] += $pts;
            $porTema[$p->tema_id]['maxima'] += $max;
        }

        foreach ($porTema as &$t) {
            $t['indice'] = $t['maxima'] > 0 ? round($t['obtida'] / $t['maxima'] * 100, 2) : null;
        }

        return [
            'obtida' => round($obtida, 2),
            'maxima' => round($maxima, 2),
            'indice' => $maxima > 0 ? round($obtida / $maxima * 100, 2) : null,
            'por_tema' => $porTema,
        ];
    }

    public function recalcular(Preenchimento $preenchimento): Preenchimento
    {
        $questionario = $preenchimento->questionario;
        $respostas = $this->respostasArray($preenchimento);
        $perguntas = $this->perguntasDoQuestionario($questionario);
        foreach ($preenchimento->respostas as $r) {
            $p = $perguntas->firstWhere('id', $r->pergunta_id);
            if ($p) {
                $r->pontuacao = $this->perguntaHabilitada($p, $perguntas, $respostas)
                    ? $this->pontuacaoPergunta($p, (array) $r->opcoes_ids) : 0;
                $r->save();
            }
        }
        $res = $this->calcular($questionario, $respostas);
        $preenchimento->forceFill([
            'pontuacao_obtida' => $res['obtida'],
            'pontuacao_maxima' => $res['maxima'],
            'indice_aderencia' => $res['indice'],
            'indices_por_tema' => collect($res['por_tema'])->map(fn ($t) => $t['indice'])->all(),
        ])->save();

        return $preenchimento;
    }

    public function respostasArray(Preenchimento $preenchimento): array
    {
        return $preenchimento->respostas()->get()
            ->mapWithKeys(fn ($r) => [$r->pergunta_id => ['opcoes_ids' => $r->opcoes_ids, 'valor_texto' => $r->valor_texto]])
            ->all();
    }

    /** @return Collection<int, Pergunta> */
    public function perguntasDoQuestionario(Questionario $questionario): Collection
    {
        return $questionario->perguntas()->with(['opcoes', 'opcoesHabilitadoras', 'tema', 'perguntaPai'])->get();
    }

    public static function faixa(?float $indice): ?string
    {
        if ($indice === null) {
            return null;
        }
        if ($indice <= config('sdgt.faixas.baixa_ate')) {
            return 'baixa';
        }
        if ($indice <= config('sdgt.faixas.media_ate')) {
            return 'media';
        }

        return 'alta';
    }

    public static function faixaLabel(?string $faixa): string
    {
        return match ($faixa) {
            'baixa' => 'Baixa aderência',
            'media' => 'Média aderência',
            'alta' => 'Alta aderência',
            default => 'Sem índice',
        };
    }
}
