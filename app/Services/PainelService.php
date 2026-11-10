<?php

namespace App\Services;

use App\Models\Municipio;
use App\Models\Preenchimento;
use App\Models\Questionario;
use App\Models\Relatoria;
use App\Models\Tema;
use Illuminate\Support\Collection;

/**
 * Consolidações para o Painel Corporativo (EP07) e Relatórios Gerenciais (EP08).
 * Considera "concluídos" os preenchimentos finalizados (aguardando validação) e validados.
 */
class PainelService
{
    public const CONCLUIDOS = ['aguardando_validacao', 'validado'];

    public function questionarioPadrao(): ?Questionario
    {
        return Questionario::publicados()->orderByDesc('data_inicio')->first();
    }

    /** @return Collection<int, Questionario> */
    public function questionariosPublicados(): Collection
    {
        return Questionario::publicados()->orderByDesc('data_inicio')->get();
    }

    /** Uma linha por município abrangido, com o preenchimento (se houver). */
    public function linhas(Questionario $q, array $filtros = []): Collection
    {
        $municipios = $q->municipiosAlvoQuery()->with('relatoria')
            ->when($filtros['relatoria_id'] ?? null, fn ($m, $v) => $m->where('relatoria_id', $v))
            ->when($filtros['regiao'] ?? null, fn ($m, $v) => $m->where('regiao_imediata', $v))
            ->when($filtros['municipio_ids'] ?? null, fn ($m, $v) => $m->whereIn('id', $v))
            ->orderBy('nome')->get();
        $preenchimentos = Preenchimento::where('questionario_id', $q->id)->get()->keyBy('municipio_id');

        $linhas = $municipios->map(function (Municipio $m) use ($preenchimentos) {
            $p = $preenchimentos->get($m->id);
            $concluido = $p && in_array($p->status, self::CONCLUIDOS, true);
            $indice = $concluido ? $p->indice_aderencia : null;

            return (object) [
                'municipio' => $m,
                'preenchimento' => $p,
                'status' => $p?->status ?? 'nao_iniciado',
                'concluido' => $concluido,
                'indice' => $indice,
                'faixa' => PontuacaoService::faixa($indice),
                'por_tema' => $concluido ? (array) $p->indices_por_tema : [],
            ];
        });

        if ($f = $filtros['faixa'] ?? null) {
            $linhas = $linhas->filter(fn ($l) => $l->faixa === $f);
        }
        if ($s = $filtros['status'] ?? null) {
            $linhas = $linhas->filter(fn ($l) => $l->status === $s);
        }

        return $linhas->values();
    }

    public function panorama(Questionario $q): array
    {
        $linhas = $this->linhas($q);
        $concluidos = $linhas->where('concluido', true);
        $temas = $this->temasDoQuestionario($q);

        return [
            'total' => $linhas->count(),
            'por_status' => collect(Preenchimento::STATUS)->map(fn ($label, $k) => $linhas->where('status', $k)->count()),
            'concluidos' => $concluidos->count(),
            'validados' => $linhas->where('status', 'validado')->count(),
            'participacao' => $linhas->count() ? round($concluidos->count() / $linhas->count() * 100, 1) : 0,
            'indice_medio' => $concluidos->count() ? round($concluidos->avg('indice'), 1) : null,
            'faixas' => [
                'baixa' => $concluidos->where('faixa', 'baixa')->count(),
                'media' => $concluidos->where('faixa', 'media')->count(),
                'alta' => $concluidos->where('faixa', 'alta')->count(),
            ],
            'por_tema' => $temas->map(fn (Tema $t) => [
                'tema' => $t->nome,
                'indice' => $this->media($concluidos->map(fn ($l) => $l->por_tema[$t->id] ?? null)),
            ])->values(),
            'ranking_melhores' => $concluidos->sortByDesc('indice')->take(10)->values(),
            'ranking_piores' => $concluidos->sortBy('indice')->take(10)->values(),
            'por_regiao' => $linhas->groupBy(fn ($l) => $l->municipio->regiao_imediata ?? '—')->map(fn ($g, $regiao) => [
                'regiao' => $regiao,
                'total' => $g->count(),
                'concluidos' => $g->where('concluido', true)->count(),
                'indice' => $this->media($g->where('concluido', true)->pluck('indice')),
            ])->sortBy('regiao')->values(),
        ];
    }

    public function porRelatoria(Questionario $q): Collection
    {
        $linhas = $this->linhas($q);

        return Relatoria::orderBy('nome')->get()->map(function (Relatoria $r) use ($linhas) {
            $g = $linhas->filter(fn ($l) => $l->municipio->relatoria_id === $r->id);
            $c = $g->where('concluido', true);

            return (object) [
                'relatoria' => $r,
                'total' => $g->count(),
                'concluidos' => $c->count(),
                'validados' => $g->where('status', 'validado')->count(),
                'participacao' => $g->count() ? round($c->count() / $g->count() * 100, 1) : 0,
                'indice' => $this->media($c->pluck('indice')),
                'baixa' => $c->where('faixa', 'baixa')->count(),
                'media' => $c->where('faixa', 'media')->count(),
                'alta' => $c->where('faixa', 'alta')->count(),
                'piores' => $c->sortBy('indice')->take(3)->values(),
            ];
        })->filter(fn ($r) => $r->total > 0)->values();
    }

    /** Evolução ao longo dos ciclos (questionários publicados em ordem cronológica). */
    public function evolucao(?int $municipioId = null): Collection
    {
        return Questionario::publicados()->orderBy('data_inicio')->get()->map(function (Questionario $q) use ($municipioId) {
            $linhas = $this->linhas($q);
            $c = $linhas->where('concluido', true);
            $mun = $municipioId ? $linhas->first(fn ($l) => $l->municipio->id === $municipioId) : null;

            return (object) [
                'questionario' => $q,
                'participacao' => $linhas->count() ? round($c->count() / $linhas->count() * 100, 1) : 0,
                'indice' => $this->media($c->pluck('indice')),
                'baixa' => $c->count() ? round($c->where('faixa', 'baixa')->count() / $c->count() * 100, 1) : 0,
                'media' => $c->count() ? round($c->where('faixa', 'media')->count() / $c->count() * 100, 1) : 0,
                'alta' => $c->count() ? round($c->where('faixa', 'alta')->count() / $c->count() * 100, 1) : 0,
                'indice_municipio' => $mun?->indice,
            ];
        });
    }

    public function temasDoQuestionario(Questionario $q): Collection
    {
        $ids = $q->perguntas()->pluck('perguntas.tema_id')->unique();

        return Tema::whereIn('id', $ids)->orderBy('ordem')->get();
    }

    private function media(Collection $valores): ?float
    {
        $v = $valores->filter(fn ($x) => $x !== null);

        return $v->count() ? round($v->avg(), 1) : null;
    }
}
