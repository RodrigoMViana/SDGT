<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    protected $table = 'notificacoes';

    public const TIPOS = [
        'inicio_preenchimento' => 'Início do período de preenchimento',
        'lembrete_encerramento' => 'Lembrete de encerramento',
        'encerramento_preenchimento' => 'Encerramento do período de preenchimento',
        'prorrogacao' => 'Prorrogação de prazo',
        'aguardando_validacao' => 'Questionário aguardando validação',
        'devolucao' => 'Questionário devolvido para ajustes',
        'validacao' => 'Questionário validado',
        'geral' => 'Aviso',
    ];

    protected $fillable = ['user_id', 'tipo', 'titulo', 'mensagem', 'link', 'questionario_id', 'lida_em', 'email_enviado_em'];

    protected $casts = ['lida_em' => 'datetime', 'email_enviado_em' => 'datetime', 'user_id' => 'integer', 'questionario_id' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questionario(): BelongsTo
    {
        return $this->belongsTo(Questionario::class);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
