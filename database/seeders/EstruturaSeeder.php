<?php

namespace Database\Seeders;

use App\Enums\Perfil;
use App\Models\Municipio;
use App\Models\Relatoria;
use App\Models\Tema;
use App\Models\User;
use Illuminate\Database\Seeder;

class EstruturaSeeder extends Seeder
{
    public function run(): void
    {
        // Relatorias. DECISÃO PENDENTE: distribuição oficial dos municípios por relatoria (importar do TCE/TO).
        $qtd = (int) env('SDGT_QTD_RELATORIAS', 6);
        $relatorias = collect(range(1, $qtd))->map(fn ($i) => Relatoria::firstOrCreate(['nome' => "{$i}ª Relatoria"]));

        // Municípios do Tocantins (fonte: API de Localidades do IBGE) — arquivo database/data/municipios_to.json
        $arquivo = database_path('data/municipios_'.strtolower(config('sdgt.orgao.uf')).'.json');
        $lista = json_decode(file_get_contents($arquivo), true);
        foreach ($lista as $i => $m) {
            Municipio::updateOrCreate(['codigo_ibge' => $m['codigo_ibge']], [
                'nome' => $m['nome'],
                'regiao_imediata' => $m['regiao_imediata'],
                'microrregiao' => $m['microrregiao'],
                'relatoria_id' => Municipio::where('codigo_ibge', $m['codigo_ibge'])->value('relatoria_id')
                    ?? $relatorias[$i % $relatorias->count()]->id,
            ]);
        }

        // Temas (dimensões) previstos no Documento de Visão, item 4
        $temas = [
            ['Legislação Tributária', 'Adequação do arcabouço normativo municipal (Lei Orgânica, Código Tributário, IPTU e benefícios fiscais) à EC nº 132/2023 e à regulamentação da Reforma Tributária.'],
            ['Recursos Humanos', 'Estrutura de pessoal da administração tributária: carreira, quantitativo e capacitação.'],
            ['Infraestrutura Física e Tecnologia da Informação', 'Sistemas de gestão tributária, NFS-e, integração com ambientes nacionais e infraestrutura física.'],
            ['Procedimentos de Fiscalização', 'Planejamento, método e normatização das ações fiscais.'],
            ['Cobrança de Créditos Tributários e Contabilização', 'Dívida ativa, instrumentos de cobrança e registro contábil da receita tributária.'],
            ['Governança da Transição para a Reforma Tributária', 'Estruturas, estudos e acompanhamento da transição ISS → IBS.'],
        ];
        foreach ($temas as $i => [$nome, $desc]) {
            Tema::firstOrCreate(['nome' => $nome], ['descricao' => $desc, 'ordem' => $i + 1, 'ativo' => true]);
        }

        // Administrador inicial (troque a senha no primeiro acesso em produção)
        User::firstOrCreate(['email' => env('SDGT_ADMIN_EMAIL', 'admin@tceto.tc.br')], [
            'name' => 'Administrador do SDGT',
            'perfil' => Perfil::Administrador,
            'password' => env('SDGT_ADMIN_PASSWORD', 'Sdgt@2026'),
            'ativo' => true,
        ]);
    }
}
