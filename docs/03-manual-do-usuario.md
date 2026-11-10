# 03 — Manual do Usuário

Este manual descreve o uso do SDGT por perfil. As telas seguem o Guia de Estilo do TCE/TO: cabeçalho com o nome do sistema, ícone de **notificações** (com contador de não lidas), nome/perfil do usuário e botão **Sair**; menu lateral à esquerda (recolhível em celulares).

> Para conhecer o sistema sem instalar nada, use a **demonstração**: `https://rodrigomviana.github.io/SDGT/` (dados fictícios salvos só no seu navegador).

## Perfis

| Perfil | Quem é | Acesso | O que faz |
|---|---|---|---|
| **Administrador do Sistema** | Servidor do TCE gestor da plataforma | e-mail/CPF + senha | Temas, perguntas, questionários (publicar, prorrogar, duplicar), materiais, usuários, auditoria, painéis e relatórios |
| **Auditor / Técnico do Tribunal** | Servidores de fiscalização | e-mail/CPF + senha | Painéis, relatórios, visualização dos questionários preenchidos |
| **Responsável Municipal** | Secretário de Fazenda/Finanças ou equivalente | GOV.BR ou Certificado Digital | Preenche o questionário, anexa evidências e finaliza |
| **Gestor Municipal** | Prefeito ou autoridade designada | GOV.BR ou Certificado Digital | Revisa, **valida e assina** ou devolve para ajustes |

## 1. Acesso (EP01)

- **Municípios:** clique em **Entrar com gov.br** (conta gov.br do CPF cadastrado no CADUN) ou **Entrar com Certificado Digital ICP-Brasil** (e-CPF instalado no computador/token).
- **Tribunal:** informe e-mail ou CPF e a senha.
- Em homologação aparece a caixa **Acesso simulado**, que permite entrar como qualquer usuário de teste.

## 2. Responsável municipal

### 2.1 Meus questionários (HU05.01)
Lista os questionários disponíveis para o município, com a **linha do tempo** (início → fim do preenchimento → prazo de assinatura), a situação do questionário e do preenchimento e, após a finalização, o índice obtido.

### 2.2 Apresentação e início
Clique em **Iniciar preenchimento**. A tela de apresentação mostra os normativos/portarias, os materiais de apoio e **todas as perguntas** — permitindo preparar os documentos antes. Clique em **Li as orientações — iniciar preenchimento**.

### 2.3 Preencher, salvar e retomar (HU05.02, HU05.03)
- As perguntas são agrupadas pelos 6 temas. Itens com * são obrigatórios.
- O quadro azul **ajuda contextual** orienta cada pergunta; “Material de apoio ›” abre a cartilha relacionada.
- **Perguntas condicionais** aparecem esmaecidas com o aviso “Responda somente se…”; elas são habilitadas automaticamente quando a resposta anterior atende à condição.
- Use **Salvar e continuar depois** a qualquer momento. Ao voltar, use **Continuar preenchimento**: as respostas estarão lá. A barra inferior mostra o progresso.

### 2.4 Evidências (EP06)
- Em perguntas que pedem comprovação há o quadro **Documentos comprobatórios**. Escolha o arquivo e clique em **Anexar** (PDF, DOC/DOCX, ODT, XLS/XLSX, ODS, CSV, PNG, JPG — até 20 MB).
- Quando a resposta marcada exige documento (ex.: “Há projeto em tramitação” → anexar o projeto), o quadro fica laranja com a etiqueta **Obrigatório**.
- Prefira **PDF textual (pesquisável)**; imagens geram um aviso.
- Clique no nome para **baixar** e em **Excluir** para remover (somente enquanto o prazo estiver aberto).

### 2.5 Revisar e finalizar (HU05.04)
Clique em **Salvar e revisar para finalizar**. O sistema lista as **pendências** (obrigatórias sem resposta, evidências faltantes). Sem pendências, marque a **declaração de veracidade** e clique em **Finalizar e encaminhar ao gestor**. O gestor é notificado.

### 2.6 Devolução
Se o gestor devolver, você recebe notificação com o motivo; o questionário volta a ficar editável (dentro do prazo).

## 3. Gestor municipal

1. Abra a notificação **Questionário aguardando sua validação** ou **Meus questionários → Revisar e validar**.
2. Confira o resumo (índice por tema), as respostas e as evidências.
3. **Validar e assinar:** marque a declaração e confirme. O sistema registra identificação, data/hora e o hash do conteúdo; o questionário fica bloqueado.
4. **Devolver para ajustes:** informe o motivo (possível apenas durante o período de preenchimento).
5. **Baixar PDF** gera o questionário preenchido (HU05.06).

