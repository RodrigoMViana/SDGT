<?php

namespace App\Integracoes\Contracts;

use App\Models\Preenchimento;
use App\Models\User;

/** Serviço de assinatura (ICP-Brasil disponibilizado pelo TCE/TO). */
interface AssinaturaDigital
{
    /**
     * @return array{metodo: string, hash: string, detalhes: string}
     */
    public function assinar(Preenchimento $preenchimento, User $signatario, string $conteudo): array;
}
