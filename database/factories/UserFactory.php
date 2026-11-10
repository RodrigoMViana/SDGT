<?php

namespace Database\Factories;

use App\Enums\Perfil;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'perfil' => Perfil::Administrador,
            'ativo' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Senha123'),
            'remember_token' => Str::random(10),
        ];
    }

    public function perfil(Perfil $perfil, ?int $municipioId = null): static
    {
        return $this->state(fn () => [
            'perfil' => $perfil,
            'municipio_id' => $municipioId,
            'cpf' => $perfil->municipal() ? (string) fake()->unique()->numerify('###########') : null,
            'password' => $perfil->municipal() ? null : (static::$password ??= Hash::make('Senha123')),
        ]);
    }
}
