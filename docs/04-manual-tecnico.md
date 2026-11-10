# 04 — Manual Técnico

## 1. Requisitos

| Uso | Requisitos |
|---|---|
| Execução em contêineres (recomendado) | Docker 24+ e Docker Compose v2; host **linux/amd64** (o driver ODBC da Microsoft e a imagem do SQL Server são amd64) |
| Desenvolvimento local sem Docker | PHP 8.3 com `pdo_sqlite`, `intl`, `gd`, `zip`, `mbstring`, `fileinfo`, `openssl` (+ `sqlsrv`/`pdo_sqlsrv` para SQL Server); Composer 2 |
| Pré-visualizar o site do Pages | Node 20+ |

## 2. Subir com Docker (homologação completa)

```bash
git clone https://github.com/RodrigoMViana/SDGT.git && cd SDGT
cp .env.docker.example .env.docker
docker compose run --rm --no-deps app php artisan key:generate --show   # copie para APP_KEY em .env.docker
docker compose up -d --build
```

- Aplicação: <http://localhost:8080> · E-mails capturados (Mailpit): <http://localhost:8025> · SQL Server: `localhost,14330` (usuário `sa`).
- Na primeira subida o `app` cria o banco `sdgt`, executa as migrações e o seed (com `SDGT_SEED_DEMO=true`, a base fictícia de 3 ciclos).
- Logs: `docker compose logs -f app worker scheduler`.
- Portas configuráveis por variáveis de ambiente do shell: `SDGT_PORTA`, `SQLSERVER_PORTA`, `MAILPIT_PORTA`. A senha do SA vem de `DB_PASSWORD` (padrão `Sdgt#Sql2026`) e deve coincidir com a de `.env.docker`.

### Produção
1. Use o SQL Server corporativo: remova o serviço `sqlserver` (ou use um `docker-compose.override.yml`) e aponte `DB_HOST`/`DB_USERNAME`/`DB_PASSWORD` para um login com permissão de DDL no banco `sdgt` (ou crie o banco previamente com collation UTF-8).
2. Em `.env.docker`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `SDGT_SEED_DEMO=false`, `SDGT_LOGIN_SIMULADO=false`, `SDGT_ADMIN_PASSWORD` forte, `MAIL_*` do Tribunal, drivers de integração reais (ver [02](02-integracoes-pendentes.md)), `SESSION_SECURE_COOKIE=true`.
3. Publique atrás do proxy reverso com TLS (e mTLS para o login por certificado). Ajuste `TRUSTED_PROXIES`.
4. Remova o `mailpit`. Faça backup do volume `sdgt-storage` (evidências) e do banco.
5. Troque a senha do administrador inicial no primeiro acesso (tela *Usuários*).

## 3. Desenvolvimento local (sem Docker)

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite            # Windows: type nul > database\database.sqlite
# opcional: base de demonstração e login simulado
#   SDGT_SEED_DEMO=true e SDGT_LOGIN_SIMULADO=true no .env
php artisan migrate --seed
php artisan serve                          # http://localhost:8000
php artisan queue:work                     # outro terminal (e-mails)
php artisan schedule:work                  # outro terminal (rotina diária)
```
Usuário inicial: `admin@tceto.tc.br` / `Sdgt@2026` (variáveis `SDGT_ADMIN_EMAIL`/`SDGT_ADMIN_PASSWORD`). Base demo: também `auditor@tceto.tc.br` / `Sdgt@2026` e responsáveis/gestores fictícios de Palmas, Araguaína, Gurupi, Porto Nacional e Paraíso do Tocantins (acesso pela caixa **Acesso simulado**).

## 4. Estrutura do projeto

```
app/
  Console/Commands/ExportarDemo.php    sdgt:exportar-demo (JSON para o Pages)
  Enums/Perfil.php
  Http/Controllers/{Admin,Tribunal,Municipio,Auth}/
  Http/Middleware/VerificarPerfil.php  alias "perfil"
  Integracoes/{Contracts,Drivers}/, GovBrOidc.php, CertificadoDigital.php
  Mail/NotificacaoMail.php
  Models/                              12 models
  Services/                            regras de negócio
  Support/Exportador.php               PDF (DomPDF) e XLSX (PhpSpreadsheet)
config/sdgt.php                        parâmetros do produto e drivers
database/
  data/municipios_to.json              139 municípios (API IBGE)
  migrations/                          esquema compatível SQL Server/SQLite
  seeders/{Estrutura,BancoPerguntas,Demo}Seeder.php
