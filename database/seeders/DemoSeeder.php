<?php

namespace Database\Seeders;

use App\Enums\Perfil;
use App\Integracoes\Contracts\CalendarioOficial;
use App\Models\Auditoria;
use App\Models\Evidencia;
use App\Models\MaterialApoio;
use App\Models\Municipio;
use App\Models\Notificacao;
use App\Models\Pergunta;
use App\Models\Preenchimento;
use App\Models\PreenchimentoResposta;
use App\Models\Questionario;
use App\Models\User;
use App\Services\PontuacaoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Base de DEMONSTRAÇÃO (fictícia). Datas relativas a "hoje" para que o ciclo 2026 esteja sempre aberto.
 * Usuários de demonstração (login simulado):
 *   admin@tceto.tc.br / Sdgt@2026 · auditor@tceto.tc.br / Sdgt@2026
 *   Responsável e gestor fictícios para Palmas, Araguaína, Gurupi, Porto Nacional e Paraíso do Tocantins.
 */
class DemoSeeder extends Seeder
{
    private PontuacaoService $pontuacao;

    private string $evidenciaModelo;

    public function run(PontuacaoService $pontuacao): void
    {
        DB::transaction(fn () => $this->executar($pontuacao));
    }

    private function executar(PontuacaoService $pontuacao): void
    {
        mt_srand(2026);
        $this->pontuacao = $pontuacao;
        $admin = User::where('perfil', 'administrador')->first();
        $hoje = today();

        User::firstOrCreate(['email' => 'auditor@tceto.tc.br'], [
            'name' => 'Auditor de Controle Externo (demonstração)', 'perfil' => Perfil::Auditor, 'password' => 'Sdgt@2026', 'ativo' => true,
        ]);

        // Usuários municipais fictícios (CPFs gerados com dígitos verificadores válidos, sem correspondência real intencional)
        $demo = ['Palmas', 'Araguaína', 'Gurupi', 'Porto Nacional', 'Paraíso do Tocantins'];
        $usuarios = [];
        foreach ($demo as $i => $nome) {
            $m = Municipio::where('nome', $nome)->firstOrFail();
            $slug = str($nome)->slug('.');
            $usuarios[$nome]['responsavel'] = User::firstOrCreate(['email' => "responsavel.{$slug}@demo.sdgt"], [
                'name' => "Responsável Tributário — {$nome}", 'cpf' => self::cpf(100000000 + $i * 2), 'perfil' => Perfil::Responsavel,
                'municipio_id' => $m->id, 'cargo' => 'Secretário(a) Municipal de Fazenda', 'ativo' => true,
            ]);
            $usuarios[$nome]['gestor'] = User::firstOrCreate(['email' => "gestor.{$slug}@demo.sdgt"], [
                'name' => "Gestor Municipal — {$nome}", 'cpf' => self::cpf(100000001 + $i * 2), 'perfil' => Perfil::Gestor,
                'municipio_id' => $m->id, 'cargo' => 'Prefeito(a) Municipal', 'ativo' => true,
            ]);
        }

        $this->evidenciaModelo = 'evidencias/demo/documento-comprobatorio-exemplo.pdf';
        Storage::disk(config('sdgt.evidencias.disk'))->put($this->evidenciaModelo,
            Pdf::loadHTML('<h2>Documento comprobatório (exemplo)</h2><p>Arquivo fictício gerado para a base de demonstração do SDGT.</p>')->output());

        $todas = Pergunta::orderBy('id')->get();
        $piloto = $todas->whereIn('codigo', ['LT01', 'LT03', 'LT04', 'RH01', 'RH02', 'RH03', 'TI01', 'TI02', 'PF01', 'PF02', 'CC01', 'CC03', 'GT01', 'GT03']);
        $apresentacao = "Este diagnóstico é instituído pelo Tribunal de Contas do Estado do Tocantins para acompanhar a adequação dos municípios jurisdicionados à Reforma Tributária (Emenda Constitucional nº 132/2023 e Lei Complementar nº 214/2025).\n\n"
            ."O preenchimento é obrigatório e deve ser realizado pelo servidor responsável pela gestão tributária municipal, com validação e assinatura do gestor (Prefeito ou autoridade designada).\n\n"
            ."Prazos: o período de preenchimento é de 15 dias corridos, acrescido de 1 dia útil para a validação/assinatura. Após o encerramento, as informações ficam disponíveis apenas para consulta.\n\n"
            .'Anexe preferencialmente documentos em PDF textual (pesquisável), evitando imagens digitalizadas.';

        $ciclos = [
            ['Diagnóstico da Gestão Tributária Municipal — Ciclo 2024 (piloto)', '2024', $hoje->copy()->subYears(2)->subDays(10), $piloto, 0.70, 0.0],
            ['Diagnóstico da Gestão Tributária Municipal — Ciclo 2025', '2025', $hoje->copy()->subYear()->subDays(10), $todas, 0.88, 0.10],
            ['Diagnóstico da Gestão Tributária Municipal — Ciclo 2026', '2026', $hoje->copy()->subDays(7), $todas, null, 0.20],
        ];
        $municipios = Municipio::orderBy('id')->get();
        $maturidade = $municipios->mapWithKeys(fn ($m) => [$m->id => mt_rand(10, 70) / 100]);

        foreach ($ciclos as [$titulo, $ciclo, $inicio, $perguntas, $participacao, $ganho]) {
            $fim = $inicio->copy()->addDays(14);
            $q = Questionario::create([
                'titulo' => $titulo, 'ciclo' => $ciclo, 'apresentacao' => $apresentacao,
                'descricao' => 'Questionário eletrônico de diagnóstico da estrutura de gestão tributária municipal.',
                'data_inicio' => $inicio, 'data_fim' => $fim, 'data_limite_assinatura' => $this->diaUtilApos($fim),
                'status' => $participacao === null ? 'publicado' : 'encerrado', 'abrangencia' => 'todos',
                'publicado_em' => $inicio->copy()->subDays(2)->setTime(9, 30), 'publicado_por' => $admin->id, 'criado_por' => $admin->id,
            ]);
            foreach ($perguntas->values() as $i => $p) {
                $q->perguntas()->attach($p->id, ['ordem' => $i + 1]);
            }
            $this->auditar('publicacao', $q, $admin, $q->publicado_em, ['status' => 'rascunho'], [
                'status' => 'publicado', 'data_inicio' => $q->data_inicio->toDateString(), 'data_fim' => $q->data_fim->toDateString(),
                'data_limite_assinatura' => $q->data_limite_assinatura->toDateString(), 'abrangencia' => 'todos', 'total_perguntas' => $perguntas->count(),
            ]);
            $q->load('perguntas.opcoes', 'perguntas.opcoesHabilitadoras', 'perguntas.tema');

            foreach ($municipios as $m) {
                $maturidade[$m->id] = min(0.98, $maturidade[$m->id] + $ganho * mt_rand(40, 140) / 100);
                $nomeDemo = in_array($m->nome, $demo, true) ? $m->nome : null;
                if ($participacao !== null) {
                    if (mt_rand(1, 100) <= $participacao * 100) {
                        $this->preencher($q, $m, $maturidade[$m->id], 'validado', $usuarios[$nomeDemo] ?? null);
                    }

                    continue;
                }
                // Ciclo aberto: cenários fixos para os municípios de demonstração
                $status = match ($m->nome) {
                    'Palmas' => 'em_preenchimento',
                    'Araguaína' => 'aguardando_validacao',
                    'Gurupi' => null,
                    'Porto Nacional' => 'validado',
                    'Paraíso do Tocantins' => 'devolvido',
                    default => [null, 'em_preenchimento', 'aguardando_validacao', 'validado', 'validado'][mt_rand(0, 4)],
                };
                if ($status) {
                    $this->preencher($q, $m, $maturidade[$m->id], $status, $usuarios[$nomeDemo] ?? null);
                }
            }

            if ($ciclo === '2025') {
                $this->auditar('prorrogacao', $q, $admin, $fim->copy()->subDays(3)->setTime(16, 10), [
                    'data_fim' => $fim->copy()->subDays(3)->toDateString(), 'data_limite_assinatura' => $this->diaUtilApos($fim->copy()->subDays(3))->toDateString(),
                ], ['data_fim' => $fim->toDateString(), 'data_limite_assinatura' => $q->data_limite_assinatura->toDateString()],
                    'Solicitação da Associação Tocantinense de Municípios em razão de instabilidade no acesso GOV.BR (exemplo fictício).');
            }
            if ($participacao === null) {
                $this->notificacoesDemo($q, $usuarios);
            }
        }

        // Rascunho para testes de edição, associação de perguntas e publicação
        $r = Questionario::create([
            'titulo' => 'Diagnóstico da Gestão Tributária Municipal — Ciclo 2027', 'ciclo' => '2027', 'apresentacao' => $apresentacao,
            'data_inicio' => $hoje->copy()->addMonths(2), 'data_fim' => $hoje->copy()->addMonths(2)->addDays(14),
            'data_limite_assinatura' => $this->diaUtilApos($hoje->copy()->addMonths(2)->addDays(14)),
            'status' => 'rascunho', 'abrangencia' => 'todos', 'criado_por' => $admin->id,
        ]);
        foreach ($todas->values() as $i => $p) {
            $r->perguntas()->attach($p->id, ['ordem' => $i + 1]);
        }

        // Materiais de apoio
        $disk = config('sdgt.materiais.disk');
        Storage::disk($disk)->put('materiais/cartilha-preenchimento.pdf', Pdf::loadView('pdf.cartilha-demo')->output());
        MaterialApoio::create(['titulo' => 'Cartilha de preenchimento do Diagnóstico', 'categoria' => 'cartilha', 'ativo' => true,
            'descricao' => 'Orientações passo a passo para responsáveis e gestores municipais.', 'criado_por' => $admin->id,
            'arquivo_caminho' => 'materiais/cartilha-preenchimento.pdf', 'arquivo_nome' => 'cartilha-preenchimento.pdf', 'arquivo_mime' => 'application/pdf', 'arquivo_tamanho' => 4096]);
        MaterialApoio::create(['titulo' => 'Emenda Constitucional nº 132/2023', 'categoria' => 'normativo', 'ativo' => true,
            'descricao' => 'Altera o Sistema Tributário Nacional (Reforma Tributária).', 'url_externa' => 'https://www.planalto.gov.br/ccivil_03/constituicao/emendas/emc/emc132.htm', 'criado_por' => $admin->id]);
        MaterialApoio::create(['titulo' => 'Lei Complementar nº 214/2025', 'categoria' => 'normativo', 'ativo' => true,
            'descricao' => 'Institui o IBS, a CBS e o Imposto Seletivo.', 'url_externa' => 'https://www.planalto.gov.br/ccivil_03/leis/lcp/lcp214.htm', 'criado_por' => $admin->id]);
    }

