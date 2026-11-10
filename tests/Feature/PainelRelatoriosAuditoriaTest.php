<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Models\Auditoria;
use App\Models\Preenchimento;
use App\Models\Questionario;
use Tests\TestCase;
use Tests\UsaBaseDemonstracao;

/**
 * EP07 — Painel Corporativo · EP08 — Relatórios · EP12 — Auditoria
 * Usa a base de demonstração completa (3 ciclos × 139 municípios).
 */
class PainelRelatoriosAuditoriaTest extends TestCase
{
    use UsaBaseDemonstracao { setUp as setUpBaseDemo; }

    protected function setUp(): void
    {
        $this->setUpBaseDemo();
        $this->actingAs($this->admin());
    }

    private function aberto(): Questionario
    {
        return Questionario::where('ciclo', '2026')->firstOrFail();
    }

    public function test_hu0701_panorama_consolidado(): void
    {
        $this->get('/tribunal/painel')->assertOk()
            ->assertSee('Municípios abrangidos')->assertSee('139')->assertSee('Índice médio de aderência')
            ->assertSee('Menores índices')->assertSee('Por região imediata');
        $antigo = Questionario::where('ciclo', '2024')->first();
        $this->get('/tribunal/painel?questionario_id='.$antigo->id)->assertOk()->assertSee('Ciclo 2024');
    }

    public function test_hu0702_comparar_municipios(): void
    {
        $ids = [$this->municipio('Porto Nacional')->id, $this->municipio('Araguaína')->id];
        $this->get('/tribunal/painel/comparar?questionario_id='.$this->aberto()->id.'&municipios[]='.$ids[0].'&municipios[]='.$ids[1])
            ->assertOk()->assertSee('Porto Nacional')->assertSee('Araguaína')->assertSee('Índice geral')->assertSee('Legislação Tributária');
    }

    public function test_hu0703_desempenho_por_relatoria(): void
    {
        $this->get('/tribunal/painel/relatorias')->assertOk()->assertSee('1ª Relatoria')->assertSee('6ª Relatoria');
    }

    public function test_hu0704_evolucao_da_adequacao(): void
    {
        $this->get('/tribunal/painel/evolucao')->assertOk()->assertSee('2024')->assertSee('2025')->assertSee('2026');
        $this->get('/tribunal/painel/evolucao?municipio_id='.$this->municipio('Palmas')->id)->assertOk()->assertSee('Palmas');
    }

    public function test_hu0705_e_0706_visualizar_e_baixar_preenchido(): void
    {
        $p = Preenchimento::where('status', 'validado')->firstOrFail();
        $this->get("/tribunal/preenchimentos/{$p->id}")->assertOk()->assertSee($p->municipio->nome)->assertSee('Índice de aderência');
        $this->assertSame('application/pdf', $this->get("/tribunal/preenchimentos/{$p->id}/pdf")->headers->get('content-type'));
    }

    public function test_hu0801_consultar_resultados_com_filtros(): void
    {
        $q = $this->aberto();
        $this->get('/tribunal/relatorios/resultados?questionario_id='.$q->id)->assertOk()->assertSee('139 município(s)');
        $r = $this->get('/tribunal/relatorios/resultados?questionario_id='.$q->id.'&status=validado')->assertOk();
        $validados = Preenchimento::where('questionario_id', $q->id)->where('status', 'validado')->count();
        $r->assertSee($validados.' município(s)');
        $this->get('/tribunal/relatorios/resultados?questionario_id='.$q->id.'&faixa=baixa')->assertOk();
    }

    public function test_hu0802_e_0803_exportar_pdf_e_xls(): void
    {
        $q = $this->aberto();
        $this->assertSame('application/pdf', $this->get('/tribunal/relatorios/resultados/pdf?questionario_id='.$q->id)->headers->get('content-type'));
        $x = $this->get('/tribunal/relatorios/resultados/xlsx?questionario_id='.$q->id)->assertOk();
        $this->assertStringContainsString('spreadsheetml', $x->headers->get('content-type'));
        $this->assertStringStartsWith('PK', $x->streamedContent()); // arquivo XLSX (zip)
    }

    public function test_auditor_acessa_painel_mas_nao_administracao(): void
    {
        $auditor = $this->usuario(Perfil::Auditor);
        $this->actingAs($auditor)->get('/tribunal/painel')->assertOk();
        $this->actingAs($auditor)->get('/tribunal/relatorios/resultados')->assertOk();
        $this->actingAs($auditor)->get('/admin/auditoria')->assertForbidden();
    }

    public function test_hu1202_listar_auditoria_com_filtros(): void
    {
        $this->get('/admin/auditoria')->assertOk()->assertSee('Publicação de questionário')->assertSee('Prorrogação de prazo');
        $this->get('/admin/auditoria?acao=prorrogacao')->assertOk()->assertSee('Associação Tocantinense')->assertDontSee('Ciclo 2024 (piloto)');
    }

    public function test_hu1203_e_1204_exportar_listagem_pdf_xls(): void
    {
        $this->assertSame('application/pdf', $this->get('/admin/auditoria/exportar/pdf')->headers->get('content-type'));
        $x = $this->get('/admin/auditoria/exportar/xlsx?acao=publicacao')->assertOk();
        $this->assertStringStartsWith('PK', $x->streamedContent());
    }

    public function test_hu1205_e_1206_visualizar_e_exportar_registro(): void
    {
        $a = Auditoria::where('acao', 'prorrogacao')->firstOrFail();
        $this->get("/admin/auditoria/{$a->id}")->assertOk()->assertSee('Dados anteriores')->assertSee('Dados novos')->assertSee('data fim');
        $this->assertSame('application/pdf', $this->get("/admin/auditoria/{$a->id}/pdf")->headers->get('content-type'));
    }
}
