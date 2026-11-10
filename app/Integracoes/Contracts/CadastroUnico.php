<?php

namespace App\Integracoes\Contracts;

/** CADUN - Cadastro Único do Tribunal (fonte oficial dos responsáveis/gestores municipais). */
interface CadastroUnico
{
    /**
     * @return array{nome: string, email: string, cpf: string, perfil: string, codigo_ibge: ?string, cargo: ?string}|null
     */
    public function buscarPorCpf(string $cpf): ?array;
}
