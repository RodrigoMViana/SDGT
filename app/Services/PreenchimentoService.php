<?php

namespace App\Services;

use App\Enums\Perfil;
use App\Integracoes\Contracts\AlertaFiscalizacao;
use App\Integracoes\Contracts\AssinaturaDigital;
use App\Models\Pergunta;
use App\Models\Preenchimento;
use App\Models\PreenchimentoResposta;
use App\Models\Questionario;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * EP05 — preenchimento, finalização e validação (assinatura) dos questionários.
 */
class PreenchimentoService
{
    public function __construct(
        private PontuacaoService $pontuacao,
        private NotificacaoService $notificacoes,
        private AuditoriaService $auditoria,
        private AssinaturaDigital $assinatura,
        private AlertaFiscalizacao $sfg,
    ) {}

    public function obterOuCriar(Questionario $q, int $municipioId, ?User $responsavel = null): Preenchimento
    {
        return Preenchimento::firstOrCreate(
            ['questionario_id' => $q->id, 'municipio_id' => $municipioId],
            ['status' => 'em_preenchimento', 'responsavel_id' => $responsavel?->id, 'iniciado_em' => now()],
        );
    }

    /**
     * Salva (parcialmente) as respostas. Formato de $dados:
     *   [pergunta_id => ['opcoes' => [ids] | 'opcao' => id | 'valor' => string]]
     * Respostas de perguntas desabilitadas pela dependência são descartadas.
     */
    public function salvar(Preenchimento $p, array $dados, User $user): Preenchimento
    {
        if (! $p->editavel()) {
            throw ValidationException::withMessages(['preenchimento' => 'Este questionário não está disponível para edição.']);
        }
        $perguntas = $this->pontuacao->perguntasDoQuestionario($p->questionario);

        DB::transaction(function () use ($p, $dados, $user, $perguntas) {
            $normalizadas = [];
            foreach ($perguntas as $pergunta) {
                if (! array_key_exists($pergunta->id, $dados) && ! array_key_exists((string) $pergunta->id, $dados)) {
                    continue;
                }
                $normalizadas[$pergunta->id] = $this->normalizar($pergunta, (array) ($dados[$pergunta->id] ?? []));
            }
            // mescla com respostas já gravadas para avaliar dependências corretamente
            $atuais = $this->pontuacao->respostasArray($p);
            $mapa = array_replace($atuais, $normalizadas);

            foreach ($perguntas as $pergunta) {
                $habilitada = $this->pontuacao->perguntaHabilitada($pergunta, $perguntas, $mapa);
                if (! $habilitada) {
                    PreenchimentoResposta::where('preenchimento_id', $p->id)->where('pergunta_id', $pergunta->id)->delete();
                    unset($mapa[$pergunta->id]);

                    continue;
                }
                if (! isset($normalizadas[$pergunta->id])) {
                    continue;
                }
                $r = $normalizadas[$pergunta->id];
                PreenchimentoResposta::updateOrCreate(
                    ['preenchimento_id' => $p->id, 'pergunta_id' => $pergunta->id],
                    ['opcoes_ids' => $r['opcoes_ids'], 'valor_texto' => $r['valor_texto']],
                );
            }

            $p->forceFill([
                'responsavel_id' => $p->responsavel_id ?? $user->id,
                'iniciado_em' => $p->iniciado_em ?? now(),
                'status' => $p->status === 'devolvido' ? 'devolvido' : 'em_preenchimento',
            ])->save();
        });

        return $this->pontuacao->recalcular($p->refresh());
    }

    /** @return array{opcoes_ids: ?array, valor_texto: ?string} */
    private function normalizar(Pergunta $pergunta, array $entrada): array
    {
        $validas = $pergunta->opcoes->pluck('id')->map(fn ($i) => (int) $i)->all();
        switch ($pergunta->tipo) {
            case 'unica':
                $id = isset($entrada['opcao']) && $entrada['opcao'] !== '' ? (int) $entrada['opcao'] : null;
                if ($id !== null && ! in_array($id, $validas, true)) {
                    throw ValidationException::withMessages(["respostas.{$pergunta->id}" => "Opção inválida para a pergunta {$pergunta->codigo}."]);
                }

                return ['opcoes_ids' => $id ? [$id] : null, 'valor_texto' => null];
            case 'multipla':
                $ids = array_values(array_unique(array_map('intval', (array) ($entrada['opcoes'] ?? []))));
                if (array_diff($ids, $validas)) {
                    throw ValidationException::withMessages(["respostas.{$pergunta->id}" => "Opção inválida para a pergunta {$pergunta->codigo}."]);
                }

                return ['opcoes_ids' => $ids ?: null, 'valor_texto' => null];
            case 'numero':
                $v = trim((string) ($entrada['valor'] ?? ''));
                $v = str_replace(',', '.', $v);
                if ($v !== '' && ! is_numeric($v)) {
                    throw ValidationException::withMessages(["respostas.{$pergunta->id}" => "Informe um número válido na pergunta {$pergunta->codigo}."]);
                }

                return ['opcoes_ids' => null, 'valor_texto' => $v === '' ? null : $v];
            case 'data':
                $v = trim((string) ($entrada['valor'] ?? ''));
                if ($v !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                    throw ValidationException::withMessages(["respostas.{$pergunta->id}" => "Informe uma data válida na pergunta {$pergunta->codigo}."]);
                }

                return ['opcoes_ids' => null, 'valor_texto' => $v === '' ? null : $v];
            default:
                $v = trim((string) ($entrada['valor'] ?? ''));

                return ['opcoes_ids' => null, 'valor_texto' => $v === '' ? null : mb_substr($v, 0, 10000)];
        }
    }

