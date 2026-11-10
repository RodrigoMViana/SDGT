<?php

namespace Tests;

use Database\Seeders\BancoPerguntasSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\EstruturaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;

/**
 * Testes que dependem da base de demonstração completa (3 ciclos × 139 municípios).
 * Em SQLite usa um arquivo dedicado, migrado e semeado UMA vez por execução; cada teste
 * roda dentro de uma transação revertida ao final (rápido e isolado).
 */
trait UsaBaseDemonstracao
{
    use RefreshDatabase;

    private static bool $baseDemoPronta = false;

    protected function setUp(): void
    {
        if (($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION')) === 'sqlite') {
            $arquivo = dirname(__DIR__).'/database/testing-demo.sqlite';
            if (! self::$baseDemoPronta) {
                if (is_file($arquivo)) {
                    unlink($arquivo);
                }
                touch($arquivo);
            }
            foreach (['DB_DATABASE' => $arquivo] as $k => $v) {
                $_ENV[$k] = $_SERVER[$k] = $v;
                putenv("$k=$v");
            }
        }
        parent::setUp();
    }

    protected function refreshTestDatabase(): void
    {
        if (! self::$baseDemoPronta) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->seed([EstruturaSeeder::class, BancoPerguntasSeeder::class, DemoSeeder::class]);
            self::$baseDemoPronta = true;
            RefreshDatabaseState::$migrated = true;
        }
        $this->beginDatabaseTransaction();
    }

    /** Força as próximas classes (base limpa) a migrarem novamente quando o banco não é em memória (ex.: SQL Server no CI). */
    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION')) !== 'sqlite') {
            return;
        }
        foreach (['DB_DATABASE' => ':memory:'] as $k => $v) {
            $_ENV[$k] = $_SERVER[$k] = $v;
            putenv("$k=$v");
        }
    }
}