    private function preencher(Questionario $q, Municipio $m, float $mat, string $status, ?array $users): void
    {
        $resp = $users['responsavel'] ?? null;
        $gest = $users['gestor'] ?? null;
        $inicio = Carbon::parse($q->data_inicio)->addDays(mt_rand(0, 3))->setTime(mt_rand(8, 17), mt_rand(0, 59));
        $p = Preenchimento::create([
            'questionario_id' => $q->id, 'municipio_id' => $m->id, 'status' => 'em_preenchimento',
            'responsavel_id' => $resp?->id, 'iniciado_em' => $inicio,
        ]);
        $parcial = $status === 'em_preenchimento';
        $mapa = [];
        foreach ($q->perguntas as $idx => $pg) {
            if ($parcial && $idx > $q->perguntas->count() * 0.45) {
                break;
            }
            if (! $this->pontuacao->perguntaHabilitada($pg, $q->perguntas, $mapa)) {
                continue;
            }
            $r = $this->resposta($pg, $mat);
            if ($r === null) {
                continue;
            }
            $mapa[$pg->id] = $r;
            PreenchimentoResposta::create(['preenchimento_id' => $p->id, 'pergunta_id' => $pg->id] + $r);
            if (! $parcial && $this->exigeEvidencia($pg, $r)) {
                Evidencia::create(['preenchimento_id' => $p->id, 'pergunta_id' => $pg->id, 'nome_original' => 'comprovante-'.strtolower($pg->codigo).'.pdf',
                    'caminho' => $this->evidenciaModelo, 'mime' => 'application/pdf', 'tamanho' => 1520, 'hash_sha256' => hash('sha256', $pg->codigo.$m->id), 'enviado_por' => $resp?->id]);
            }
        }
        $finalizado = $inicio->copy()->addDays(mt_rand(2, 8));
        $dados = ['status' => $status];
        if (in_array($status, ['aguardando_validacao', 'validado'], true)) {
            $dados['finalizado_em'] = $finalizado;
        }
        if ($status === 'validado') {
            $dados += ['validado_em' => $finalizado->copy()->addHours(mt_rand(2, 30)), 'gestor_id' => $gest?->id, 'assinatura_metodo' => 'eletronica_simples',
                'assinatura_hash' => hash('sha256', 'demo'.$p->id), 'assinatura_detalhes' => 'Assinatura eletrônica simples (base de demonstração).'];
        }
        if ($status === 'devolvido') {
            $dados['observacao_devolucao'] = 'Anexar o ato de instituição do grupo de trabalho (GT01) e revisar a resposta da pergunta TI01.';
        }
        $p->forceFill($dados)->save();
        $this->pontuacao->recalcular($p);
    }