> Prazos: preenchimento padrão de 15 dias corridos; validação até 1 dia útil após o fim. Encerrado o prazo de assinatura, tudo fica apenas para consulta.

## 4. Administrador

### 4.1 Temas de avaliação (EP02)
**Diagnóstico → Temas de avaliação**: listar, cadastrar, editar, ativar/inativar e excluir (excluir só é permitido se o tema não tiver perguntas — caso contrário, inative).

### 4.2 Perguntas e respostas (EP04)
- **Cadastrar pergunta:** código, tema, tipo (única, múltipla, texto, número, data), enunciado, ajuda contextual, link de apoio, peso (0 = informativa), obrigatória, “sempre exigir evidência”.
- Em **Respostas**, cadastre as opções com **pontuação** e, se for o caso, **solicita evidência**.
- **Dependência:** escolha a pergunta condicionante e as respostas que habilitam a pergunta atual. Ciclos são impedidos.
- Perguntas usadas em questionário publicado ficam **bloqueadas** (só ajuda/link podem mudar). Para alterar conteúdo, crie nova pergunta.

### 4.3 Questionários (EP03)
1. **Cadastrar questionário:** título, ciclo, descrição, **apresentação** (normativos), datas e **abrangência** (todos ou municípios selecionados — questionário personalizado). O prazo de assinatura em branco é calculado (fim + 1 dia útil).
2. **Associar perguntas:** marque as perguntas por tema e ajuste a ordem. Perguntas condicionais exigem a pergunta-pai.
3. **Visualizar / Baixar PDF** do questionário em branco.
4. **Publicar:** o sistema valida (perguntas, opções, datas, abrangência), registra auditoria e notifica responsáveis e gestores (no início do período).
5. **Prorrogar prazo:** novas datas + justificativa (auditado e notificado). Também reabre questionário encerrado.
6. **Duplicar:** cria cópia em rascunho com perguntas e abrangência.
7. **Excluir:** somente rascunhos sem preenchimentos.

### 4.4 Materiais de apoio (EP11)
**Geral → Materiais de apoio → Cadastrar material**: título, categoria (cartilha, manual, normativo, outro), descrição, arquivo (até 50 MB) ou link externo, e se fica disponível aos municípios.

### 4.5 Auditoria (EP12)
**Administração → Auditoria**: filtros por ação, período e texto; **Exportar PDF/XLS** da lista; **Detalhes** mostra dados anteriores × novos, justificativa, usuário, IP e navegador; **Exportar PDF** do registro.

### 4.6 Usuários e municípios
- **Usuários** (provisório até a integração com o CADUN): cadastro com perfil, CPF (obrigatório para municipais) e município. Excluir = inativar.
- **Municípios e relatorias:** vincule cada município à sua relatoria (a distribuição inicial é provisória).

## 5. Painel e relatórios (Administrador e Auditor)

| Tela | O que mostra |
|---|---|
| **Panorama consolidado** (HU07.01) | Municípios abrangidos, participação, índice médio, municípios em baixa aderência; gráficos por situação, faixa e tema; 10 menores e 10 maiores índices; tabela por região imediata |
| **Comparar municípios** (HU07.02) | Até 6 municípios lado a lado, por tema (gráfico + tabela) |
| **Desempenho por relatoria** (HU07.03) | Participação, índice médio e distribuição por faixa por relatoria, com os menores índices de cada uma |
| **Evolução da adequação** (HU07.04) | Série histórica dos ciclos (índice médio, participação, faixas), com opção de destacar um município |
| **Questionário preenchido** (HU07.05/06) | Respostas, evidências, assinatura e índice por tema; **Baixar PDF** |
| **Relatório de resultados** (EP08) | Filtros por questionário, relatoria, região, faixa e situação; **Exportar PDF** e **Exportar XLS** |

## 6. Notificações (EP09/EP10)
O sino no topo mostra o total de não lidas. Em **Notificações** há abas *Todas* / *Não lidas*; abrir uma notificação marca como lida e oferece o link para o questionário. Avisos de início, lembrete (3 dias antes do fim), encerramento, prorrogação, validação e devolução também são enviados por **e-mail**.

## 7. Dúvidas frequentes
- **Não consigo editar o questionário:** o prazo encerrou ou ele já foi finalizado/validado.
- **Pergunta esmaecida:** é condicional e não se aplica à resposta dada anteriormente.
- **Botão Finalizar desabilitado:** resolva as pendências listadas na revisão.
- **CPF não habilitado:** o CPF não está no CADUN para o município — procure o Tribunal.
