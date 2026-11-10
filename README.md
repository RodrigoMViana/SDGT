# SDGT — Sistema de Diagnóstico da Gestão Tributária Municipal

[![CI](https://github.com/RodrigoMViana/SDGT/actions/workflows/ci.yml/badge.svg)](https://github.com/RodrigoMViana/SDGT/actions/workflows/ci.yml)
[![Pages](https://github.com/RodrigoMViana/SDGT/actions/workflows/pages.yml/badge.svg)](https://github.com/RodrigoMViana/SDGT/actions/workflows/pages.yml)

Plataforma do **Tribunal de Contas do Estado do Tocantins (TCE/TO)** para acompanhar a adequação dos 139 municípios jurisdicionados à **Reforma Tributária** (EC nº 132/2023 e LC nº 214/2025): questionários eletrônicos por dimensão temática, evidências, validação/assinatura pelo gestor, índice de aderência, painéis, relatórios, notificações e auditoria.

**Demonstração navegável:** <https://rodrigomviana.github.io/SDGT/> · **Documentação:** <https://rodrigomviana.github.io/SDGT/docs/> (ou a pasta [`docs/`](docs/README.md))

## Tecnologia
Laravel 13 (PHP 8.3) · Blade · Microsoft SQL Server 2022 · Eloquent · Docker — conforme as diretrizes do cliente. PDF com DomPDF, XLSX com PhpSpreadsheet, gráficos com Chart.js. Identidade visual do Guia de Estilo TCE/TO.

## Escopo
Os 12 épicos / 65 histórias do Documento de Visão 1.0:

| Épico | | Épico | |
|---|---|---|---|
| EP01 Autenticação (senha, GOV.BR, certificado) | ✅ | EP07 Painel corporativo | ✅ |
| EP02 Temas de avaliação | ✅ | EP08 Relatórios (PDF/XLS) | ✅ |
| EP03 Questionários (publicar, prorrogar, duplicar…) | ✅ | EP09 Notificações | ✅ |
| EP04 Perguntas, respostas e dependências | ✅ | EP10 E-mails | ✅ |
| EP05 Preenchimento e validação | ✅ | EP11 Materiais de apoio | ✅ |
| EP06 Evidências | ✅ | EP12 Auditoria | ✅ |

Integrações com sistemas do Tribunal (GOV.BR, ICP-Brasil, CADUN, Calendário Oficial, SFG, Arquivos) estão prontas atrás de interfaces e operam em **modo simulado** até a disponibilização das APIs — ver [docs/02](docs/02-integracoes-pendentes.md).

## Início rápido (Docker)
```bash
cp .env.docker.example .env.docker
docker compose run --rm --no-deps app php artisan key:generate --show   # cole em APP_KEY
docker compose up -d --build
```
Acesse <http://localhost:8080> (e-mails de teste em <http://localhost:8025>). Usuário inicial: `admin@tceto.tc.br` / `Sdgt@2026`. Com `SDGT_SEED_DEMO=true` a base vem com 3 ciclos fictícios e a caixa **Acesso simulado** no login.

## Desenvolvimento local
```bash
composer install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
php artisan serve
php artisan test
```

## Documentação
1. [Documento de Arquitetura](docs/01-documento-de-arquitetura.md)
2. [Integrações Pendentes](docs/02-integracoes-pendentes.md)
3. [Manual do Usuário](docs/03-manual-do-usuario.md)
4. [Manual Técnico](docs/04-manual-tecnico.md)
5. [Relatório de Testes](docs/05-relatorio-de-testes.md)
6. [O que falta fazer](docs/06-o-que-falta-fazer.md)
7. [Fluxo de validação](docs/07-fluxo-de-validacao.md)