    private function resposta(Pergunta $pg, float $mat): ?array
    {
        return match ($pg->tipo) {
            'unica' => ['opcoes_ids' => [$this->escolher($pg, $mat)], 'valor_texto' => null],
            'multipla' => ['opcoes_ids' => $pg->opcoes->filter(fn ($o) => $o->pontuacao > 0 && mt_rand(1, 100) <= $mat * 100)->pluck('id')->values()->all()
                ?: [$pg->opcoes->sortBy('pontuacao')->first()->id], 'valor_texto' => null],
            'numero' => ['opcoes_ids' => null, 'valor_texto' => (string) ($pg->codigo === 'RH01' ? max(1, (int) round($mat * 25 + mt_rand(0, 6))) : (int) round($mat * 120 + mt_rand(0, 30)))],
            'data' => ['opcoes_ids' => null, 'valor_texto' => today()->subDays(mt_rand(60, 1400))->toDateString()],
            default => mt_rand(1, 100) <= 30 ? ['opcoes_ids' => null, 'valor_texto' => 'O município está estruturando a equipe e aguarda a regulamentação do Comitê Gestor do IBS.'] : null,
        };
    }

    private function escolher(Pergunta $pg, float $mat): int
    {
        $ops = $pg->opcoes->sortBy('pontuacao')->values();
        $alvo = $mat * ($ops->count() - 1) + (mt_rand(-100, 100) / 100);
        $i = (int) max(0, min($ops->count() - 1, round($alvo)));

        return $ops[$i]->id;
    }

