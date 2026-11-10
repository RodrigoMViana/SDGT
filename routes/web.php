<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EvidenciaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\Municipio;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\Tribunal;
use Illuminate\Support\Facades\Route;

/*
| EP01 — Autenticação
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'form'])->name('login');
    Route::post('/login', [LoginController::class, 'senha'])->middleware('throttle:10,1')->name('login.senha');
    Route::get('/login/govbr', [LoginController::class, 'govbr'])->name('login.govbr');
    Route::get('/login/govbr/callback', [LoginController::class, 'govbrCallback'])->name('login.govbr.callback');
    Route::get('/login/certificado', [LoginController::class, 'certificado'])->name('login.certificado');
    Route::post('/login/simulado', [LoginController::class, 'simulado'])->name('login.simulado');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    // EP09 — Notificações
    Route::get('/notificacoes', [NotificacaoController::class, 'index'])->name('notificacoes.index');
    Route::get('/notificacoes/{notificacao}', [NotificacaoController::class, 'show'])->name('notificacoes.show');
    Route::post('/notificacoes/marcar-todas', [NotificacaoController::class, 'marcarTodas'])->name('notificacoes.marcar-todas');

    // EP11 — Materiais de apoio (consulta e download para todos os perfis)
    Route::get('/materiais', [MaterialController::class, 'index'])->name('materiais.index');
    Route::get('/materiais/{material}/download', [MaterialController::class, 'download'])->name('materiais.download');

    // EP06 — Download de evidência (autorização no controller)
    Route::get('/evidencias/{evidencia}/download', [EvidenciaController::class, 'download'])->name('evidencias.download');

    /*
    | Administrador do Sistema
    */
    Route::middleware('perfil:administrador')->prefix('admin')->name('admin.')->group(function () {
        // EP02 — Temas
        Route::resource('temas', Admin\TemaController::class)->except('show');
        Route::patch('temas/{tema}/status', [Admin\TemaController::class, 'alternarStatus'])->name('temas.status');

        // EP04 — Perguntas, respostas e dependências
        Route::resource('perguntas', Admin\PerguntaController::class)->parameters(['perguntas' => 'pergunta']);
        Route::post('perguntas/{pergunta}/opcoes', [Admin\OpcaoRespostaController::class, 'store'])->name('opcoes.store');
        Route::put('opcoes/{opcao}', [Admin\OpcaoRespostaController::class, 'update'])->name('opcoes.update');
        Route::delete('opcoes/{opcao}', [Admin\OpcaoRespostaController::class, 'destroy'])->name('opcoes.destroy');
        Route::put('perguntas/{pergunta}/dependencia', [Admin\PerguntaController::class, 'dependencia'])->name('perguntas.dependencia');

        // EP03 — Questionários
        Route::resource('questionarios', Admin\QuestionarioController::class);
        Route::get('questionarios/{questionario}/perguntas', [Admin\QuestionarioController::class, 'perguntas'])->name('questionarios.perguntas');
        Route::put('questionarios/{questionario}/perguntas', [Admin\QuestionarioController::class, 'associarPerguntas'])->name('questionarios.perguntas.salvar');
        Route::post('questionarios/{questionario}/publicar', [Admin\QuestionarioController::class, 'publicar'])->name('questionarios.publicar');
        Route::post('questionarios/{questionario}/prorrogar', [Admin\QuestionarioController::class, 'prorrogar'])->name('questionarios.prorrogar');
        Route::post('questionarios/{questionario}/duplicar', [Admin\QuestionarioController::class, 'duplicar'])->name('questionarios.duplicar');
        Route::get('questionarios/{questionario}/pdf', [Admin\QuestionarioController::class, 'pdf'])->name('questionarios.pdf');

        // EP11 — Materiais de apoio (gestão)
        Route::resource('materiais', Admin\MaterialApoioController::class)->except('show')->parameters(['materiais' => 'material']);

        // EP12 — Auditoria
        Route::get('auditoria', [Admin\AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('auditoria/exportar/pdf', [Admin\AuditoriaController::class, 'listaPdf'])->name('auditoria.pdf');
        Route::get('auditoria/exportar/xlsx', [Admin\AuditoriaController::class, 'listaXlsx'])->name('auditoria.xlsx');
        Route::get('auditoria/{auditoria}', [Admin\AuditoriaController::class, 'show'])->name('auditoria.show');
        Route::get('auditoria/{auditoria}/pdf', [Admin\AuditoriaController::class, 'registroPdf'])->name('auditoria.registro-pdf');

        // Cadastros de apoio (provisórios até integração com o CADUN)
        Route::resource('usuarios', Admin\UsuarioController::class)->except('show')->parameters(['usuarios' => 'usuario']);
        Route::get('municipios', [Admin\MunicipioController::class, 'index'])->name('municipios.index');
        Route::put('municipios/{municipio}', [Admin\MunicipioController::class, 'update'])->name('municipios.update');
    });

    /*
    | Tribunal (Administrador e Auditor) — EP07 Painel e EP08 Relatórios
    */
    Route::middleware('perfil:administrador,auditor')->prefix('tribunal')->name('tribunal.')->group(function () {
        Route::get('painel', [Tribunal\PainelController::class, 'panorama'])->name('painel');
        Route::get('painel/comparar', [Tribunal\PainelController::class, 'comparar'])->name('painel.comparar');
        Route::get('painel/relatorias', [Tribunal\PainelController::class, 'relatorias'])->name('painel.relatorias');
        Route::get('painel/evolucao', [Tribunal\PainelController::class, 'evolucao'])->name('painel.evolucao');
        Route::get('preenchimentos/{preenchimento}', [Tribunal\PainelController::class, 'preenchimento'])->name('preenchimentos.show');
        Route::get('preenchimentos/{preenchimento}/pdf', [Tribunal\PainelController::class, 'preenchimentoPdf'])->name('preenchimentos.pdf');

        Route::get('relatorios/resultados', [Tribunal\RelatorioController::class, 'resultados'])->name('relatorios.resultados');
        Route::get('relatorios/resultados/pdf', [Tribunal\RelatorioController::class, 'pdf'])->name('relatorios.pdf');
        Route::get('relatorios/resultados/xlsx', [Tribunal\RelatorioController::class, 'xlsx'])->name('relatorios.xlsx');
    });

    /*
    | Município (Responsável e Gestor) — EP05 e EP06
    */
    Route::middleware('perfil:responsavel,gestor')->prefix('municipio')->name('municipio.')->group(function () {
        Route::get('questionarios', [Municipio\QuestionarioController::class, 'index'])->name('questionarios.index');
        Route::get('questionarios/{questionario}', [Municipio\QuestionarioController::class, 'apresentacao'])->name('questionarios.apresentacao');
        Route::post('questionarios/{questionario}/iniciar', [Municipio\QuestionarioController::class, 'iniciar'])
            ->middleware('perfil:responsavel')->name('questionarios.iniciar');

        Route::get('preenchimentos/{preenchimento}', [Municipio\PreenchimentoController::class, 'show'])->name('preenchimentos.show');
        Route::get('preenchimentos/{preenchimento}/pdf', [Municipio\PreenchimentoController::class, 'pdf'])->name('preenchimentos.pdf');

        Route::middleware('perfil:responsavel')->group(function () {
            Route::get('preenchimentos/{preenchimento}/editar', [Municipio\PreenchimentoController::class, 'edit'])->name('preenchimentos.edit');
            Route::put('preenchimentos/{preenchimento}', [Municipio\PreenchimentoController::class, 'update'])->name('preenchimentos.update');
            Route::get('preenchimentos/{preenchimento}/revisar', [Municipio\PreenchimentoController::class, 'revisar'])->name('preenchimentos.revisar');
            Route::post('preenchimentos/{preenchimento}/finalizar', [Municipio\PreenchimentoController::class, 'finalizar'])->name('preenchimentos.finalizar');
            Route::post('preenchimentos/{preenchimento}/evidencias', [EvidenciaController::class, 'store'])->name('evidencias.store');
            Route::delete('evidencias/{evidencia}', [EvidenciaController::class, 'destroy'])->name('evidencias.destroy');
        });

        Route::middleware('perfil:gestor')->group(function () {
            Route::post('preenchimentos/{preenchimento}/validar', [Municipio\PreenchimentoController::class, 'validar'])->name('preenchimentos.validar');
            Route::post('preenchimentos/{preenchimento}/devolver', [Municipio\PreenchimentoController::class, 'devolver'])->name('preenchimentos.devolver');
        });
    });
});
