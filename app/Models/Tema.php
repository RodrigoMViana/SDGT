<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tema extends Model
{
    protected $table = 'temas';

    protected $fillable = ['nome', 'descricao', 'ordem', 'ativo'];

    protected $casts = ['ativo' => 'boolean', 'ordem' => 'integer'];

    public function perguntas(): HasMany
    {
        return $this->hasMany(Pergunta::class);
    }

    public function scopeAtivos(Builder $q): Builder
    {
        return $q->where('ativo', true);
    }
}
