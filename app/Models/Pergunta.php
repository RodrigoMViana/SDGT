<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pergunta extends Model
{
    protected $table = 'perguntas';

    public const TIPOS = [
        'unica' => 'Escolha única',
        'multipla' => 'Múltipla escolha',
        'texto' => 'Texto livre',
        'numero' => 'Numérica',
        'data' => 'Data',
    ];

    protected $fillable = [
        'tema_id', 'codigo', 'enunciado', 'ajuda', 'link_apoio', 'tipo', 'obrigatoria',
        'exige_evidencia', 'peso', 'pergunta_pai_id', 'ativo',
    ];

    protected $casts = [
        'obrigatoria' => 'boolean',
        'exige_evidencia' => 'boolean',
        'ativo' => 'boolean',
        'peso' => 'float',
    ];

    public function tema(): BelongsTo
    {
        return $this->belongsTo(Tema::class);
    }

    public function opcoes(): HasMany
    {
        return $this->hasMany(OpcaoResposta::class)->orderBy('ordem')->orderBy('id');
    }

    public function perguntaPai(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class, 'pergunta_pai_id');
    }

    public function dependentes(): HasMany
    {
        return $this->hasMany(Pergunta::class, 'pergunta_pai_id');
    }

    /** Opções da pergunta-pai que habilitam esta pergunta. */
    public function opcoesHabilitadoras(): BelongsToMany
    {
        return $this->belongsToMany(OpcaoResposta::class, 'pergunta_dependencias', 'pergunta_id', 'opcao_resposta_id');
    }

    public function questionarios(): BelongsToMany
    {
        return $this->belongsToMany(Questionario::class, 'questionario_pergunta')->withPivot('ordem');
    }

    public function temOpcoes(): bool
    {
        return in_array($this->tipo, ['unica', 'multipla'], true);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /** Pergunta usada em questionário já publicado/encerrado não pode ter estrutura alterada. */
    public function bloqueadaParaEdicao(): bool
    {
        return $this->questionarios()->whereIn('status', ['publicado', 'encerrado'])->exists();
    }

    public function pontuacaoMaxima(): float
    {
        if (! $this->temOpcoes()) {
            return 0.0;
        }
        $opcoes = $this->relationLoaded('opcoes') ? $this->opcoes : $this->opcoes()->get();
        if ($opcoes->isEmpty()) {
            return 0.0;
        }

        return $this->tipo === 'unica'
            ? (float) $opcoes->max('pontuacao')
            : (float) $opcoes->where('pontuacao', '>', 0)->sum('pontuacao');
    }
}
