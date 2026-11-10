<?php

/*
|--------------------------------------------------------------------------
| SDGT - Sistema de Diagnóstico da Gestão Tributária Municipal
|--------------------------------------------------------------------------
| Parâmetros de negócio e seleção de drivers de integração. Todos os
| valores podem ser sobrescritos por variáveis de ambiente (.env), o que
| permite replicar o produto para outros Tribunais sem alterar código.
*/

return [

    'orgao' => [
        'nome' => env('SDGT_ORGAO_NOME', 'Tribunal de Contas do Estado do Tocantins'),
        'sigla' => env('SDGT_ORGAO_SIGLA', 'TCE/TO'),
        'uf' => env('SDGT_ORGAO_UF', 'TO'),
        'sistema' => env('SDGT_NOME_SISTEMA', 'Diagnóstico da Gestão Tributária Municipal'),
    ],

    // Faixas de aderência (índice 0-100). DECISÃO PENDENTE DO CLIENTE: limites definitivos.
    'faixas' => [
        'baixa_ate' => (float) env('SDGT_FAIXA_BAIXA_ATE', 49.99),
        'media_ate' => (float) env('SDGT_FAIXA_MEDIA_ATE', 74.99),
    ],

    // Prazos padrão (Memória de Reunião 03/11/2026: ~15 dias corridos + 1 dia útil para assinatura)
    'prazos' => [
        'dias_preenchimento' => (int) env('SDGT_DIAS_PREENCHIMENTO', 15),
        'dias_uteis_assinatura' => (int) env('SDGT_DIAS_UTEIS_ASSINATURA', 1),
        'dias_lembrete_encerramento' => (int) env('SDGT_DIAS_LEMBRETE', 3),
    ],

    'evidencias' => [
        'disk' => env('SDGT_EVIDENCIAS_DISK', 'local'),
        'tamanho_max_kb' => (int) env('SDGT_EVIDENCIA_MAX_KB', 20480),
        'extensoes' => ['pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'ods', 'csv', 'png', 'jpg', 'jpeg'],
    ],

    'materiais' => [
        'disk' => env('SDGT_MATERIAIS_DISK', 'local'),
    ],

    /*
    | Drivers de integração. "fake" = simulado (desenvolvimento/homologação).
    | Os drivers reais dependem de APIs/credenciais do Tribunal (ver docs/02-integracoes-pendentes.md).
    */
    'integracoes' => [
        'govbr' => [
            'driver' => env('GOVBR_DRIVER', 'fake'), // fake | oidc
            'client_id' => env('GOVBR_CLIENT_ID'),
            'client_secret' => env('GOVBR_CLIENT_SECRET'),
            'redirect_uri' => env('GOVBR_REDIRECT_URI'),
            'url_provider' => env('GOVBR_URL_PROVIDER', 'https://sso.staging.acesso.gov.br'),
            'scopes' => env('GOVBR_SCOPES', 'openid email profile govbr_confiabilidades'),
        ],
        'certificado' => [
            'driver' => env('CERTIFICADO_DRIVER', 'fake'), // fake | header
            'header' => env('CERTIFICADO_HEADER', 'X-SSL-CLIENT-CERT'),
        ],
        'assinatura' => [
            'driver' => env('ASSINATURA_DRIVER', 'simples'), // simples | icp_tce
            'url' => env('ASSINATURA_ICP_URL'),
            'token' => env('ASSINATURA_ICP_TOKEN'),
        ],
        'cadun' => [
            'driver' => env('CADUN_DRIVER', 'local'), // local | api
            'url' => env('CADUN_API_URL'),
            'token' => env('CADUN_API_TOKEN'),
        ],
        'calendario' => [
            'driver' => env('CALENDARIO_DRIVER', 'local'), // local | api
            'url' => env('CALENDARIO_API_URL'),
            'token' => env('CALENDARIO_API_TOKEN'),
            // feriados fixos usados pelo driver local (MM-DD) + datas específicas (YYYY-MM-DD)
            'feriados' => array_filter(explode(',', env('CALENDARIO_FERIADOS',
                '01-01,04-21,05-01,09-07,10-05,10-12,11-02,11-15,11-20,12-25'))),
        ],
        'sfg' => [
            'driver' => env('SFG_DRIVER', 'log'), // log | api
            'url' => env('SFG_API_URL'),
            'token' => env('SFG_API_TOKEN'),
        ],
    ],

    // Habilita o seletor de usuários simulados na tela de login (NUNCA em produção)
    'login_simulado' => (bool) env('SDGT_LOGIN_SIMULADO', false),
];
