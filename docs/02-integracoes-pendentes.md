# 02 — Integrações Pendentes

A Memória de Reunião de 06/11/2026 registra que **o TCE/TO (DINFO/CODES) ainda analisará a disponibilidade das APIs** e definirá o responsável por elas; o Documento de Visão (item 7) coloca fora do escopo o desenvolvimento/manutenção dessas APIs. Por isso, **todas as integrações foram construídas atrás de interfaces**, com:

- um **driver local/simulado** em uso (o sistema funciona ponta a ponta hoje); e
- um **driver real já codificado** sobre um **contrato presumido**, a ser confirmado com o Tribunal.

A troca é feita apenas por variável de ambiente — nenhum controller ou regra de negócio precisa mudar.

## Resumo

| # | Integração | Uso no sistema | Driver atual | Driver real | Variáveis | Situação |
|---|---|---|---|---|---|---|
| 1 | **GOV.BR (Login Único)** | HU01.02 — login de responsável/gestor | `fake` (seletor de usuários) | `oidc` — `App\Integracoes\GovBrOidc` (Authorization Code + PKCE, `userinfo`) | `GOVBR_DRIVER`, `GOVBR_CLIENT_ID`, `GOVBR_CLIENT_SECRET`, `GOVBR_REDIRECT_URI`, `GOVBR_URL_PROVIDER` | **Pendente**: credenciamento do serviço no gov.br (staging e produção) |
| 2 | **Certificado Digital ICP-Brasil** | HU01.03 — login com e-CPF | `fake` | `header` — `App\Integracoes\CertificadoDigital` (lê o PEM repassado pelo proxy) | `CERTIFICADO_DRIVER`, `CERTIFICADO_HEADER` | **Pendente**: configurar mTLS no proxy reverso do TCE |
| 3 | **Assinatura digital ICP-Brasil (serviço do TCE)** | HU05.07 — assinatura do gestor | `simples` (assinatura eletrônica simples + hash SHA-256) | `icp_tce` — `Drivers\AssinaturaIcpTce` | `ASSINATURA_DRIVER`, `ASSINATURA_ICP_URL`, `ASSINATURA_ICP_TOKEN` | **Pendente**: contrato da API + **decisão do cliente** sobre o nível de assinatura |
| 4 | **CADUN (Cadastro Único)** | Fonte oficial de responsáveis/gestores; provisionamento no 1º login | `local` (cadastro provisório em *Usuários*) | `api` — `Drivers\CadunApi` | `CADUN_DRIVER`, `CADUN_API_URL`, `CADUN_API_TOKEN` | **Pendente**: API + regra de vínculo município/papel |
| 5 | **Calendário Oficial do TCE** | Prazo de assinatura (dias úteis) | `local` (fins de semana + feriados nacionais configuráveis) | `api` — `Drivers\CalendarioApi` (com cache e *fallback* local) | `CALENDARIO_DRIVER`, `CALENDARIO_API_URL`, `CALENDARIO_API_TOKEN`, `CALENDARIO_FERIADOS` | **Pendente**: API |
| 6 | **Gerenciamento de Arquivos do TCE** | Evidências (EP06) e materiais (EP11) | disk `local` (volume Docker) | *disk* customizado do Laravel | `SDGT_EVIDENCIAS_DISK`, `SDGT_MATERIAIS_DISK` | **Pendente**: API; implementar adaptador `Storage::extend` |
| 7 | **SFG (Sistema de Fiscalização da Gestão)** | Alerta automático de baixa aderência | `log` | `api` — `Drivers\SfgApi` | `SFG_DRIVER`, `SFG_API_URL`, `SFG_API_TOKEN` | **Pendente**: API + regra de disparo |
| 8 | **SMTP corporativo** | EP10 — e-mails | `log`/Mailpit | SMTP do Tribunal | `MAIL_*` | **Pendente**: host, credenciais, remetente oficial, SPF/DKIM |
| 9 | **SSO do TCE** (citado na Visão) | Login de servidores do Tribunal | usuário/senha local | — | — | **A definir**: se servidores devem usar SSO corporativo em vez de senha local |

