<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** EP09 — Gestão de Notificações */
class NotificacaoController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->user()->notificacoes()->latest();
        if ($request->query('filtro') === 'nao_lidas') {
            $q->whereNull('lida_em');
        }

        return view('notificacoes.index', ['notificacoes' => $q->paginate(15)->withQueryString()]);
    }

    public function show(Request $request, Notificacao $notificacao): View
    {
        abort_unless($notificacao->user_id === $request->user()->id, 403);
        if (! $notificacao->lida_em) {
            $notificacao->forceFill(['lida_em' => now()])->save();
        }

        return view('notificacoes.show', compact('notificacao'));
    }

    public function marcarTodas(Request $request): RedirectResponse
    {
        $request->user()->notificacoes()->whereNull('lida_em')->update(['lida_em' => now()]);

        return back()->with('sucesso', 'Todas as notificações foram marcadas como lidas.');
    }
}
