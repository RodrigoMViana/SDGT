<?php

namespace Database\Seeders;

use App\Models\OpcaoResposta;
use App\Models\Pergunta;
use App\Models\Tema;
use Illuminate\Database\Seeder;

/**
 * Banco de perguntas SUGERIDO (versão inicial para validação pelo TCE/TO — decisão pendente).
 * Formato da opção: [texto, pontuação, solicita_evidência]
 * Dependência: 'dep' => [código da pergunta-pai, [índices das opções que habilitam]]
 */
class BancoPerguntasSeeder extends Seeder
{
    public static function perguntas(): array
    {
        $lt = 'Legislação Tributária';
        $rh = 'Recursos Humanos';
        $ti = 'Infraestrutura Física e Tecnologia da Informação';
        $pf = 'Procedimentos de Fiscalização';
        $cc = 'Cobrança de Créditos Tributários e Contabilização';
        $gt = 'Governança da Transição para a Reforma Tributária';

        return [
            ['LT01', $lt, 'unica', 'A Lei Orgânica Municipal foi adequada às disposições da Emenda Constitucional nº 132/2023?', [
                ['Sim, integralmente', 10, true], ['Sim, parcialmente', 6, true], ['Há projeto em tramitação na Câmara Municipal', 4, true], ['Não', 0, false],
            ], 'Considere as alterações relativas à instituição do IBS, à transição do ISS e às novas competências municipais. Se houver projeto em tramitação, anexe o projeto como evidência.'],
            ['LT02', $lt, 'unica', 'Existe estudo ou cronograma formal para a adequação da Lei Orgânica Municipal?', [
                ['Sim', 5, true], ['Não', 0, false],
            ], 'Responda somente se a Lei Orgânica ainda não foi adequada.', 'dep' => ['LT01', [3]]],
            ['LT03', $lt, 'unica', 'O Código Tributário Municipal foi revisado considerando a transição do ISS para o IBS (2029 a 2033)?', [
                ['Sim, revisado e publicado', 10, true], ['Em revisão', 5, false], ['Revisão não iniciada', 0, false],
            ]],
            ['LT04', $lt, 'unica', 'A base de cálculo do IPTU (Planta Genérica de Valores) foi atualizada nos últimos 4 anos?', [
                ['Sim, por lei', 10, true], ['Sim, por decreto do Executivo, conforme critérios fixados em lei (EC 132/2023)', 10, true], ['Não', 0, false],
            ]],
            ['LT05', $lt, 'data', 'Informe a data de publicação da última atualização da Planta Genérica de Valores.', [], null, 'obrigatoria' => false, 'dep' => ['LT04', [0, 1]]],
            ['LT06', $lt, 'unica', 'A legislação de benefícios fiscais do ISS foi revisada considerando as regras de transição da Reforma Tributária?', [
                ['Sim', 10, true], ['Em revisão', 5, false], ['Não', 0, false], ['O município não concede benefícios fiscais de ISS', 10, false],
            ]],

            ['RH01', $rh, 'numero', 'Quantos servidores efetivos atuam na administração tributária (fiscalização, arrecadação e cobrança)?', [], 'Informe apenas servidores efetivos, sem comissionados ou terceirizados.', 'peso' => 0],
            ['RH02', $rh, 'unica', 'Existe carreira específica de auditor/fiscal de tributos, com ingresso por concurso público?', [
                ['Sim, com cargos providos', 10, true], ['Sim, mas com cargos vagos', 5, true], ['Não', 0, false],
            ], 'Anexe a lei de criação da carreira.'],
            ['RH03', $rh, 'unica', 'Qual o percentual de servidores da administração tributária capacitados sobre IBS/CBS nos últimos 12 meses?', [
                ['Nenhum', 0, false], ['Até 25%', 3, false], ['De 26% a 75%', 7, false], ['Acima de 75%', 10, false],
            ]],
            ['RH04', $rh, 'unica', 'Há plano de capacitação aprovado para a transição da Reforma Tributária?', [
                ['Sim', 10, true], ['Em elaboração', 4, false], ['Não', 0, false],
            ]],

            ['TI01', $ti, 'unica', 'Qual o modelo de emissão de Nota Fiscal de Serviço eletrônica (NFS-e) utilizado pelo município?', [
                ['Padrão nacional (emissor e Ambiente de Dados Nacional)', 10, false], ['Sistema próprio integrado ao Ambiente de Dados Nacional', 8, false],
                ['Sistema próprio não integrado ao padrão nacional', 2, false], ['Não emite NFS-e eletrônica', 0, false],
            ], 'O padrão nacional da NFS-e é requisito para a operacionalização do IBS.'],
            ['TI02', $ti, 'multipla', 'Quais módulos o sistema informatizado de gestão tributária possui de forma integrada?', [
                ['Cadastro imobiliário', 2, false], ['Cadastro mobiliário (contribuintes)', 2, false], ['Arrecadação', 2, false], ['Dívida ativa', 2, false], ['Fiscalização', 2, false], ['Nenhum sistema informatizado', 0, false],
            ], 'Marque todas as opções aplicáveis.'],
            ['TI03', $ti, 'unica', 'A infraestrutura física da administração tributária (espaço, equipamentos e conectividade) é adequada?', [
                ['Adequada', 10, false], ['Parcialmente adequada', 5, false], ['Inadequada', 0, false],
            ]],
            ['TI04', $ti, 'unica', 'O município aderiu a convênios de compartilhamento de dados com o Comitê Gestor do IBS e/ou com a Receita Federal?', [
                ['Sim', 10, true], ['Em andamento', 5, false], ['Não', 0, false],
            ], 'Anexe o termo de adesão ou convênio.'],

            ['PF01', $pf, 'unica', 'Existe plano anual de fiscalização formalmente aprovado?', [
                ['Sim', 10, true], ['Não', 0, false],
            ]],
            ['PF02', $pf, 'unica', 'A fiscalização utiliza cruzamento de dados (malha fiscal) com informações de NFS-e, operações com cartões e outras fontes?', [
                ['Sim, rotineiramente', 10, false], ['Eventualmente', 5, false], ['Não', 0, false],
            ]],
            ['PF03', $pf, 'numero', 'Quantas ações fiscais foram concluídas no último exercício?', [], null, 'peso' => 0],
            ['PF04', $pf, 'unica', 'Há manual de procedimentos de fiscalização normatizado?', [
                ['Sim', 10, true], ['Não', 0, false],
            ]],

            ['CC01', $cc, 'unica', 'A dívida ativa tributária é inscrita regularmente (até o exercício seguinte ao vencimento)?', [
                ['Sim', 10, false], ['Parcialmente', 5, false], ['Não', 0, false],
            ]],
            ['CC02', $cc, 'multipla', 'Quais instrumentos de cobrança de créditos tributários são utilizados?', [
                ['Cobrança administrativa', 2.5, false], ['Protesto extrajudicial de CDA', 2.5, false], ['Execução fiscal', 2.5, false], ['Programa de parcelamento', 2.5, false], ['Nenhum', 0, false],
            ]],
            ['CC03', $cc, 'unica', 'A receita tributária é reconhecida pelo regime de competência, conforme o MCASP?', [
                ['Sim', 10, false], ['Parcialmente', 5, false], ['Não', 0, false],
            ]],
            ['CC04', $cc, 'unica', 'Há conciliação periódica entre o sistema tributário e a contabilidade?', [
                ['Mensal', 10, false], ['Anual', 5, false], ['Não há conciliação', 0, false],
            ]],

            ['GT01', $gt, 'unica', 'O município instituiu comissão ou grupo de trabalho para a transição da Reforma Tributária?', [
                ['Sim', 10, true], ['Em constituição', 4, false], ['Não', 0, false],
            ], 'Anexe o ato de instituição/nomeação (decreto ou portaria).'],
            ['GT02', $gt, 'unica', 'Com que periodicidade o grupo de trabalho se reúne?', [
                ['Mensal ou inferior', 10, false], ['Trimestral', 6, false], ['Sem periodicidade definida', 2, false],
            ], null, 'dep' => ['GT01', [0]]],
            ['GT03', $gt, 'unica', 'Foi elaborado estudo de impacto da Reforma Tributária na arrecadação própria do município?', [
                ['Sim', 10, true], ['Em elaboração', 5, false], ['Não', 0, false],
            ]],
            ['GT04', $gt, 'unica', 'O município acompanha as normas e deliberações do Comitê Gestor do IBS?', [
                ['Sim, com responsável formalmente designado', 10, false], ['Sim, sem responsável designado', 5, false], ['Não', 0, false],
            ]],
            ['GT05', $gt, 'texto', 'Observações complementares sobre a preparação do município para a Reforma Tributária.', [], null, 'obrigatoria' => false, 'peso' => 0],
        ];
    }

