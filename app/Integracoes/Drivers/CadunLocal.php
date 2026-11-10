<?php

namespace App\Integracoes\Drivers;

use App\Integracoes\Contracts\CadastroUnico;
use App\Models\User;

/**
 * Driver local: usa a própria tabela de usuários do SDGT como cadastro.
 * Enquanto a API do CADUN não estiver disponível, os usuários são mantidos pelo
 * Administrador em "Usuários" (cadastro provisório).
 */
class CadunLocal implements CadastroUnico
{
    public function buscarPorCpf(string $cpf): ?array
    {
        $u = User::with('municipio')->where('cpf', preg_replace('/\D/', '', $cpf))->first();

        return $u ? [
            'nome' => $u->name,
            'email' => $u->email,
            'cpf' => $u->cpf,
            'perfil' => $u->perfil->value,
            'codigo_ibge' => $u->municipio?->codigo_ibge,
            'cargo' => $u->cargo,
        ] : null;
    }
}
