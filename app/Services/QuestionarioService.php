<?php

namespace App\Services;

use App\Models\Preenchimento;
use App\Models\Questionario;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ciclo de vida do questionário: publicação (HU03.06), prorrogação (HU03.09), duplicação (HU03.08),
 * encerramento e marcos de notificação (EP09/EP10), com auditoria (EP12).
 */
class QuestionarioService
{
    public function __construct(
        private AuditoriaService $auditoria,
        private NotificacaoService $notificacoes,
    ) {}

    public function publicar(Questionario $q, User $user): Questionario
    {
        if ($q->status !== 'rascunho') {
            throw ValidationException::withMessages(['status' => 'Somente questionários em rascunho podem ser publicados.']);
        }
        $erros = [];
        if ($q->perguntas()->count() === 0) {
            $erros[] = 'Associe ao menos uma pergunta ao questionário.';
        }
        $semOpcoes = $q->perguntas()->whereIn('tipo', ['unica', 'multipla'])->doesntHave('opcoes')->pluck('codigo');
        if ($semOpcoes->isNotEmpty()) {
            $erros[] = 'Perguntas de escolha sem respostas cadastradas: '.$semOpcoes->implode(', ').'.';
        }
        if ($q->data_fim->lt(today())) {
            $erros[] = 'A data de encerramento do preenchimento já passou. Ajuste as datas antes de publicar.';
        }
        if ($q->abrangencia === 'selecionados' && $q->municipios()->count() === 0) {
            $erros[] = 'Selecione ao menos um município para a abrangência "municípios selecionados".';
        }
        if ($erros) {
            throw ValidationException::withMessages(['publicacao' => $erros]);
        }

        DB::transaction(function () use ($q, $user) {
            $antes = $q->only(['status', 'publicado_em']);
            $q->forceFill(['status' => 'publicado', 'publicado_em' => now(), 'publicado_por' => $user->id])->save();
            $this->auditoria->registrar('publicacao', $q, $q->titulo, $antes, [
                'status' => 'publicado',
                'data_inicio' => $q->data_inicio->toDateString(),
                'data_fim' => $q->data_fim->toDateString(),
                'data_limite_assinatura' => $q->data_limite_assinatura->toDateString(),
                'abrangencia' => $q->abrangencia,
                'municipios' => $q->abrangencia === 'todos' ? 'Todos' : $q->municipios()->pluck('nome')->implode(', '),
                'total_perguntas' => $q->perguntas()->count(),
            ], null, $user);
        });

        if ($q->data_inicio->lte(today())) {
            $this->notificarInicio($q);
        }

        return $q;
    }

    public function prorrogar(Questionario $q, User $user, Carbon $novaDataFim, Carbon $novaDataAssinatura, string $justificativa): Questionario
    {
        if ($q->status === 'rascunho') {
            throw ValidationException::withMessages(['data_fim' => 'Questionário em rascunho deve ser alterado pela edição, não por prorrogação.']);
        }
        if ($novaDataFim->lte($q->data_fim)) {
            throw ValidationException::withMessages(['data_fim' => 'A nova data de encerramento deve ser posterior à atual ('.$q->data_fim->format('d/m/Y').').']);
        }
        if ($novaDataFim->lt(today())) {
            throw ValidationException::withMessages(['data_fim' => 'A nova data de encerramento não pode estar no passado.']);
        }
        if ($novaDataAssinatura->lt($novaDataFim)) {
            throw ValidationException::withMessages(['data_limite_assinatura' => 'O prazo de assinatura deve ser igual ou posterior ao encerramento do preenchimento.']);
        }

        DB::transaction(function () use ($q, $user, $novaDataFim, $novaDataAssinatura, $justificativa) {
            $antes = [
                'status' => $q->status,
                'data_fim' => $q->data_fim->toDateString(),
                'data_limite_assinatura' => $q->data_limite_assinatura->toDateString(),
            ];
            $q->forceFill([
                'data_fim' => $novaDataFim->toDateString(),
                'data_limite_assinatura' => $novaDataAssinatura->toDateString(),
                'status' => 'publicado', // reabre se estava encerrado
            ])->save();
            $this->auditoria->registrar('prorrogacao', $q, $q->titulo, $antes, [
                'status' => 'publicado',
                'data_fim' => $novaDataFim->toDateString(),
                'data_limite_assinatura' => $novaDataAssinatura->toDateString(),
            ], $justificativa, $user);
        });

        $this->notificacoes->notificar(
            $this->notificacoes->destinatariosMunicipais($q),
            'prorrogacao',
            'Prazo prorrogado: '.$q->titulo,
            "O prazo de preenchimento do questionário \"{$q->titulo}\" foi prorrogado até {$novaDataFim->format('d/m/Y')}. "
                ."O prazo final para validação/assinatura passa a ser {$novaDataAssinatura->format('d/m/Y')}.",
            route('municipio.questionarios.index'),
            $q,
        );

        return $q->refresh();
    }

