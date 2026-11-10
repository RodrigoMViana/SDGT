<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso: ->middleware('perfil:administrador,auditor') */
class VerificarPerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $user = $request->user();
        if (! $user || ! $user->ativo) {
            abort(403, 'Usuário sem acesso.');
        }
        if ($perfis && ! in_array($user->perfil->value, $perfis, true)) {
            abort(403, 'Seu perfil não tem permissão para acessar esta funcionalidade.');
        }

        return $next($request);
    }
}
