<?php

namespace Tests\Unit;

use App\Integracoes\Drivers\CalendarioLocal;
use App\Services\PontuacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Regras de cálculo do índice, dependência entre perguntas e calendário. */
class RegrasNegocioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    public function test_pergunta_condicional_so_habilita_com_opcao_da_pergunta_pai(): void
    {
        $q = $this->questionarioAberto();
        $svc = app(PontuacaoService::class);
        $perguntas = $svc->perguntasDoQuestionario($q);
        $lt02 = $perguntas->firstWhere('codigo', 'LT02');
        $lt01 = $this->pergunta('LT01');

        $this->assertFalse($svc->perguntaHabilitada($lt02, $perguntas, []));
        $this->assertFalse($svc->perguntaHabilitada($lt02, $perguntas, [$lt01->id => ['opcoes_ids' => [$this->opcao('LT01', 'Sim, integralmente')]]]));
        $this->assertTrue($svc->perguntaHabilitada($lt02, $perguntas, [$lt01->id => ['opcoes_ids' => [$this->opcao('LT01', 'Não')]]]));
    }

    public function test_pergunta_desabilitada_nao_entra_na_pontuacao_maxima(): void
    {
        $q = $this->questionarioAberto();
        $svc = app(PontuacaoService::class);
        $lt01 = $this->pergunta('LT01');

        // LT01 = "Sim, integralmente" (10 pts) → LT02 desabilitada (não soma no máximo)
        $r1 = $svc->calcular($q, [$lt01->id => ['opcoes_ids' => [$this->opcao('LT01', 'Sim, integralmente')]]]);
        // LT01 = "Não" (0 pts) → LT02 habilitada: máximo aumenta em 5
        $r2 = $svc->calcular($q, [$lt01->id => ['opcoes_ids' => [$this->opcao('LT01', 'Não')]]]);

        $this->assertEquals($r1['maxima'] + 5, $r2['maxima']);
        $this->assertEquals(10, $r1['obtida']);
        $this->assertEquals(0, $r2['obtida']);
    }

    public function test_multipla_escolha_soma_opcoes_e_respeita_o_maximo(): void
    {
        $svc = app(PontuacaoService::class);
        $ti02 = $this->pergunta('TI02');
        $todas = $ti02->opcoes->pluck('id')->all();

        $this->assertEquals(10.0, $ti02->pontuacaoMaxima());
        $this->assertEquals(10.0, $svc->pontuacaoPergunta($ti02, $todas));
        $this->assertEquals(4.0, $svc->pontuacaoPergunta($ti02, array_slice($todas, 0, 2)));
    }

    public function test_indice_completo_e_por_tema(): void
    {
        $q = $this->questionarioAberto();
        $svc = app(PontuacaoService::class);
        $respostas = [];
        foreach ($svc->perguntasDoQuestionario($q) as $p) {
            if ($p->temOpcoes()) {
                $melhor = $p->tipo === 'unica' ? [$p->opcoes->sortByDesc('pontuacao')->first()->id] : $p->opcoes->where('pontuacao', '>', 0)->pluck('id')->all();
                $respostas[$p->id] = ['opcoes_ids' => $melhor];
            }
        }
        $r = $svc->calcular($q, $respostas);
        $this->assertEquals(100.0, $r['indice']);
        $this->assertCount(6, $r['por_tema']);
        foreach ($r['por_tema'] as $t) {
            $this->assertEquals(100.0, $t['indice']);
        }
    }

    public function test_faixas_de_aderencia_configuraveis(): void
    {
        $this->assertSame('baixa', PontuacaoService::faixa(30));
        $this->assertSame('media', PontuacaoService::faixa(60));
        $this->assertSame('alta', PontuacaoService::faixa(80));
        $this->assertNull(PontuacaoService::faixa(null));
        config(['sdgt.faixas.baixa_ate' => 70]);
        $this->assertSame('baixa', PontuacaoService::faixa(60));
    }

    public function test_calendario_soma_dias_uteis_pulando_fim_de_semana_e_feriado(): void
    {
        $cal = new CalendarioLocal;
        // sexta 13/11/2026 + 1 dia útil = segunda 16/11/2026 (15/11 é feriado e domingo)
        $this->assertSame('2026-11-16', $cal->somarDiasUteis(Carbon::parse('2026-11-13'), 1)->toDateString());
        // quinta 19/11/2026 + 1 dia útil: 20/11 (Consciência Negra) é feriado → segunda 23/11
        $this->assertSame('2026-11-23', $cal->somarDiasUteis(Carbon::parse('2026-11-19'), 1)->toDateString());
        $this->assertTrue($cal->diaUtil(Carbon::parse('2026-11-10')));
    }
}