demo/                                  protótipo estático (GitHub Pages)
docker/  Dockerfile  docker-compose.yml
docs/                                  esta documentação
lang/pt_BR/                            mensagens em português
public/css/sdgt.css  public/js/sdgt.js  public/vendor/chart.umd.min.js
resources/views/                       Blade (layouts, partials, telas, pdf, emails, errors)
routes/web.php  routes/console.php     rotas e agendamento
scripts/montar-pages.mjs  scripts/servidor-estatico.mjs
tests/                                 PHPUnit (Feature + Unit)
```

## 5. Configuração (`config/sdgt.php` / `.env`)

| Variável | Padrão | Descrição |
|---|---|---|
| `SDGT_ORGAO_NOME`, `SDGT_ORGAO_SIGLA`, `SDGT_ORGAO_UF`, `SDGT_NOME_SISTEMA` | TCE/TO | Identidade do órgão (replicação) |
| `SDGT_QTD_RELATORIAS` | 6 | Relatorias criadas no seed |
| `SDGT_FAIXA_BAIXA_ATE`, `SDGT_FAIXA_MEDIA_ATE` | 49.99 / 74.99 | Faixas de aderência |
| `SDGT_DIAS_PREENCHIMENTO`, `SDGT_DIAS_UTEIS_ASSINATURA`, `SDGT_DIAS_LEMBRETE` | 15 / 1 / 3 | Prazos padrão e lembrete |
| `SDGT_EVIDENCIA_MAX_KB` | 20480 | Tamanho máximo de evidência |
| `SDGT_HORARIO_ROTINA` | 06:00 | Horário da rotina diária |
| `SDGT_SEED_DEMO` | false | Carrega base fictícia |
| `SDGT_LOGIN_SIMULADO` | false | Seletor de usuários no login (ignorado em produção) |
| `SDGT_AUTO_MIGRATE` | true | Migra no start do contêiner `app` |
| Drivers de integração | simulados | ver [02](02-integracoes-pendentes.md) |

## 6. Comandos Artisan do produto

| Comando | Uso |
|---|---|
| `php artisan sdgt:processar-marcos` | Rotina diária (agendada): notifica início, lembrete e encerramento; encerra questionários vencidos. Idempotente. |
| `php artisan sdgt:exportar-demo demo/data/seed.json` | Exporta a base atual para o protótipo do Pages |
| `php artisan db:seed --class=DemoSeeder` | Carrega somente a base de demonstração |

## 7. Testes

```bash
php artisan test                         # SQLite em memória (≈ 1,5 min)
DB_CONNECTION=sqlsrv DB_HOST=127.0.0.1 DB_DATABASE=sdgt_test DB_USERNAME=sa DB_PASSWORD=... DB_TRUST_SERVER_CERTIFICATE=true php artisan test
```
Os testes que usam a base de demonstração (`tests/UsaBaseDemonstracao.php`) semeiam uma vez por classe e isolam cada teste em transação. Detalhes em [05](05-relatorio-de-testes.md).

## 8. CI/CD (GitHub Actions)

| Workflow | Gatilho | O que faz |
|---|---|---|
| `ci.yml` | push/PR | (1) testes em SQLite + Pint; (2) **SQL Server 2022** como *service*: cria bancos, `migrate:fresh --seed` com base demo e suíte completa; (3) `docker build` + verificação das extensões |
| `pages.yml` | push em `main` | Gera a base demo com a própria aplicação, exporta o JSON, monta `demo/` + `docs/` (Markdown → HTML) e publica no GitHub Pages |

Para o Pages funcionar: **Settings → Pages → Build and deployment → Source: GitHub Actions**.

## 9. Protótipo estático (GitHub Pages)

O Pages só hospeda arquivos estáticos (não executa PHP nem SQL Server). Por isso `demo/` é uma SPA em JavaScript puro que:
- usa o **mesmo CSS** (`public/css/sdgt.css`) e a mesma estrutura de telas do Blade;
- carrega a **base exportada pela aplicação** (`sdgt:exportar-demo`) e desloca as datas para "hoje";
- reimplementa as regras dos *Services* (`demo/js/dados.js`) e persiste no `localStorage`.

Pré-visualizar localmente:
```bash
php artisan sdgt:exportar-demo demo/data/seed.json
npm install --no-save marked@12.0.2 && node scripts/montar-pages.mjs _site
node scripts/servidor-estatico.mjs _site 8090      # http://localhost:8090
```
> Ao alterar uma regra de negócio em `app/Services`, replique em `demo/js/dados.js` (o protótipo é material de validação, não substitui a aplicação).

## 10. Replicar para outro Tribunal
1. Gere o arquivo de municípios: `https://servicodados.ibge.gov.br/api/v1/localidades/estados/<UF>/municipios` → converter para o formato de `database/data/municipios_to.json` (campos `codigo_ibge`, `nome`, `microrregiao`, `regiao_imediata`) e salvar como `municipios_<uf>.json`.
2. Ajuste `SDGT_ORGAO_*`, `SDGT_QTD_RELATORIAS`, faixas e prazos.
3. Revise o banco de perguntas sugerido (`BancoPerguntasSeeder`) ou cadastre pelo sistema.
4. `docker compose up -d --build`.

## 11. Operação e manutenção
- **Backup:** banco SQL Server (rotina corporativa) + volume `sdgt-storage`.
- **Logs:** `LOG_CHANNEL=stderr` nos contêineres (coletados pelo Docker); erros de integração são registrados com contexto.
- **Fila com falha:** `php artisan queue:failed` / `queue:retry all`.
- **Atualização de versão:** `git pull && docker compose up -d --build` (migrações rodam automaticamente).
- **Limpar caches:** `php artisan optimize:clear`.

## 12. Solução de problemas
| Sintoma | Causa provável / ação |
|---|---|
| `app` reinicia com "SQL Server indisponível" | Senha do SA diferente entre compose e `.env.docker`; host sem amd64; aguardar o healthcheck |
| "Login failed for user" | Usuário sem permissão para criar banco: crie `sdgt` manualmente |
| SSL/ODBC: certificate verify failed | Defina `DB_TRUST_SERVER_CERTIFICATE=true` ou instale a CA do SQL Server |
| E-mails não chegam | `worker` parado ou `MAIL_*` incorretos; ver Mailpit em homologação |
| Notificações de início não saem para questionário agendado | São enviadas pelo `scheduler` no dia do início — verifique se está ativo |
