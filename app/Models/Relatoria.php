<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Relatoria extends Model
{
    protected $table = 'relatorias';

    protected $fillable = ['nome', 'conselheiro'];

    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class);
    }
}