    /**
     * Pendências que impedem a finalização (HU05.04).
     *
     * @return array<int, string>
     */
    public function pendencias(Preenchimento $p): array
    {
        $perguntas = $this->pontuacao->perguntasDoQuestionario($p->questionario);
        $respostas = $this->pontuacao->respostasArray($p);
        $evidencias = $p->evidencias()->get()->groupBy('pergunta_id');
        $pend = [];
        foreach ($perguntas as $pergunta) {
            if (! $this->pontuacao->perguntaHabilitada($pergunta, $perguntas, $respostas)) {
                continue;
            }
            $r = $respostas[$pergunta->id] ?? null;
            $preenchida = $r && (! empty($r['opcoes_ids']) || (isset($r['valor_texto']) && $r['valor_texto'] !== ''));
            if ($pergunta->obrigatoria && ! $preenchida) {
                $pend[] = "{$pergunta->codigo}: resposta obrigatória não informada.";

                continue;
            }
            if ($this->evidenciaExigida($pergunta, $r) && ! $evidencias->has($pergunta->id)) {
                $pend[] = "{$pergunta->codigo}: anexe o documento comprobatório (evidência).";
            }
        }

        return $pend;
    }

    public function evidenciaExigida(Pergunta $pergunta, ?array $resposta): bool
    {
        if (! $resposta || (empty($resposta['opcoes_ids']) && ($resposta['valor_texto'] ?? null) === null)) {
            return false;
        }
        if ($pergunta->exige_evidencia) {
            return true;
        }
        $marcadas = array_map('intval', (array) ($resposta['opcoes_ids'] ?? []));

        return $pergunta->opcoes->whereIn('id', $marcadas)->contains(fn ($o) => $o->solicita_evidencia);
    }

    public function finalizar(Preenchimento $p, User $user): Preenchimento
    {
        if (! $p->editavel()) {
            throw ValidationException::withMessages(['preenchimento' => 'Este questionário não está disponível para finalização.']);
        }
        $pend = $this->pendencias($p);
        if ($pend) {
            throw ValidationException::withMessages(['pendencias' => $pend]);
        }
        $p->forceFill([
            'status' => 'aguardando_validacao',
            'finalizado_em' => now(),
            'responsavel_id' => $user->id,
            'observacao_devolucao' => null,
        ])->save();
        $this->pontuacao->recalcular($p);

        $this->notificacoes->notificar(
            $this->notificacoes->usuariosDoMunicipio($p->municipio_id, Perfil::Gestor),
            'aguardando_validacao',
            'Questionário aguardando sua validação',
            "O responsável municipal {$user->name} finalizou o preenchimento do questionário \"{$p->questionario->titulo}\". "
                ."Revise as informações e realize a validação/assinatura até {$p->questionario->data_limite_assinatura->format('d/m/Y')}.",
            route('municipio.preenchimentos.show', $p),
            $p->questionario,
        );

        return $p;
    }

