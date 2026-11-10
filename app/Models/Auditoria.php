<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    public const UPDATED_AT = null;

    public const ACOES = [
        'publicacao' => 'Publicação de questionário',
        'prorrogacao' => 'Prorrogação de prazo',
        'encerramento' => 'Encerramento automático',
        'validacao' => 'Validação/assinatura de questionário',
    ];

    protected $fillable = [
        'acao', 'entidade_tipo', 'entidade_id', 'entidade_descricao', 'user_id', 'user_nome', 'justificativa',
        'dados_anteriores', 'dados_novos', 'ip', 'user_agent',
    ];

    protected $casts = ['dados_anteriores' => 'array', 'dados_novos' => 'array', 'created_at' => 'datetime', 'entidade_id' => 'integer', 'user_id' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acaoLabel(): string
    {
        return self::ACOES[$this->acao] ?? $this->acao;
    }
}
