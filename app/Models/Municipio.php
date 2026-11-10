<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    protected $table = 'municipios';

    protected $fillable = ['codigo_ibge', 'nome', 'regiao_imediata', 'microrregiao', 'relatoria_id'];

    protected $casts = ['relatoria_id' => 'integer'];

    public function relatoria(): BelongsTo
    {
        return $this->belongsTo(Relatoria::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function preenchimentos(): HasMany
    {
        return $this->hasMany(Preenchimento::class);
    }
}
