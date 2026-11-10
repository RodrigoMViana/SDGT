<?php

namespace App\Models;

use App\Enums\Perfil;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'cpf', 'perfil', 'municipio_id', 'cargo', 'ativo', 'password',
        'ultimo_metodo_login', 'ultimo_acesso_em',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'perfil' => Perfil::class,
        ];
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function notificacoes(): HasMany
    {
        return $this->hasMany(Notificacao::class);
    }

    public function temPerfil(Perfil|string ...$perfis): bool
    {
        foreach ($perfis as $p) {
            $p = $p instanceof Perfil ? $p : Perfil::from($p);
            if ($this->perfil === $p) {
                return true;
            }
        }

        return false;
    }

    public function isTribunal(): bool
    {
        return $this->temPerfil(Perfil::Administrador, Perfil::Auditor);
    }

    public function isMunicipal(): bool
    {
        return $this->temPerfil(Perfil::Responsavel, Perfil::Gestor);
    }

    public function naoLidas(): int
    {
        return $this->notificacoes()->whereNull('lida_em')->count();
    }

    public function cpfFormatado(): ?string
    {
        if (! $this->cpf || strlen($this->cpf) !== 11) {
            return $this->cpf;
        }

        return substr($this->cpf, 0, 3).'.'.substr($this->cpf, 3, 3).'.'.substr($this->cpf, 6, 3).'-'.substr($this->cpf, 9, 2);
    }
}
