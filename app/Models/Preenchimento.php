<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Preenchimento extends Model
{
    protected $table = 'preenchimentos';

    public const STATUS = [
        'nao_iniciado' => 'Não iniciado',
        'em_preenchimento' => 'Em preenchimento',
        'aguardando_validacao' => 'Aguardando validação do gestor',
        'devolvido' => 'Devolvido para ajustes',
        'validado' => 'Validado e assinado',
    ];

    protected $fillable = [
        'questionario_id', 'municipio_id', 'status', 'responsavel_id', 'gestor_id', 'iniciado_em', 'finalizado_em',
        'validado_em', 'assinatura_metodo', 'assinatura_hash', 'assinatura_detalhes', 'observacao_devolucao',
        'pontuacao_obtida', 'pontuacao_maxima', 'indice_aderencia', 'indices_por_tema',
    ];

    protected $casts = [
        'iniciado_em' => 'datetime',
        'finalizado_em' => 'datetime',
        'validado_em' => 'datetime',
        'pontuacao_obtida' => 'float',
        'pontuacao_maxima' => 'float',
        'indice_aderencia' => 'float',
        'indices_por_tema' => 'array',
    ];

    public function questionario(): BelongsTo
    {
        return $this->belongsTo(Questionario::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(PreenchimentoResposta::class);
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** Responsável pode editar: período aberto e status editável. */
    public function editavel(): bool
    {
        return in_array($this->status, ['em_preenchimento', 'devolvido'], true)
            && $this->questionario->aceitaPreenchimento();
    }

    public function validavel(): bool
    {
        return $this->status === 'aguardando_validacao' && $this->questionario->aceitaValidacao();
    }
}
