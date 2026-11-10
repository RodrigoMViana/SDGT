<?php

use App\Services\QuestionarioService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| EP09/EP10 — Rotina diária de marcos dos questionários:
| notificação de início, lembrete de encerramento, notificação de encerramento e
| encerramento automático após o prazo de assinatura.
*/
Artisan::command('sdgt:processar-marcos', function (QuestionarioService $service) {
    $r = $service->processarMarcos();
    $this->info(sprintf('Início: %d | Lembretes: %d | Encerramento: %d | Questionários encerrados: %d',
        $r['inicio'], $r['lembrete'], $r['encerramento'], $r['encerrados']));
})->purpose('Processa os marcos (início/lembrete/encerramento) dos questionários e envia notificações');

Schedule::command('sdgt:processar-marcos')->dailyAt(env('SDGT_HORARIO_ROTINA', '06:00'))->withoutOverlapping();
