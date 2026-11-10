<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relatorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('conselheiro', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_ibge', 7)->unique();
            $table->string('nome', 120);
            $table->string('regiao_imediata', 120)->nullable();
            $table->string('microrregiao', 120)->nullable();
            $table->foreignId('relatoria_id')->nullable()->constrained('relatorias')->nullOnDelete();
            $table->timestamps();
            $table->index('nome');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            // CPF não é UNIQUE no banco porque o SQL Server só permite um NULL em índices únicos;
            // a unicidade é garantida pela aplicação (FormRequest + Rule::unique).
            $table->string('cpf', 11)->nullable()->index();
            $table->string('perfil', 20)->index(); // administrador | auditor | responsavel | gestor
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            $table->string('cargo', 120)->nullable();
            $table->boolean('ativo')->default(true);
            $table->string('ultimo_metodo_login', 20)->nullable(); // senha | govbr | certificado
            $table->timestamp('ultimo_acesso_em')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('municipios');
        Schema::dropIfExists('relatorias');
    }
};
