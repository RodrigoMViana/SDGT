# 05 — Relatório de Testes

**Data da execução:** 10/11/2026 · **Versão:** 1.0.0 · **Ferramenta:** PHPUnit 12.5 (Laravel 13.42, PHP 8.3.36)

## 1. Resumo

| Ambiente | Testes | Asserções | Resultado | Duração |
|---|---|---|---|---|
| Local — SQLite (Windows 11, PHP 8.3) | **76** | **480** | ✅ 76 aprovados, 0 falhas | 1 min 37 s |
| CI — SQLite (Ubuntu 24.04) | 76 | 480 | ver aba *Actions* (`CI › Testes (PHP 8.3 + SQLite)`) | — |
| CI — **SQL Server 2022** (migrações + seed completo + suíte) | 76 | 480 | ver aba *Actions* (`CI › Testes (PHP 8.3 + SQL Server 2022)`) | — |
| CI — Build da imagem Docker | — | — | ver aba *Actions* (`CI › Build da imagem Docker`) | — |
| Protótipo estático (navegador, roteiro automatizado via DOM) | 28 telas + fluxo E2E | — | ✅ sem erros de script | — |

> O resultado dos jobs de CI fica registrado em cada execução em `https://github.com/RodrigoMViana/SDGT/actions` (artefatos `junit-sqlite` e `junit-sqlserver`). A seção 6 traz o status da primeira execução.

## 2. Estratégia

| Nível | O que cobre | Onde |
|---|---|---|
| Unitário (regras) | Índice de aderência, dependência entre perguntas, múltipla escolha, faixas, calendário de dias úteis | `tests/Unit/RegrasNegocioTest.php` |
| Funcional (HTTP + banco) | Cada HU via requisições reais às rotas, validando resposta, banco, arquivos, e-mails enfileirados e notificações | `tests/Feature/*` |
| Smoke de telas | Todas as telas de cada perfil renderizam (HTTP 200) com a base de demonstração | `tests/Feature/SmokePaginasTest.php` |
| Integrações | Contratos simulados com `Http::fake` (GOV.BR + CADUN), certificado e-CPF de teste (`tests/fixtures/ecpf-teste.pem`) | `AutenticacaoTest` |
| Banco real | Mesma suíte em SQL Server 2022 (CI) — valida DDL, FKs sem cascata múltipla, JSON, datas | `.github/workflows/ci.yml` |
| Protótipo | Roteiro em JavaScript no navegador: renderização das 28 rotas e fluxo responsável → gestor | `docs/07` (manual) |

Dublês usados: `Mail::fake` (e-mails), `Storage::fake` (evidências/materiais), `Http::fake` (APIs externas).

## 3. Rastreabilidade HU → teste (todos ✅)