    private function exigeEvidencia(Pergunta $pg, array $r): bool
    {
        return $pg->exige_evidencia || $pg->opcoes->whereIn('id', (array) $r['opcoes_ids'])->contains(fn ($o) => $o->solicita_evidencia);
    }

    private function notificacoesDemo(Questionario $q, array $usuarios): void
    {
        foreach ($usuarios as $nome => $par) {
            foreach ($par as $u) {
                Notificacao::create(['user_id' => $u->id, 'tipo' => 'inicio_preenchimento', 'questionario_id' => $q->id,
                    'titulo' => 'Início do preenchimento: '.$q->titulo, 'link' => '/municipio/questionarios',
                    'mensagem' => "Está aberto o período de preenchimento do questionário \"{$q->titulo}\", de {$q->data_inicio->format('d/m/Y')} a {$q->data_fim->format('d/m/Y')}. A validação/assinatura pelo gestor deve ocorrer até {$q->data_limite_assinatura->format('d/m/Y')}.",
                    'created_at' => $q->data_inicio->copy()->setTime(6, 0), 'email_enviado_em' => $q->data_inicio->copy()->setTime(6, 0)]);
            }
        }
        Notificacao::create(['user_id' => $usuarios['Araguaína']['gestor']->id, 'tipo' => 'aguardando_validacao', 'questionario_id' => $q->id,
            'titulo' => 'Questionário aguardando sua validação', 'link' => '/municipio/questionarios',
            'mensagem' => "O responsável municipal finalizou o preenchimento do questionário \"{$q->titulo}\". Revise as informações e realize a validação/assinatura até {$q->data_limite_assinatura->format('d/m/Y')}."]);
        Notificacao::create(['user_id' => $usuarios['Paraíso do Tocantins']['responsavel']->id, 'tipo' => 'devolucao', 'questionario_id' => $q->id,
            'titulo' => 'Questionário devolvido para ajustes', 'link' => '/municipio/questionarios',
            'mensagem' => 'O gestor devolveu o questionário para ajustes. Motivo: Anexar o ato de instituição do grupo de trabalho (GT01) e revisar a resposta da pergunta TI01.']);
    }

    private function auditar(string $acao, Questionario $q, User $u, $quando, array $antes, array $depois, ?string $just = null): void
    {
        $a = Auditoria::create(['acao' => $acao, 'entidade_tipo' => 'Questionario', 'entidade_id' => $q->id, 'entidade_descricao' => $q->titulo,
            'user_id' => $u->id, 'user_nome' => $u->name, 'justificativa' => $just, 'dados_anteriores' => $antes, 'dados_novos' => $depois,
            'ip' => '10.0.0.15', 'user_agent' => 'seed']);
        $a->forceFill(['created_at' => $quando])->save();
    }

    private function diaUtilApos(Carbon $d): Carbon
    {
        return app(CalendarioOficial::class)->somarDiasUteis($d, 1);
    }

    /** Gera CPF com dígitos verificadores válidos a partir de 9 dígitos base. */
    public static function cpf(int $base): string
    {
        $n = str_pad((string) $base, 9, '0', STR_PAD_LEFT);
        for ($t = 9; $t < 11; $t++) {
            $s = 0;
            for ($i = 0; $i < $t; $i++) {
                $s += (int) $n[$i] * (($t + 1) - $i);
            }
            $n .= (string) ((10 * $s) % 11 % 10);
        }

        return $n;
    }
}
