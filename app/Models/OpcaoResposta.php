<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpcaoResposta extends Model
{
    protected $table = 'opcoes_resposta';

    protected $fillable = ['pergunta_id', 'texto', 'pontuacao', 'ordem', 'solicita_evidencia'];

    protected $casts = ['pontuacao' => 'float', 'ordem' => 'integer', 'solicita_evidencia' => 'boolean'];

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class);
    }
}