| Épico | HU | Caso(s) de teste |
|---|---|---|
| EP01 | HU01.01 Usuário e senha | `Hu0101 admin autentica com usuario e senha`, `Hu0101 auditor autentica pelo cpf`, `Senha invalida e rejeitada`, `Usuario municipal nao usa senha`, `Usuario inativo nao entra` |
| | HU01.02 GOV.BR | `Hu0102 govbr oidc redireciona com pkce`, `Hu0102 govbr callback autentica e provisiona pelo cadun`, `Hu0102 govbr callback com state invalido` |
| | HU01.03 Certificado | `Hu0103 certificado digital via cabecalho do proxy`, `Hu0103 certificado ausente e rejeitado` |
| | Controle de acesso | `Controle de acesso por perfil`, `Login simulado desabilitado por padrao`, `Login simulado habilitado`, `Isolamento entre municipios` |
| EP02 | HU02.01–05 | `Hu0201 listar temas`, `Hu0202 e 0203 cadastrar e atualizar tema`, `Hu0204 ativar inativar tema`, `Hu0205 excluir tema somente sem perguntas` |
| EP03 | HU03.01/03 | `Hu0301 e 0303 listar e visualizar` |
| | HU03.02 | `Hu0302 cadastrar calcula prazo de assinatura em dia util`, `Hu0302 validacao de datas e abrangencia` |
| | HU03.04 | `Hu0304 baixar questionario em pdf` |
| | HU03.05 | `Hu0305 atualizar somente em rascunho` |
| | HU03.06 | `Hu0306 publicar valida audita e notifica municipios`, `Hu0306 publicacao futura nao notifica antes do inicio` |
| | HU03.07 | `Hu0307 excluir somente rascunho` |
| | HU03.08 | `Hu0308 duplicar questionario`, `Questionario personalizado so aparece para municipios selecionados` |
| | HU03.09 | `Hu0309 prorrogar prazo com auditoria e notificacao`, `Hu0309 prorrogacao reabre questionario encerrado` |
| | HU03.10 | `Hu0310 associar perguntas exige pergunta condicionante` |
| EP04 | HU04.01–04 | `Hu0401 listar perguntas com filtros`, `Hu0402 a 0404 crud de pergunta`, `Hu0404 nao exclui pergunta associada a questionario`, `Pergunta em questionario publicado fica bloqueada` |
| | HU04.05 | `Hu0405 configurar e remover dependencia`, `Hu0405 impede dependencia circular`, Unit: `Pergunta condicional so habilita com opcao da pergunta pai` |
| | HU04.06–09 | `Hu0406 a 0409 crud de respostas` |
| EP05 | HU05.01 | `Hu0501 listar questionarios do municipio`, `Apresentacao inicial exibe normativos e perguntas` |
| | HU05.02/03 | `Hu0502 e 0503 salvar parcialmente e retomar`, `Hu0502 numero invalido e rejeitado`, `Dependencia descarta resposta de pergunta desabilitada`, `Opcao de outra pergunta e rejeitada` |
| | HU05.04 | `Hu0504 finalizacao bloqueada por pendencias` |
| | HU05.05–07 | `Fluxo completo finalizar validar com assinatura`, `Gestor devolve para ajustes` |
| | Prazos | `Encerramento do prazo bloqueia alteracoes`, `Nao inicia fora do periodo` |
| EP06 | HU06.01 | `Hu0601 evidencia exigida pela resposta`, `Hu0601 tipo de arquivo nao permitido` |
| | HU06.02/03 | `Hu0602 e 0603 excluir e baixar evidencia` |
| EP07 | HU07.01–06 | `Hu0701 panorama consolidado`, `Hu0702 comparar municipios`, `Hu0703 desempenho por relatoria`, `Hu0704 evolucao da adequacao`, `Hu0705 e 0706 visualizar e baixar preenchido`, `Auditor acessa painel mas nao administracao` |
| EP08 | HU08.01–03 | `Hu0801 consultar resultados com filtros`, `Hu0802 e 0803 exportar pdf e xls` |
| EP09 | HU09.01–03 | `Hu0901 a 0903 indicador listagem e visualizacao` |
| EP10 | HU10.01/02 | `Hu1001 e 1002 rotina de marcos notifica inicio lembrete e encerramento`, `Lembrete nao e enviado a municipio que ja finalizou`, e-mails em `Hu0306 publicar…` |
| EP11 | HU11.01–05 | `Hu1101 a 1105 materiais de apoio` |
| EP12 | HU12.01 | asserções de auditoria em `Hu0306…`, `Hu0309…`, `Fluxo completo…`, `Hu1001…` (encerramento) |
| | HU12.02–06 | `Hu1202 listar auditoria com filtros`, `Hu1203 e 1204 exportar listagem pdf xls`, `Hu1205 e 1206 visualizar e exportar registro` |
| Regras | Índice | Unit: `Pergunta desabilitada nao entra na pontuacao maxima`, `Multipla escolha soma opcoes e respeita o maximo`, `Indice completo e por tema`, `Faixas de aderencia configuraveis`, `Calendario soma dias uteis pulando fim de semana e feriado` |
| Apoio | Usuários / relatorias | `Cadastro provisorio de usuarios`, `Vinculo de municipio a relatoria` |
| Telas | Todas | `Todas as paginas renderizam por perfil` (34 URLs + telas municipais de 5 municípios × 2 perfis) |

## 4. Saída da execução local (testdox, resumida)

```
Autenticacao ............................ 13/13 ✔
Gestao Questionarios .................... 13/13 ✔
Gestao Temas Perguntas .................. 11/11 ✔
Notificacoes Materiais Usuarios .........  6/6  ✔
Painel Relatorios Auditoria ............. 11/11 ✔
Preenchimento Validacao ................. 15/15 ✔
Regras Negocio (Unit) ...................  6/6  ✔
Smoke Paginas ...........................  1/1  ✔
OK (76 tests, 480 assertions)
```

## 5. Protótipo navegável (GitHub Pages)

Roteiro executado no navegador (Chromium) com a base exportada:
- 28 rotas do administrador renderizadas sem erro de script (painéis, questionários, perguntas, temas, auditoria, usuários, municípios, materiais, notificações, rotina, preenchimento).
- Fluxo E2E: responsável de **Gurupi** inicia → pergunta condicional LT02 desabilitada → LT01 = "Não" habilita LT02 → responde tudo → revisão sem pendências → finaliza (índice 58,84%) → gestor recebe notificação → valida com hash SHA-256 → registro de auditoria criado.

## 6. Defeitos encontrados e corrigidos durante a construção

| # | Defeito | Correção |
|---|---|---|
| 1 | Views de painel com `@json(...fn...)` geravam erro de compilação Blade | Dados pré-montados em `@php` e serializados com `json_encode` |
| 2 | Seed de demonstração lento (3 min) | Execução em transação única (≈ 25 s) |
| 3 | Geração de certificado em teste dependia do `openssl.cnf` do SO | Certificado e-CPF de teste versionado em `tests/fixtures` |
| 4 | Asserção de progresso considerava HTML | `assertSeeText` |

## 7. Limitações e itens não cobertos

- **Integrações reais** (GOV.BR, ICP-Brasil, CADUN, Calendário, SFG, Arquivos, SMTP) — testadas apenas contra contratos presumidos/simulados; exigem homologação com o TCE (ver [02](02-integracoes-pendentes.md)).
- **Docker Compose completo** não foi executado na máquina de desenvolvimento (sem Docker instalado); a imagem é construída e verificada no CI, e as migrações/seed/testes em SQL Server 2022 também rodam no CI.
- Testes de **acessibilidade (e-MAG/WCAG)**, **carga** e **compatibilidade de navegadores** não foram realizados — recomendados na homologação.
- **Validação visual** (capturas de tela) não automatizada.
