# 06 — O que falta fazer

Situação: **todas as 65 HUs dos 12 épicos do Documento de Visão estão implementadas e testadas**, com integrações em modo simulado. Abaixo, o que depende do cliente (TCE/TO), das integrações e as recomendações para produção.

## 1. Decisões pendentes do cliente

| # | Decisão | Impacto | Valor provisório adotado | Onde ajustar |
|---|---|---|---|---|
| D1 | **Responsável pelas integrações/APIs** (DINFO/CODES) — encaminhamento da reunião de 06/11 | Cronograma e ativação dos drivers reais | Drivers simulados | `.env` |
| D2 | **Nível de assinatura do gestor**: eletrônica simples (GOV.BR) × qualificada ICP-Brasil | HU05.07 | Simples + hash SHA-256 | `ASSINATURA_DRIVER` |
| D3 | **Nível de confiabilidade gov.br** exigido (bronze/prata/ouro) | Segurança do login | Não verificado | `GovBrOidc` (escopo `govbr_confiabilidades`) |
| D4 | **Faixas de aderência** (baixa/média/alta) | Painéis, relatórios, alertas SFG | ≤ 49,99 / ≤ 74,99 / acima | `SDGT_FAIXA_*` |
| D5 | **Banco de perguntas definitivo**, pesos e pontuações | Índice de aderência | 27 perguntas sugeridas em 6 temas | Tela *Perguntas* ou `BancoPerguntasSeeder` |
| D6 | **Distribuição oficial dos municípios por relatoria** (e conselheiro de cada uma) | HU07.03 | Distribuição sequencial provisória em 6 relatorias | Tela *Municípios e relatorias* |
| D7 | **Comportamento das perguntas condicionais** — "será validado por protótipos" (Memória 06/11) | UX do preenchimento | Visíveis e desabilitadas até a condição | Demonstração no Pages |
| D8 | **Regra de disparo do alerta ao SFG** (só baixa? também média? na validação ou no encerramento?) | Integração SFG | Na validação, faixa baixa | `PreenchimentoService::validar` |
| D9 | **Devolução pelo gestor** (funcionalidade adicional, não prevista nas HUs) | Fluxo EP05 | Implementada, apenas durante o período aberto | Pode ser desativada |
| D10 | **Personalização por nível de aderência** (Memória 03/11 — "possibilidade futura") | Questionários personalizados | Abrangência por municípios selecionados + duplicação | — |
| D11 | **Painel individual do município** com evolução histórica (Memória 03/11 — "interesse") | Novo escopo | Município vê o índice na lista; sem painel próprio | Evolução futura |
| D12 | **Periodicidade e textos das notificações** (lembrete com quantos dias? repetir?) | EP09/EP10 | Lembrete único, 3 dias antes | `SDGT_DIAS_LEMBRETE` |
| D13 | **Servidores do Tribunal via SSO corporativo** em vez de senha local | EP01 | Senha local | Nova integração |
| D14 | **Retenção/LGPD** dos documentos e logs | Operação | Sem expurgo automático | Política a definir |
| D15 | **Cronograma** (proposta de concluir desenvolvimento em janeiro e integrar em fevereiro) | Planejamento | — | Após D1 |

## 2. Integrações a concluir (dependem de APIs do TCE)

| Integração | O que falta | Esforço estimado após API disponível |
|---|---|---|
| GOV.BR | Credenciamento do serviço; homologar em staging | Baixo (driver pronto) |
| Certificado digital | mTLS no proxy com cadeia ICP-Brasil e OCSP | Baixo (infra) |
| Assinatura ICP-Brasil | Contrato da API e fluxo de assinatura (pode exigir assinador no cliente) | Médio |
| CADUN | Contrato real; sincronização periódica opcional; tornar *Usuários* somente leitura | Baixo/Médio |
| Calendário Oficial | Contrato real | Baixo |
| Gerenciamento de Arquivos | Adaptador Flysystem para a API | Médio |
| SFG | Contrato real e regra (D8) | Baixo |
| SMTP | Credenciais e remetente oficial | Baixo |

Detalhes e contratos presumidos em [02 — Integrações Pendentes](02-integracoes-pendentes.md).

## 3. Recomendações técnicas antes da produção

1. Homologar o `docker-compose` na infraestrutura do TCE (o CI já valida build, migrações e testes em SQL Server 2022).
2. Ativar HTTPS, `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, desativar `SDGT_LOGIN_SIMULADO` e `SDGT_SEED_DEMO`.
3. Trocar a senha do administrador inicial e criar os administradores nominais.
4. Teste de carga (139 municípios preenchendo em paralelo no último dia do prazo) e ajuste de `pm`/workers.
5. Avaliação de acessibilidade (e-MAG) e testes em navegadores homologados pelo TCE.
6. Hospedar a fonte Montserrat localmente se a rede do Tribunal bloquear o Google Fonts.
7. Política de backup do banco e do volume de evidências; antivírus nos uploads (ex.: ClamAV), se exigido pela segurança da informação.
8. Monitoramento (logs centralizados, alertas de fila com falha).
9. Revisão jurídica dos textos de declaração (finalização e assinatura).

## 4. Evoluções sugeridas (fora do escopo atual)

- Painel do município com sua evolução e comparação com a média estadual/regional.
- Importação/exportação do banco de perguntas (planilha) e versionamento de questionários.
- Questionários diferenciados automaticamente por faixa de aderência do ciclo anterior.
- Mapa coroplético dos municípios por índice.
- API somente leitura para consumo por outros sistemas do Tribunal.
- Plano de ação: município registra providências para itens de baixa pontuação.