    public function run(): void
    {
        $temas = Tema::pluck('id', 'nome');
        $criadas = [];
        foreach (self::perguntas() as $def) {
            [$codigo, $tema, $tipo, $enunciado, $opcoes] = $def;
            $p = Pergunta::firstOrCreate(['codigo' => $codigo], [
                'tema_id' => $temas[$tema],
                'enunciado' => $enunciado,
                'ajuda' => $def[5] ?? null,
                'tipo' => $tipo,
                'obrigatoria' => $def['obrigatoria'] ?? true,
                'exige_evidencia' => false,
                'peso' => $def['peso'] ?? 1,
                'ativo' => true,
            ]);
            if ($p->wasRecentlyCreated) {
                foreach ($opcoes as $i => [$texto, $pts, $evid]) {
                    OpcaoResposta::create(['pergunta_id' => $p->id, 'texto' => $texto, 'pontuacao' => $pts, 'ordem' => $i + 1, 'solicita_evidencia' => $evid]);
                }
            }
            $criadas[$codigo] = $p;
        }
        foreach (self::perguntas() as $def) {
            if (! isset($def['dep']) || ! $criadas[$def[0]]->wasRecentlyCreated) {
                continue;
            }
            [$paiCod, $indices] = $def['dep'];
            $pai = $criadas[$paiCod];
            $opcoes = $pai->opcoes()->get()->values();
            $filho = $criadas[$def[0]];
            $filho->update(['pergunta_pai_id' => $pai->id]);
            $filho->opcoesHabilitadoras()->sync(collect($indices)->map(fn ($i) => $opcoes[$i]->id)->all());
        }
    }
}
