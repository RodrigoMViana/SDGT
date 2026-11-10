<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Support\Exportador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** EP12 — Auditoria e Rastreabilidade (HU12.02 a HU12.06) */
class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.auditoria.index', [
            'registros' => $this->filtrar($request)->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Auditoria $auditoria): View
    {
        return view('admin.auditoria.show', compact('auditoria'));
    }

    public function listaPdf(Request $request)
    {
        return Exportador::pdf('pdf.auditoria-lista', [
            'registros' => $this->filtrar($request)->limit(5000)->get(),
            'filtros' => $request->only(['acao', 'de', 'ate', 'busca']),
        ], 'auditoria-'.now()->format('Ymd-His').'.pdf', 'landscape');
    }

    public function listaXlsx(Request $request)
    {
        $linhas = $this->filtrar($request)->limit(50000)->get()->map(fn (Auditoria $a) => [
            $a->id, $a->created_at?->format('d/m/Y H:i:s'), $a->acaoLabel(), $a->entidade_descricao,
            $a->user_nome, $a->justificativa, $this->resumo($a->dados_anteriores), $this->resumo($a->dados_novos), $a->ip,
        ]);

        return Exportador::xlsx('Auditoria - Publicação e Prorrogação', [
            'ID', 'Data/hora', 'Ação', 'Questionário', 'Usuário', 'Justificativa', 'Dados anteriores', 'Dados novos', 'IP',
        ], $linhas, 'auditoria-'.now()->format('Ymd-His').'.xlsx');
    }

    public function registroPdf(Auditoria $auditoria)
    {
        return Exportador::pdf('pdf.auditoria-registro', compact('auditoria'), 'auditoria-registro-'.$auditoria->id.'.pdf');
    }

    private function filtrar(Request $request): Builder
    {
        return Auditoria::query()
            ->whereIn('acao', $request->filled('acao') ? [$request->query('acao')] : array_keys(Auditoria::ACOES))
            ->when($request->filled('de'), fn ($q) => $q->where('created_at', '>=', $request->date('de')->startOfDay()))
            ->when($request->filled('ate'), fn ($q) => $q->where('created_at', '<=', $request->date('ate')->endOfDay()))
            ->when($request->filled('busca'), fn ($q) => $q->where(fn ($w) => $w
                ->where('entidade_descricao', 'like', '%'.$request->query('busca').'%')
                ->orWhere('user_nome', 'like', '%'.$request->query('busca').'%')))
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    private function resumo(?array $dados): string
    {
        return collect($dados ?? [])->map(fn ($v, $k) => $k.': '.(is_scalar($v) || $v === null ? $v : json_encode($v, JSON_UNESCAPED_UNICODE)))->implode('; ');
    }
}
