<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreenchimentoResposta extends Model
{
    protected $table = 'preenchimento_respostas';

    protected $fillable = ['preenchimento_id', 'pergunta_id', 'opcoes_ids', 'valor_texto', 'pontuacao'];

    protected $casts = ['opcoes_ids' => 'array', 'pontuacao' => 'float', 'preenchimento_id' => 'integer', 'pergunta_id' => 'integer'];

    public function preenchimento(): BelongsTo
    {
        return $this->belongsTo(Preenchimento::class);
    }

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class);
    }

    public function preenchida(): bool
    {
        return ! empty($this->opcoes_ids) || (isset($this->valor_texto) && trim((string) $this->valor_texto) !== '');
    }
}
