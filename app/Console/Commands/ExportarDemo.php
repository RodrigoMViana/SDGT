<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Exporta a base atual (normalmente a base de demonstração) para JSON consumido pela
 * versão estática de demonstração publicada no GitHub Pages (pasta demo/).
 * Uso: php artisan sdgt:exportar-demo demo/data/seed.json
 */
class ExportarDemo extends Command
{
    protected $signature = 'sdgt:exportar-demo {arquivo=demo/data/seed.json}';

    protected $description = 'Exporta a base de demonstração para o protótipo estático (GitHub Pages)';

    public function handle(): int
    {
        $tabelas = [
            'relatorias' => ['id', 'nome', 'conselheiro'],
            'municipios' => ['id', 'codigo_ibge', 'nome', 'regiao_imediata', 'relatoria_id'],
            'users' => ['id', 'name', 'email', 'cpf', 'perfil', 'municipio_id', 'cargo', 'ativo'],
            'temas' => ['id', 'nome', 'descricao', 'ordem', 'ativo'],
            'perguntas' => ['id', 'tema_id', 'codigo', 'enunciado', 'ajuda', 'link_apoio', 'tipo', 'obrigatoria', 'exige_evidencia', 'peso', 'pergunta_pai_id', 'ativo'],
            'opcoes_resposta' => ['id', 'pergunta_id', 'texto', 'pontuacao', 'ordem', 'solicita_evidencia'],
            'pergunta_dependencias' => ['pergunta_id', 'opcao_resposta_id'],
            'questionarios' => ['id', 'titulo', 'descricao', 'apresentacao', 'ciclo', 'data_inicio', 'data_fim', 'data_limite_assinatura', 'status', 'abrangencia', 'publicado_em', 'origem_id'],
            'questionario_pergunta' => ['questionario_id', 'pergunta_id', 'ordem'],
            'questionario_municipio' => ['questionario_id', 'municipio_id'],
            'preenchimentos' => ['id', 'questionario_id', 'municipio_id', 'status', 'responsavel_id', 'gestor_id', 'iniciado_em', 'finalizado_em', 'validado_em', 'assinatura_metodo', 'assinatura_hash', 'assinatura_detalhes', 'observacao_devolucao', 'indice_aderencia', 'indices_por_tema'],
            'preenchimento_respostas' => ['preenchimento_id', 'pergunta_id', 'opcoes_ids', 'valor_texto'],
            'evidencias' => ['id', 'preenchimento_id', 'pergunta_id', 'nome_original', 'mime', 'tamanho', 'created_at'],
            'notificacoes' => ['id', 'user_id', 'tipo', 'titulo', 'mensagem', 'questionario_id', 'lida_em', 'created_at'],
            'materiais_apoio' => ['id', 'titulo', 'descricao', 'categoria', 'arquivo_nome', 'url_externa', 'ativo'],
            'auditorias' => ['id', 'acao', 'entidade_tipo', 'entidade_id', 'entidade_descricao', 'user_nome', 'justificativa', 'dados_anteriores', 'dados_novos', 'ip', 'created_at'],
        ];

        $saida = ['gerado_em' => now()->toDateString(), 'config' => [
            'faixas' => config('sdgt.faixas'),
            'prazos' => config('sdgt.prazos'),
            'orgao' => config('sdgt.orgao'),
        ]];
        foreach ($tabelas as $tabela => $colunas) {
            $saida[$tabela] = DB::table($tabela)->select($colunas)->orderBy($colunas[0])->get()->map(function ($r) {
                foreach (['opcoes_ids', 'indices_por_tema', 'dados_anteriores', 'dados_novos'] as $json) {
                    if (property_exists($r, $json) && is_string($r->$json)) {
                        $r->$json = json_decode($r->$json, true);
                    }
                }
                foreach (['ativo', 'obrigatoria', 'exige_evidencia', 'solicita_evidencia'] as $b) {
                    if (property_exists($r, $b)) {
                        $r->$b = (bool) $r->$b;
                    }
                }
                foreach (['peso', 'pontuacao', 'indice_aderencia'] as $n) {
                    if (property_exists($r, $n) && $r->$n !== null) {
                        $r->$n = (float) $r->$n;
                    }
                }

                return $r;
            });
        }

        $arquivo = base_path($this->argument('arquivo'));
        @mkdir(dirname($arquivo), 0775, true);
        file_put_contents($arquivo, json_encode($saida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info('Exportado: '.$arquivo.' ('.number_format(filesize($arquivo) / 1024, 0).' KB)');

        return self::SUCCESS;
    }
}