## Detalhamento e contratos presumidos

### 1. GOV.BR
- Fluxo implementado: `/login/govbr` → `authorize` (PKCE S256, `state`, `nonce`) → `/login/govbr/callback` → `token` → `userinfo` → CPF (`sub`).
- O CPF é procurado em `users`; se não existir, é consultado o CADUN e o usuário é provisionado com o perfil e município do vínculo.
- **Necessário do TCE:** cadastro no gov.br (formulário de integração), `client_id`/`client_secret`, URL de retorno homologada (`https://<host>/login/govbr/callback`), definição de **nível mínimo de confiabilidade** (prata/ouro) — **decisão pendente**.

### 2. Certificado Digital
- O TLS mútuo deve ser negociado pelo proxy (Nginx/Apache/WAF), validando a cadeia ICP-Brasil e a revogação (OCSP/LCR). Exemplo Nginx:
  ```nginx
  ssl_verify_client optional;
  ssl_client_certificate /etc/ssl/icp-brasil-cadeia.pem;
  location /login/certificado { proxy_set_header X-SSL-CLIENT-CERT $ssl_client_escaped_cert; proxy_pass http://sdgt; }
  ```
- O sistema extrai o CPF do CN (`NOME:CPF`) ou do OID 2.16.76.1.3.1 e rejeita certificados expirados.

### 3. Assinatura ICP-Brasil
- Contrato presumido: `POST {URL}/assinaturas` `{cpf, documento_base64, referencia}` → `{id, hash, certificado_titular, certificado_emissor, assinado_em}`.
- Hoje: **assinatura eletrônica simples** (Lei 14.063/2020, art. 4º, I) — gestor autenticado + declaração + hash SHA-256 do conteúdo canônico (respostas e hashes das evidências) + carimbo de tempo. A Memória de 03/11 indica que GOV.BR é suficiente “desde que não haja necessidade de assinatura digital formal”.

### 4. CADUN
- Contrato presumido: `GET {URL}/pessoas/{cpf}` → `{nome, email, cpf, vinculos:[{codigo_ibge, papel: RESPONSAVEL_TRIBUTARIO|GESTOR, cargo}]}`.
- Com a API ativa, a tela *Usuários* torna-se apenas de consulta/ajuste (cadastro centralizado no CADUN, conforme Memória 03/11).

### 5. Calendário Oficial
- Contrato presumido: `GET {URL}/feriados?ano=AAAA` → `[{data: "AAAA-MM-DD", descricao}]`. Resultado em cache por 24 h; falha → driver local.

### 6. Gerenciamento de Arquivos
- `ArquivoService` usa *disks* do Laravel. Para a API do TCE, criar um adaptador Flysystem e registrá-lo:
  ```php
  Storage::extend('tce', fn ($app, $config) => new FilesystemAdapter(new Filesystem(new TceArquivosAdapter($config)), ...));
  ```
  e configurar `SDGT_EVIDENCIAS_DISK=tce`.

### 7. SFG
- Contrato presumido: `POST {URL}/alertas` `{codigo_ibge, origem:"SDGT", tipo:"ADERENCIA_BAIXA", indice, referencia}`. Disparo atual: na validação, quando a faixa é **baixa**.

## Como ativar uma integração (exemplo: CADUN)
```env
CADUN_DRIVER=api
CADUN_API_URL=https://api.tceto.tc.br/cadun/v1
CADUN_API_TOKEN=***
```
```bash
docker compose up -d   # recria os contêineres com o novo .env.docker
```
Os testes `AutenticacaoTest` já simulam as respostas HTTP dos contratos presumidos (GOV.BR + CADUN), servindo de especificação executável.