    public function duplicar(Questionario $q, User $user): Questionario
    {
        return DB::transaction(function () use ($q, $user) {
            $novo = $q->replicate(['status', 'publicado_em', 'publicado_por', 'origem_id', 'criado_por']);
            $novo->titulo = mb_substr($q->titulo.' (cópia)', 0, 200);
            $novo->status = 'rascunho';
            $novo->origem_id = $q->id;
            $novo->criado_por = $user->id;
            $novo->save();
            foreach ($q->perguntas as $p) {
                $novo->perguntas()->attach($p->id, ['ordem' => $p->pivot->ordem]);
            }
            $novo->municipios()->sync($q->municipios()->pluck('municipios.id'));

            return $novo;
        });
    }

    public function notificarInicio(Questionario $q): int
    {
        return $this->notificacoes->notificar(
            $this->notificacoes->destinatariosMunicipais($q),
            'inicio_preenchimento',
            'Início do preenchimento: '.$q->titulo,
            "Está aberto o período de preenchimento do questionário \"{$q->titulo}\", de {$q->data_inicio->format('d/m/Y')} "
                ."a {$q->data_fim->format('d/m/Y')}. A validação/assinatura pelo gestor deve ocorrer até {$q->data_limite_assinatura->format('d/m/Y')}.",
            route('municipio.questionarios.index'),
            $q,
            true,
            true,
        );
    }

    /**
     * Rotina diária (agendada): notifica início, lembra a proximidade do encerramento,
     * notifica o encerramento e encerra questionários com prazo de assinatura vencido.
     *
     * @return array<string, int>
     */
    public function processarMarcos(?Carbon $hoje = null): array
    {
        $hoje = ($hoje ?? today())->copy()->startOfDay();
        $res = ['inicio' => 0, 'lembrete' => 0, 'encerramento' => 0, 'encerrados' => 0];
        $diasLembrete = (int) config('sdgt.prazos.dias_lembrete_encerramento');

        foreach (Questionario::where('status', 'publicado')->get() as $q) {
            if ($q->data_inicio->lte($hoje) && $q->data_fim->gte($hoje)) {
                $res['inicio'] += $this->notificarInicio($q);
            }

            $pendentes = $this->municipiosPendentes($q);
            if ($q->data_fim->gte($hoje) && $hoje->diffInDays($q->data_fim, false) <= $diasLembrete && $q->data_inicio->lte($hoje)) {
                $res['lembrete'] += $this->notificacoes->notificar(
                    $this->notificacoes->destinatariosMunicipais($q, ['responsavel', 'gestor'], $pendentes),
                    'lembrete_encerramento',
                    'Lembrete: preenchimento encerra em '.$q->data_fim->format('d/m/Y'),
                    "O período de preenchimento do questionário \"{$q->titulo}\" encerra em {$q->data_fim->format('d/m/Y')}. "
                        .'Conclua o preenchimento e encaminhe para validação do gestor.',
                    route('municipio.questionarios.index'), $q, true, true,
                );
            }

            if ($q->data_fim->lt($hoje)) {
                $res['encerramento'] += $this->notificacoes->notificar(
                    $this->notificacoes->destinatariosMunicipais($q),
                    'encerramento_preenchimento',
                    'Encerrado o preenchimento: '.$q->titulo,
                    "O período de preenchimento do questionário \"{$q->titulo}\" foi encerrado em {$q->data_fim->format('d/m/Y')}. "
                        ."Questionários finalizados podem ser validados pelo gestor até {$q->data_limite_assinatura->format('d/m/Y')}.",
                    route('municipio.questionarios.index'), $q, true, true,
                );
            }

            if ($q->data_limite_assinatura->lt($hoje)) {
                $q->forceFill(['status' => 'encerrado'])->save();
                $this->auditoria->registrar('encerramento', $q, $q->titulo, ['status' => 'publicado'], ['status' => 'encerrado']);
                $res['encerrados']++;
            }
        }

        return $res;
    }

    /** IDs dos municípios abrangidos que ainda não finalizaram o preenchimento. */
    public function municipiosPendentes(Questionario $q): array
    {
        $concluidos = Preenchimento::where('questionario_id', $q->id)
            ->whereIn('status', ['aguardando_validacao', 'validado'])->pluck('municipio_id')->all();

        return $q->municipiosAlvoQuery()->whereNotIn('id', $concluidos)->pluck('id')->all();
    }
}
