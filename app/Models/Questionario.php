<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Questionario extends Model
{
    protected $table = 'questionarios';

    public const SITUACOES = [
        'rascunho' => 'Rascunho',
        'agendado' => 'Publicado (aguardando início)',
        'aberto' => 'Em preenchimento',
        'assinatura' => 'Período de assinatura',
        'encerrado' => 'Encerrado',
    ];

    protected $fillable = [
        'titulo', 'descricao', 'apresentacao', 'ciclo', 'data_inicio', 'data_fim', 'data_limite_assinatura',
        'status', 'abrangencia', 'publicado_em', 'publicado_por', 'origem_id', 'criado_por',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'data_limite_assinatura' => 'date',
        'publicado_em' => 'datetime',
    ];

    public function perguntas(): BelongsToMany
    {
        return $this->belongsToMany(Pergunta::class, 'questionario_pergunta')
            ->withPivot('ordem')->orderByPivot('ordem');
    }

    public function municipios(): BelongsToMany
    {
        return $this->belongsToMany(Municipio::class, 'questionario_municipio');
    }

    public function preenchimentos(): HasMany
    {
        return $this->hasMany(Preenchimento::class);
    }

    public function publicador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicado_por');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(Questionario::class, 'origem_id');
    }

    public function scopePublicados(Builder $q): Builder
    {
        return $q->whereIn('status', ['publicado', 'encerrado']);
    }

    /** Questionários disponíveis para um município (publicados e na abrangência). */
    public function scopeParaMunicipio(Builder $q, int $municipioId): Builder
    {
        return $q->publicados()->where(function (Builder $w) use ($municipioId) {
            $w->where('abrangencia', 'todos')
                ->orWhereHas('municipios', fn ($m) => $m->where('municipios.id', $municipioId));
        });
    }

    public function situacao(?Carbon $hoje = null): string
    {
        $hoje = ($hoje ?? now())->copy()->startOfDay();
        if ($this->status === 'rascunho') {
            return 'rascunho';
        }
        if ($this->status === 'encerrado') {
            return 'encerrado';
        }
        if ($hoje->lt($this->data_inicio)) {
            return 'agendado';
        }
        if ($hoje->lte($this->data_fim)) {
            return 'aberto';
        }
        if ($hoje->lte($this->data_limite_assinatura)) {
            return 'assinatura';
        }

        return 'encerrado';
    }

    public function situacaoLabel(): string
    {
        return self::SITUACOES[$this->situacao()];
    }

    public function aceitaPreenchimento(): bool
    {
        return $this->situacao() === 'aberto';
    }

    public function aceitaValidacao(): bool
    {
        return in_array($this->situacao(), ['aberto', 'assinatura'], true);
    }

    public function editavel(): bool
    {
        return $this->status === 'rascunho';
    }

    public function municipiosAlvoQuery(): Builder
    {
        return $this->abrangencia === 'todos'
            ? Municipio::query()
            : Municipio::query()->whereIn('id', $this->municipios()->pluck('municipios.id'));
    }

    public function abrangeMunicipio(int $municipioId): bool
    {
        return $this->abrangencia === 'todos' || $this->municipios()->where('municipios.id', $municipioId)->exists();
    }
}
