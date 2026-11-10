<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    public function registrar(string $acao, Model $entidade, ?string $descricao = null, array $antes = [], array $depois = [], ?string $justificativa = null, ?User $user = null): Auditoria
    {
        $user ??= auth()->user();
        $req = app()->runningInConsole() ? null : request();

        return Auditoria::create([
            'acao' => $acao,
            'entidade_tipo' => class_basename($entidade),
            'entidade_id' => $entidade->getKey(),
            'entidade_descricao' => $descricao,
            'user_id' => $user?->id,
            'user_nome' => $user?->name ?? 'Sistema (rotina automática)',
            'justificativa' => $justificativa,
            'dados_anteriores' => $antes ?: null,
            'dados_novos' => $depois ?: null,
            'ip' => $req?->ip(),
            'user_agent' => $req ? substr((string) $req->userAgent(), 0, 500) : 'console',
        ]);
    }
}
