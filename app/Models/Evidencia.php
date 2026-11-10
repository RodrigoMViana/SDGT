<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidencia extends Model
{
    protected $table = 'evidencias';

    protected $fillable = [
        'preenchimento_id', 'pergunta_id', 'nome_original', 'caminho', 'mime', 'tamanho', 'hash_sha256', 'enviado_por',
    ];

    public function preenchimento(): BelongsTo
    {
        return $this->belongsTo(Preenchimento::class);
    }

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function tamanhoFormatado(): string
    {
        $b = (int) $this->tamanho;

        return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.').' MB' : number_format($b / 1024, 0, ',', '.').' KB';
    }
}
