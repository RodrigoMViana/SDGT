<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialApoio extends Model
{
    protected $table = 'materiais_apoio';

    public const CATEGORIAS = [
        'cartilha' => 'Cartilha',
        'manual' => 'Manual',
        'normativo' => 'Normativo / Portaria',
        'outro' => 'Outro',
    ];

    protected $fillable = [
        'titulo', 'descricao', 'categoria', 'arquivo_caminho', 'arquivo_nome', 'arquivo_mime', 'arquivo_tamanho',
        'url_externa', 'ativo', 'criado_por',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function categoriaLabel(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria;
    }
}
