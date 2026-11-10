<?php

namespace App\Services;

use App\Enums\Perfil;
use App\Mail\NotificacaoMail;
use App\Models\Notificacao;
use App\Models\Questionario;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * EP09 (notificações no sistema) + EP10 (notificações por e-mail).
 */
class NotificacaoService
{
    /**
     * Cria a notificação para cada usuário e envia e-mail.
     * Com $unica = true não duplica notificações do mesmo tipo/questionário para o mesmo usuário.
     *
     * @param  iterable<User>  $usuarios
     */
    public function notificar(iterable $usuarios, string $tipo, string $titulo, string $mensagem, ?string $link = null, ?Questionario $questionario = null, bool $email = true, bool $unica = false): int
    {
        $enviadas = 0;
        foreach ($usuarios as $user) {
            if (! $user->ativo) {
                continue;
            }
            if ($unica && $questionario && Notificacao::where('user_id', $user->id)->where('tipo', $tipo)
                ->where('questionario_id', $questionario->id)->exists()) {
                continue;
            }
            $n = Notificacao::create([
                'user_id' => $user->id,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensagem' => $mensagem,
                'link' => $link,
                'questionario_id' => $questionario?->id,
            ]);
            if ($email && $user->email) {
                try {
                    Mail::to($user->email)->queue(new NotificacaoMail($n));
                    $n->forceFill(['email_enviado_em' => now()])->save();
                } catch (\Throwable $e) {
                    Log::warning('Falha ao enfileirar e-mail de notificação', ['notificacao' => $n->id, 'erro' => $e->getMessage()]);
                }
            }
            $enviadas++;
        }

        return $enviadas;
    }

    /** Responsáveis e gestores ativos dos municípios abrangidos pelo questionário. */
    public function destinatariosMunicipais(Questionario $q, array $perfis = ['responsavel', 'gestor'], ?array $municipioIds = null): Collection
    {
        $query = User::query()->where('ativo', true)->whereIn('perfil', $perfis)->whereNotNull('municipio_id');
        if ($q->abrangencia === 'selecionados') {
            $query->whereIn('municipio_id', $q->municipios()->pluck('municipios.id'));
        }
        if ($municipioIds !== null) {
            $query->whereIn('municipio_id', $municipioIds);
        }

        return $query->get();
    }

    public function usuariosDoMunicipio(int $municipioId, Perfil $perfil): Collection
    {
        return User::where('municipio_id', $municipioId)->where('perfil', $perfil->value)->where('ativo', true)->get();
    }
}
