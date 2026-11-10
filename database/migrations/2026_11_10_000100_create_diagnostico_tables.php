<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estrutura principal do diagnóstico.
 *
 * Observação para SQL Server: o SQL Server rejeita FKs que formem múltiplos caminhos de
 * "cascade". Por isso apenas as relações de composição (filho não existe sem o pai) usam
 * cascadeOnDelete; as demais usam NO ACTION e a exclusão é controlada pela aplicação.
 */
return new class extends Migration
{
    public function up(): void
    {
        // EP02 - Temas de avaliação (dimensões)
        Schema::create('temas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // EP04 - Perguntas
        Schema::create('perguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tema_id')->constrained('temas'); // NO ACTION: tema com perguntas não pode ser excluído
            $table->string('codigo', 20)->unique();
            $table->text('enunciado');
            $table->text('ajuda')->nullable();
            $table->string('link_apoio', 500)->nullable();
            $table->string('tipo', 20); // unica | multipla | texto | numero | data
            $table->boolean('obrigatoria')->default(true);
            $table->boolean('exige_evidencia')->default(false);
            $table->decimal('peso', 6, 2)->default(1);
            $table->foreignId('pergunta_pai_id')->nullable()->constrained('perguntas'); // dependência (NO ACTION)
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // EP04 - Respostas (opções de resposta de uma pergunta)
        Schema::create('opcoes_resposta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pergunta_id')->constrained('perguntas')->cascadeOnDelete();
            $table->string('texto', 500);
            $table->decimal('pontuacao', 6, 2)->default(0);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('solicita_evidencia')->default(false);
            $table->timestamps();
        });

        // HU04.05 - opções da pergunta-pai que HABILITAM a pergunta dependente
        Schema::create('pergunta_dependencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pergunta_id')->constrained('perguntas')->cascadeOnDelete();
            $table->foreignId('opcao_resposta_id')->constrained('opcoes_resposta'); // NO ACTION
            $table->unique(['pergunta_id', 'opcao_resposta_id']);
        });

        // EP03 - Questionários
        Schema::create('questionarios', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->text('descricao')->nullable();
            $table->text('apresentacao')->nullable(); // normativos, portarias, contextualização
            $table->string('ciclo', 20);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->date('data_limite_assinatura');
            $table->string('status', 20)->default('rascunho'); // rascunho | publicado | encerrado
            $table->string('abrangencia', 20)->default('todos'); // todos | selecionados
            $table->timestamp('publicado_em')->nullable();
            $table->foreignId('publicado_por')->nullable()->constrained('users');
            $table->foreignId('origem_id')->nullable()->constrained('questionarios'); // duplicação
            $table->foreignId('criado_por')->nullable()->constrained('users');
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('questionario_pergunta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionario_id')->constrained('questionarios')->cascadeOnDelete();
            $table->foreignId('pergunta_id')->constrained('perguntas'); // NO ACTION
            $table->unsignedInteger('ordem')->default(0);
            $table->unique(['questionario_id', 'pergunta_id']);
        });

        Schema::create('questionario_municipio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionario_id')->constrained('questionarios')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipios'); // NO ACTION
            $table->unique(['questionario_id', 'municipio_id']);
        });

        // EP05 - Preenchimento de um questionário por um município
        Schema::create('preenchimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionario_id')->constrained('questionarios'); // NO ACTION
            $table->foreignId('municipio_id')->constrained('municipios');       // NO ACTION
            $table->string('status', 30)->default('em_preenchimento'); // em_preenchimento | aguardando_validacao | validado | devolvido
            $table->foreignId('responsavel_id')->nullable()->constrained('users');
            $table->foreignId('gestor_id')->nullable()->constrained('users');
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamp('validado_em')->nullable();
            $table->string('assinatura_metodo', 30)->nullable();
            $table->string('assinatura_hash', 128)->nullable();
            $table->text('assinatura_detalhes')->nullable();
            $table->text('observacao_devolucao')->nullable();
            $table->decimal('pontuacao_obtida', 10, 2)->default(0);
            $table->decimal('pontuacao_maxima', 10, 2)->default(0);
            $table->decimal('indice_aderencia', 5, 2)->nullable();
            $table->json('indices_por_tema')->nullable(); // {tema_id: indice} — cache para painéis
            $table->timestamps();
            $table->unique(['questionario_id', 'municipio_id']);
        });

        Schema::create('preenchimento_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preenchimento_id')->constrained('preenchimentos')->cascadeOnDelete();
            $table->foreignId('pergunta_id')->constrained('perguntas'); // NO ACTION
            $table->json('opcoes_ids')->nullable();
            $table->text('valor_texto')->nullable();
            $table->decimal('pontuacao', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['preenchimento_id', 'pergunta_id']);
        });

        // EP06 - Evidências
        Schema::create('evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preenchimento_id')->constrained('preenchimentos')->cascadeOnDelete();
            $table->foreignId('pergunta_id')->constrained('perguntas'); // NO ACTION
            $table->string('nome_original', 255);
            $table->string('caminho', 500);
            $table->string('mime', 150)->nullable();
            $table->unsignedBigInteger('tamanho')->default(0);
            $table->string('hash_sha256', 64)->nullable();
            $table->foreignId('enviado_por')->nullable()->constrained('users');
            $table->timestamps();
        });

        // EP09 - Notificações
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('titulo', 200);
            $table->text('mensagem');
            $table->string('link', 500)->nullable();
            $table->foreignId('questionario_id')->nullable()->constrained('questionarios'); // NO ACTION
            $table->timestamp('lida_em')->nullable();
            $table->timestamp('email_enviado_em')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'lida_em']);
        });

        // EP11 - Materiais de apoio
        Schema::create('materiais_apoio', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->text('descricao')->nullable();
            $table->string('categoria', 30); // cartilha | manual | normativo | outro
            $table->string('arquivo_caminho', 500)->nullable();
            $table->string('arquivo_nome', 255)->nullable();
            $table->string('arquivo_mime', 150)->nullable();
            $table->unsignedBigInteger('arquivo_tamanho')->nullable();
            $table->string('url_externa', 500)->nullable();
            $table->boolean('ativo')->default(true);
            $table->foreignId('criado_por')->nullable()->constrained('users');
            $table->timestamps();
        });

        // EP12 - Auditoria
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->string('acao', 40)->index(); // publicacao | prorrogacao | ...
            $table->string('entidade_tipo', 80);
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->string('entidade_descricao', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('user_nome', 255)->nullable();
            $table->text('justificativa')->nullable();
            $table->json('dados_anteriores')->nullable();
            $table->json('dados_novos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        foreach (['auditorias', 'materiais_apoio', 'notificacoes', 'evidencias', 'preenchimento_respostas',
            'preenchimentos', 'questionario_municipio', 'questionario_pergunta', 'questionarios',
            'pergunta_dependencias', 'opcoes_resposta', 'perguntas', 'temas'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