    public function validar(Preenchimento $p, User $gestor): Preenchimento
    {
        if (! $p->validavel()) {
            throw ValidationException::withMessages(['preenchimento' => 'Este questionário não está disponível para validação.']);
        }
        $this->pontuacao->recalcular($p);
        $assinatura = $this->assinatura->assinar($p, $gestor, $this->conteudoCanonico($p));
        $p->forceFill([
            'status' => 'validado',
            'gestor_id' => $gestor->id,
            'validado_em' => now(),
            'assinatura_metodo' => $assinatura['metodo'],
            'assinatura_hash' => $assinatura['hash'],
            'assinatura_detalhes' => $assinatura['detalhes'],
        ])->save();

        $this->auditoria->registrar('validacao', $p, $p->municipio->nome.' — '.$p->questionario->titulo, [], [
            'status' => 'validado', 'assinatura_metodo' => $assinatura['metodo'], 'assinatura_hash' => $assinatura['hash'],
            'indice_aderencia' => $p->indice_aderencia,
        ], null, $gestor);

        $this->notificacoes->notificar(
            $this->notificacoes->usuariosDoMunicipio($p->municipio_id, Perfil::Responsavel),
            'validacao',
            'Questionário validado pelo gestor',
            "O questionário \"{$p->questionario->titulo}\" foi validado e assinado por {$gestor->name}.",
            route('municipio.preenchimentos.show', $p),
            $p->questionario,
        );

        $faixa = PontuacaoService::faixa($p->indice_aderencia);
        if ($faixa === 'baixa') {
            $this->sfg->emitirAlerta($p, $faixa);
        }

        return $p;
    }

    public function devolver(Preenchimento $p, User $gestor, string $motivo): Preenchimento
    {
        if (! $p->validavel()) {
            throw ValidationException::withMessages(['preenchimento' => 'Este questionário não pode ser devolvido.']);
        }
        if (! $p->questionario->aceitaPreenchimento()) {
            throw ValidationException::withMessages(['preenchimento' => 'O período de preenchimento já encerrou; não é possível devolver para ajustes.']);
        }
        $p->forceFill(['status' => 'devolvido', 'observacao_devolucao' => $motivo, 'finalizado_em' => null])->save();
        $this->notificacoes->notificar(
            $this->notificacoes->usuariosDoMunicipio($p->municipio_id, Perfil::Responsavel),
            'devolucao',
            'Questionário devolvido para ajustes',
            "O gestor {$gestor->name} devolveu o questionário \"{$p->questionario->titulo}\" para ajustes. Motivo: {$motivo}",
            route('municipio.preenchimentos.edit', $p),
            $p->questionario,
        );

        return $p;
    }

    /** Representação canônica (estável) das respostas, usada para o hash da assinatura. */
    public function conteudoCanonico(Preenchimento $p): string
    {
        $linhas = [
            'questionario='.$p->questionario_id,
            'municipio='.$p->municipio->codigo_ibge,
        ];
        foreach ($p->respostas()->orderBy('pergunta_id')->get() as $r) {
            $linhas[] = $r->pergunta_id.'='.json_encode($r->opcoes_ids).'|'.$r->valor_texto;
        }
        foreach ($p->evidencias()->orderBy('id')->get() as $e) {
            $linhas[] = 'evidencia='.$e->pergunta_id.'|'.$e->hash_sha256;
        }

        return implode("\n", $linhas);
    }

    /**
     * Estrutura para exibição (tela, PDF): perguntas agrupadas por tema com resposta/estado.
     *
     * @return Collection<int, array{tema: string, itens: array}>
     */
    public function estrutura(Questionario $q, ?Preenchimento $p): Collection
    {
        $perguntas = $this->pontuacao->perguntasDoQuestionario($q);
        $respostas = $p ? $this->pontuacao->respostasArray($p) : [];
        $evidencias = $p ? $p->evidencias()->get()->groupBy('pergunta_id') : collect();

        return $perguntas->groupBy('tema_id')->map(function ($grupo) use ($perguntas, $respostas, $evidencias) {
            return [
                'tema' => $grupo->first()->tema?->nome,
                'itens' => $grupo->map(function (Pergunta $pg) use ($perguntas, $respostas, $evidencias) {
                    $r = $respostas[$pg->id] ?? null;
                    $marcadas = array_map('intval', (array) ($r['opcoes_ids'] ?? []));

                    return [
                        'pergunta' => $pg,
                        'habilitada' => $this->pontuacao->perguntaHabilitada($pg, $perguntas, $respostas),
                        'opcoes_ids' => $marcadas,
                        'valor' => $r['valor_texto'] ?? null,
                        'texto_resposta' => $pg->temOpcoes()
                            ? $pg->opcoes->whereIn('id', $marcadas)->pluck('texto')->implode('; ')
                            : ($pg->tipo === 'data' && ! empty($r['valor_texto']) ? Carbon::parse($r['valor_texto'])->format('d/m/Y') : ($r['valor_texto'] ?? null)),
                        'evidencias' => $evidencias->get($pg->id, collect()),
                        'evidencia_exigida' => $this->evidenciaExigida($pg, $r),
                    ];
                })->values()->all(),
            ];
        })->values();
    }
}
