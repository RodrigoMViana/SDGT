/* SDGT — protótipo: Município (EP05, EP06), Notificações (EP09) e página "Sobre". */
(function () {
  'use strict';
  var S = window.SDGT, UI = window.UI, h = UI.h, MUN = ['responsavel', 'gestor'];
  function eu() { return S.usuarioAtual(); }
  function meuPre(id) { var p = S.porId('preenchimentos', id); if (!p || p.municipio_id !== eu().municipio_id) S.erro('Questionário de outro município.'); return p; }
  function voltar(msg, destino, tipo) { UI.flash(tipo || 'sucesso', msg); UI.ir(destino); }

  /* HU05.01 */
  UI.rota('/municipio/questionarios', function () {
    var u = eu(), mun = S.porId('municipios', u.municipio_id), limite = S.somarDias(S.hojeISO(), 30);
    var qs = S.publicados().filter(function (q) { return S.abrange(q, u.municipio_id) && q.data_inicio.slice(0, 10) <= limite; });
    return UI.cab('Questionários de diagnóstico', h(mun.nome) + ' · ' + UI.PERFIL[u.perfil]) + (qs.length ? qs.map(function (q) {
      var p = S.t('preenchimentos').find(function (x) { return x.questionario_id === q.id && x.municipio_id === u.municipio_id; }), sit = S.situacao(q), st = p ? p.status : 'nao_iniciado';
      var c = function (cond, at) { return at ? 'atual' : (cond ? 'feito' : ''); };
      var acoes = '';
      if (p) {
        if (u.perfil === 'responsavel' && S.editavel(p)) acoes += '<a class="btn btn-primario" href="#/municipio/preenchimentos/' + p.id + '/editar">' + UI.ic('editar') + ' Continuar preenchimento</a>';
        if (u.perfil === 'gestor' && S.validavel(p)) acoes += '<a class="btn btn-sucesso" href="#/municipio/preenchimentos/' + p.id + '">' + UI.ic('assinar') + ' Revisar e validar</a>';
        acoes += '<a class="btn btn-neutro" href="#/municipio/preenchimentos/' + p.id + '">' + UI.ic('olho') + ' Visualizar</a>';
      } else acoes += u.perfil === 'responsavel' && sit === 'aberto' ? '<a class="btn btn-primario" href="#/municipio/questionarios/' + q.id + '">Iniciar preenchimento</a>' : '<a class="btn btn-neutro" href="#/municipio/questionarios/' + q.id + '">' + UI.ic('olho') + ' Ver questionário</a>';
      return '<div class="card"><div class="cartao-questionario"><div class="info"><h3 style="margin-bottom:4px">' + h(q.titulo) + '</h3><div class="acoes" style="gap:6px">' + UI.etq('questionario', sit) + UI.etq('preenchimento', st) +
        (p && S.CONCL.indexOf(st) >= 0 && p.indice_aderencia !== null ? '<span class="etiqueta contorno faixa-' + S.faixa(p.indice_aderencia) + '">Índice ' + UI.ind(p.indice_aderencia) + '</span>' : '') + '</div>' +
        '<div class="linha-tempo"><div class="' + c(true, sit === 'agendado') + '">Início<br>' + S.br(q.data_inicio) + '</div><div class="' + c(sit === 'assinatura' || sit === 'encerrado', sit === 'aberto') + '">Fim do preenchimento<br>' + S.br(q.data_fim) + '</div>' +
        '<div class="' + c(sit === 'encerrado', sit === 'assinatura') + '">Assinatura até<br>' + S.br(q.data_limite_assinatura) + '</div></div>' +
        (st === 'devolvido' ? '<div class="alerta erro texto-pequeno" style="margin:8px 0 0">' + UI.ic('alerta') + '<div>Devolvido pelo gestor: ' + h(p.observacao_devolucao) + '</div></div>' : '') + '</div><div class="acoes">' + acoes + '</div></div></div>';
    }).join('') : '<div class="card vazio">Nenhum questionário disponível para o município no momento.</div>');
  }, MUN);

  /* Apresentação inicial */
  UI.rota('/municipio/questionarios/:id', function (p) {
    var q = S.porId('questionarios', p.id), u = eu();
    var pre = S.t('preenchimentos').find(function (x) { return x.questionario_id === q.id && x.municipio_id === u.municipio_id; });
    var mats = S.t('materiais_apoio').filter(function (m) { return m.ativo; });
    var btn = pre ? '<a class="btn btn-primario" href="#/municipio/preenchimentos/' + pre.id + (S.editavel(pre) && u.perfil === 'responsavel' ? '/editar' : '') + '">Abrir preenchimento</a>'
      : (u.perfil === 'responsavel' && S.situacao(q) === 'aberto' ? '<button class="btn btn-primario" data-acao="iniciar" data-id="' + q.id + '">Li as orientações — iniciar preenchimento</button>' : '');
    return UI.cab(q.titulo, 'Preenchimento de ' + S.br(q.data_inicio) + ' a ' + S.br(q.data_fim) + ' · validação/assinatura até ' + S.br(q.data_limite_assinatura), null, '<a href="#/municipio/questionarios">Meus questionários</a> › Apresentação') +
      '<div class="card"><h3>Apresentação</h3><div class="texto-pre">' + h(q.apresentacao || 'Sem texto de apresentação.') + '</div>' +
      (mats.length ? '<h4 style="margin-top:16px">Materiais de apoio</h4><ul>' + mats.map(function (m) { return '<li>' + (m.url_externa && !m.arquivo_nome ? '<a href="' + h(m.url_externa) + '" target="_blank" rel="noopener">' + h(m.titulo) + '</a>' : '<a href="#" data-acao="baixarMaterial" data-id="' + m.id + '">' + h(m.titulo) + '</a>') + '</li>'; }).join('') + '</ul>' : '') +
      '<div class="acoes" style="margin-top:16px">' + btn + '<a class="btn btn-neutro" href="#/municipio/questionarios">' + UI.ic('voltar') + ' Voltar</a></div></div>' +
      '<div class="card-titulo"><h2>Perguntas do questionário</h2><span class="muted texto-pequeno">Todas as perguntas são exibidas para que o município prepare previamente os documentos comprobatórios.</span></div>' + UI.questoesLeitura(q.id, null);
  }, MUN);
  UI.acoes.iniciar = function (a) {
    var q = S.porId('questionarios', a.dataset.id);
    if (S.situacao(q) !== 'aberto') S.erro('O questionário não está no período de preenchimento.');
    var p = S.obterOuCriar(q, eu().municipio_id, eu()); UI.ir('#/municipio/preenchimentos/' + p.id + '/editar');
  };

  /* HU05.02 / 05.03 — preencher e retomar */
  UI.rota('/municipio/preenchimentos/:id/editar', function (p) {
    var pre = meuPre(p.id), q = S.porId('questionarios', pre.questionario_id), m = S.porId('municipios', pre.municipio_id);
    if (eu().perfil !== 'responsavel') S.erro('Somente o responsável municipal preenche o questionário.');
    if (!S.editavel(pre)) { setTimeout(function () { voltar('Este questionário não está disponível para edição (prazo encerrado ou já finalizado).', '#/municipio/preenchimentos/' + pre.id, 'aviso'); }); return ''; }
    var est = UI.estrutura(q.id, pre), tot = 0, resp = 0;
    est.forEach(function (g) { g.itens.forEach(function (i) { if (i.hab) { tot++; if (i.marc.length || (i.valor !== null && i.valor !== undefined && i.valor !== '')) resp++; } }); });
    UI.depois.push(ligarDependencias);
    return UI.cab(q.titulo, h(m.nome) + ' · prazo de preenchimento até <strong>' + S.br(q.data_fim) + '</strong>', UI.etq('preenchimento', pre.status), '<a href="#/municipio/questionarios">Meus questionários</a> › Preenchimento') +
      (pre.status === 'devolvido' ? '<div class="alerta erro">' + UI.ic('alerta') + '<div><strong>Devolvido pelo gestor para ajustes:</strong> ' + h(pre.observacao_devolucao) + '</div></div>' : '') +
      '<div class="alerta info">' + UI.ic('info') + '<div>As respostas podem ser <strong>salvas parcialmente</strong> e retomadas depois. Perguntas condicionais ficam visíveis, mas só são habilitadas conforme a resposta anterior. Prefira anexar documentos em <strong>PDF textual (pesquisável)</strong>.</div></div>' +
      '<form id="form-preenchimento" data-form="salvarRespostas"><input type="hidden" name="pre" value="' + pre.id + '">' + est.map(function (g) {
        return '<section class="tema-bloco"><h2>' + h(g.tema.nome) + '</h2><div class="perguntas">' + g.itens.map(function (i) {
          var pg = i.p, dis = i.hab ? '' : ' disabled', nm = 'respostas[' + pg.id + ']', temEv = pg.exige_evidencia || i.ops.some(function (o) { return o.solicita_evidencia; });
          var campo;
          if (pg.tipo === 'unica') campo = i.ops.map(function (o) { return '<label class="check"><input type="radio" name="' + nm + '[opcao]" value="' + o.id + '"' + (i.marc.indexOf(o.id) >= 0 ? ' checked' : '') + dis + '> ' + h(o.texto) + (o.solicita_evidencia ? ' <span class="muted texto-pequeno">(requer documento)</span>' : '') + '</label>'; }).join('');
          else if (pg.tipo === 'multipla') campo = i.ops.map(function (o) { return '<label class="check"><input type="checkbox" name="' + nm + '[opcoes][]" value="' + o.id + '"' + (i.marc.indexOf(o.id) >= 0 ? ' checked' : '') + dis + '> ' + h(o.texto) + '</label>'; }).join('');
          else if (pg.tipo === 'texto') campo = '<textarea name="' + nm + '[valor]" aria-label="Resposta ' + h(pg.codigo) + '"' + dis + '>' + h(i.valor) + '</textarea>';
          else campo = '<input type="' + (pg.tipo === 'data' ? 'date' : 'text') + '" inputmode="' + (pg.tipo === 'numero' ? 'decimal' : 'text') + '" name="' + nm + '[valor]" value="' + h(i.valor) + '" style="max-width:220px" aria-label="Resposta ' + h(pg.codigo) + '"' + dis + '>';
          return '<div class="pergunta' + (i.hab ? '' : ' desabilitada') + '" id="pergunta-' + pg.id + '"' + (pg.pergunta_pai_id ? ' data-pai="' + pg.pergunta_pai_id + '" data-habilitadoras="' + S.habilitadoras(pg.id).join(',') + '"' : '') + '>' +
            '<input type="hidden" name="' + nm + '[presente]" value="1"><div class="enunciado"><span class="codigo">' + h(pg.codigo) + '</span><span>' + h(pg.enunciado) + (pg.obrigatoria ? '<span class="obrigatorio"></span>' : '') + '</span></div>' +
            (i.pai ? '<div class="condicao">' + UI.ic('info') + ' Responda somente se ' + h(i.pai.codigo) + ' = “' + h(i.habTxt.join('” ou “')) + '”.</div>' : '') +
            (pg.ajuda || pg.link_apoio ? '<div class="ajuda">' + UI.ic('info') + '<span>' + h(pg.ajuda) + (pg.link_apoio ? ' <a href="' + h(pg.link_apoio) + '" target="_blank" rel="noopener">Material de apoio ›</a>' : '') + '</span></div>' : '') +
            '<div class="opcoes">' + campo + '</div>' +
            (temEv ? '<div class="evidencias' + (i.exigida && !i.evs.length ? ' exigida' : '') + '"><h4>' + UI.ic('clip') + ' Documentos comprobatórios' + (i.exigida && !i.evs.length ? ' <span class="etiqueta amarelo">Obrigatório para a resposta selecionada</span>' : '') + '</h4>' +
              '<ul class="lista-arquivos">' + i.evs.map(function (e) { return '<li><a href="#" data-acao="baixarEvidencia" data-id="' + e.id + '">' + UI.ic('download') + ' ' + h(e.nome_original) + '</a> <span class="muted texto-pequeno">' + Math.max(1, Math.round(e.tamanho / 1024)) + ' KB</span> <button type="button" class="btn btn-link perigo btn-pequeno" data-acao="excluirEvidencia" data-id="' + e.id + '" data-confirm="Excluir o documento?">' + UI.ic('lixo') + ' Excluir</button></li>'; }).join('') + '</ul>' +
              '<div class="upload-linha"><input type="file" id="arq-' + pg.id + '" accept=".pdf,.doc,.docx,.odt,.xls,.xlsx,.ods,.csv,.png,.jpg,.jpeg" aria-label="Arquivo para ' + h(pg.codigo) + '"><button type="button" class="btn btn-secundario btn-pequeno" data-acao="anexar" data-pergunta="' + pg.id + '" data-pre="' + pre.id + '">' + UI.ic('upload') + ' Anexar</button><span class="muted texto-pequeno">Até 2 MB na demonstração</span></div></div>' : '') + '</div>';
        }).join('') + '</div></section>';
      }).join('') +
      '<div class="barra-fixa"><div class="resumo-progresso"><span class="texto-pequeno"><strong>' + resp + '</strong> de ' + tot + ' respondidas</span><div class="progresso"><span style="width:' + (tot ? Math.round(resp / tot * 100) : 0) + '%"></span></div></div>' +
      '<div class="acoes"><button class="btn btn-neutro" type="submit" name="acao" value="salvar">Salvar e continuar depois</button><button class="btn btn-primario" type="submit" name="acao" value="revisar">Salvar e revisar para finalizar ›</button></div></div></form>';
  }, ['responsavel']);
  function ligarDependencias() {
    var f = document.getElementById('form-preenchimento'); if (!f) return;
    var marc = function (pid) { return Array.prototype.map.call(f.querySelectorAll('[name="respostas[' + pid + '][opcao]"]:checked, [name="respostas[' + pid + '][opcoes][]"]:checked'), function (i) { return i.value; }); };
    var atualizar = function () {
      for (var v = 0; v < 10; v++) {
        var mudou = false;
        f.querySelectorAll('.pergunta[data-pai]').forEach(function (d) {
          var pai = d.dataset.pai, hab = d.dataset.habilitadoras.split(','), pd = document.getElementById('pergunta-' + pai);
          var ok = (!pd || !pd.classList.contains('desabilitada')) && marc(pai).some(function (x) { return hab.indexOf(x) >= 0; });
          if (d.classList.contains('desabilitada') === ok) mudou = true;
          d.classList.toggle('desabilitada', !ok);
          d.querySelectorAll('.opcoes input, .opcoes textarea').forEach(function (i) { i.disabled = !ok; if (!ok) { if (i.type === 'radio' || i.type === 'checkbox') i.checked = false; else i.value = ''; } });
        });
        if (!mudou) break;
      }
    };
    f.addEventListener('change', atualizar); atualizar();
  }
  function coletar() { var f = document.getElementById('form-preenchimento'); return f ? (UI.lerForm(f).respostas || {}) : null; }
  UI.forms.salvarRespostas = function (d) {
    var pre = meuPre(d.pre);
    S.salvarRespostas(pre, d.respostas || {});
    if (d.acao === 'revisar') return UI.ir('#/municipio/preenchimentos/' + pre.id + '/revisar');
    voltar('Respostas salvas. Você pode continuar depois — o progresso fica gravado.', location.hash);
  };
  UI.acoes.anexar = function (a) {
    var pre = meuPre(a.dataset.pre), pid = +a.dataset.pergunta, inp = document.getElementById('arq-' + pid), arq = inp && inp.files[0];
    if (!arq) S.erro('Selecione um arquivo para anexar.');
    var ext = arq.name.split('.').pop().toLowerCase();
    if (['pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'ods', 'csv', 'png', 'jpg', 'jpeg'].indexOf(ext) < 0) S.erro('Tipo de arquivo não permitido.');
    if (arq.size > 2097152) S.erro('Na demonstração o arquivo deve ter até 2 MB (na aplicação real: 20 MB).');
    var r = coletar(); if (r) S.salvarRespostas(pre, r); // salva respostas antes, evitando perda
    var fr = new FileReader();
    fr.onload = function () {
      S.inserir('evidencias', { preenchimento_id: pre.id, pergunta_id: pid, nome_original: arq.name, mime: arq.type, tamanho: arq.size, conteudo: fr.result });
      S.salvar(); voltar('Documento anexado com sucesso.' + (/^image\//.test(arq.type) ? ' Recomendação: prefira PDF textual (pesquisável) em vez de imagens.' : ''), location.hash);
      setTimeout(function () { var el = document.getElementById('pergunta-' + pid); if (el) el.scrollIntoView(); }, 50);
    };
    fr.readAsDataURL(arq);
  };
  UI.acoes.excluirEvidencia = function (a) {
    var e = S.porId('evidencias', a.dataset.id), pre = meuPre(e.preenchimento_id);
    if (!S.editavel(pre)) S.erro('O questionário não está aberto para alterações.');
    var r = coletar(); if (r) S.salvarRespostas(pre, r);
    S.remover('evidencias', function (x) { return x.id === e.id; }); S.salvar(); voltar('Documento excluído.', location.hash);
  };

  /* HU05.04 — revisar e finalizar */
  UI.rota('/municipio/preenchimentos/:id/revisar', function (p) {
    var pre = meuPre(p.id), q = S.porId('questionarios', pre.questionario_id), m = S.porId('municipios', pre.municipio_id), pend = S.pendencias(pre), dis = pend.length ? ' disabled' : '';
    return UI.cab('Revisar respostas', h(q.titulo) + ' · ' + h(m.nome), '<a class="btn btn-neutro" href="#/municipio/preenchimentos/' + pre.id + '/editar">' + UI.ic('voltar') + ' Voltar ao preenchimento</a>', '<a href="#/municipio/questionarios">Meus questionários</a> › Revisão') +
      (pend.length ? '<div class="alerta erro">' + UI.ic('alerta') + '<div><strong>Há ' + pend.length + ' pendência(s) que impedem a finalização:</strong><ul>' + pend.map(function (x) { return '<li>' + h(x) + '</li>'; }).join('') + '</ul></div></div>'
        : '<div class="alerta sucesso">' + UI.ic('check') + '<div>Todas as respostas obrigatórias e evidências exigidas foram informadas. Revise e finalize.</div></div>') +
      UI.questoesLeitura(q.id, pre) +
      '<form class="card" data-form="finalizar"><input type="hidden" name="pre" value="' + pre.id + '"><div class="declaracao"><label class="check"><input type="checkbox" name="declaracao" value="1" required' + dis + '><span>Declaro que as informações prestadas são verdadeiras e estão amparadas pelos documentos anexados, e que, após a finalização, o questionário será encaminhado ao gestor municipal para validação e assinatura.</span></label></div>' +
      '<div class="acoes" style="margin-top:16px"><button class="btn btn-primario"' + dis + '>' + UI.ic('check') + ' Finalizar e encaminhar ao gestor</button><a class="btn btn-neutro" href="#/municipio/preenchimentos/' + pre.id + '/editar">Continuar editando</a></div></form>';
  }, ['responsavel']);
  UI.forms.finalizar = function (d) { var pre = meuPre(d.pre); S.finalizar(pre); voltar('Preenchimento finalizado e encaminhado ao gestor municipal para validação.', '#/municipio/preenchimentos/' + pre.id); };

  /* HU05.05 / 05.06 / 05.07 */
  UI.rota('/municipio/preenchimentos/:id', function (p) {
    var pre = meuPre(p.id), q = S.porId('questionarios', pre.questionario_id), m = S.porId('municipios', pre.municipio_id), u = eu();
    var gestorPode = u.perfil === 'gestor' && S.validavel(pre);
    var acoes = (u.perfil === 'responsavel' && S.editavel(pre) ? '<a class="btn btn-primario" href="#/municipio/preenchimentos/' + pre.id + '/editar">' + UI.ic('editar') + ' Continuar preenchimento</a>' : '') +
      (gestorPode ? '<button class="btn btn-sucesso" data-abrir-modal="modal-validar">' + UI.ic('assinar') + ' Validar e assinar</button>' + (S.situacao(q) === 'aberto' ? '<button class="btn btn-neutro" data-abrir-modal="modal-devolver">Devolver para ajustes</button>' : '') : '') +
      '<button class="btn btn-neutro" data-acao="imprimir">' + UI.ic('download') + ' Baixar PDF</button>';
    return UI.cab(q.titulo, h(m.nome) + ' · ' + S.SIT[S.situacao(q)], acoes, '<a href="#/municipio/questionarios">Meus questionários</a> › Visualizar') +
      (gestorPode ? '<div class="alerta aviso">' + UI.ic('assinar') + '<div>O responsável finalizou o preenchimento. Revise as respostas e realize a <strong>validação/assinatura até ' + S.br(q.data_limite_assinatura) + '</strong>.</div></div>' : '') +
      UI.resumoPreenchimento(pre) + UI.questoesLeitura(q.id, pre) +
      (gestorPode ? UI.modal('modal-validar', 'Validar e assinar o questionário', '<input type="hidden" name="pre" value="' + pre.id + '"><div class="declaracao"><label class="check"><input type="checkbox" name="declaracao" value="1" required><span>Eu, <strong>' + h(u.name) + '</strong>, na qualidade de gestor(a) do município de ' + h(m.nome) + ', declaro, sob as penas da lei, que revisei as informações prestadas neste questionário e atesto sua veracidade e consistência.</span></label></div>' +
        '<p class="muted texto-pequeno" style="margin-top:12px">A assinatura registra sua identificação, data/hora e o hash SHA-256 do conteúdo das respostas. Após a validação, o questionário não poderá ser alterado.</p>', '<button class="btn btn-sucesso">' + UI.ic('assinar') + ' Assinar e validar</button>', 'validar') +
        UI.modal('modal-devolver', 'Devolver para ajustes', '<input type="hidden" name="pre" value="' + pre.id + '"><label class="obrigatorio" for="mot">Motivo / ajustes necessários</label><textarea id="mot" name="motivo" minlength="10" required></textarea>', '<button class="btn btn-perigo">Devolver</button>', 'devolver') : '');
  }, MUN);
  UI.forms.validar = function (d) { document.getElementById('modal-validar').close(); var pre = meuPre(d.pre); return S.validar(pre).then(function () { voltar('Questionário validado e assinado. Obrigado!', location.hash); }); };
  UI.forms.devolver = function (d) { document.getElementById('modal-devolver').close(); S.devolver(meuPre(d.pre), d.motivo); voltar('Questionário devolvido ao responsável para ajustes.', location.hash); };

  /* EP09 — Notificações */
  var TIPOS_N = { inicio_preenchimento: 'Início do período de preenchimento', lembrete_encerramento: 'Lembrete de encerramento', encerramento_preenchimento: 'Encerramento do período de preenchimento', prorrogacao: 'Prorrogação de prazo', aguardando_validacao: 'Questionário aguardando validação', devolucao: 'Questionário devolvido para ajustes', validacao: 'Questionário validado', geral: 'Aviso' };
  UI.rota('/notificacoes', function (p, q) {
    var lista = S.t('notificacoes').filter(function (n) { return n.user_id === eu().id && (q.filtro !== 'nao_lidas' || !n.lida_em); }).sort(function (a, b) { return a.created_at < b.created_at ? 1 : -1; });
    return UI.cab('Notificações', 'Avisos sobre início, prazos e encerramento dos questionários.', '<button class="btn btn-neutro" data-acao="lerTodas">' + UI.ic('check') + ' Marcar todas como lidas</button>') +
      '<div class="abas"><a href="#/notificacoes"' + (q.filtro !== 'nao_lidas' ? ' class="ativo"' : '') + '>Todas</a><a href="#/notificacoes?filtro=nao_lidas"' + (q.filtro === 'nao_lidas' ? ' class="ativo"' : '') + '>Não lidas</a></div>' +
      '<div class="card" style="padding:0"><ul class="lista-notificacoes">' + (lista.length ? lista.map(function (n) {
        return '<li' + (n.lida_em ? ' class="lida"' : '') + '><a href="#/notificacoes/' + n.id + '"><span class="ponto"></span><span style="flex:1"><strong>' + h(n.titulo) + '</strong><br><span class="muted texto-pequeno">' + (TIPOS_N[n.tipo] || n.tipo) + ' · ' + S.brHora(n.created_at) + '</span></span>' + (n.lida_em ? '' : '<span class="etiqueta azul">Nova</span>') + '</a></li>';
      }).join('') : '<li class="vazio" style="padding:32px">Nenhuma notificação.</li>') + '</ul></div>';
  });
  UI.rota('/notificacoes/:id', function (p) {
    var n = S.porId('notificacoes', p.id);
    if (!n || n.user_id !== eu().id) S.erro('Notificação não encontrada.');
    if (!n.lida_em) { n.lida_em = S.agoraISO(); S.salvar(); }
    return '<div class="migalhas"><a href="#/notificacoes">Notificações</a> › Detalhe</div><div class="card"><div class="card-titulo"><h2>' + h(n.titulo) + '</h2><span class="etiqueta azul">' + (TIPOS_N[n.tipo] || n.tipo) + '</span></div>' +
      '<p class="muted texto-pequeno">Recebida em ' + S.brHora(n.created_at) + ' · na aplicação real também é enviada por e-mail</p><p class="texto-pre">' + h(n.mensagem) + '</p>' +
      '<div class="acoes"><a href="#/notificacoes" class="btn btn-neutro">' + UI.ic('voltar') + ' Voltar</a>' + (n.link ? '<a href="' + h(n.link) + '" class="btn btn-primario">Ir para o questionário</a>' : '') + '</div></div>';
  });
  UI.acoes.lerTodas = function () { S.t('notificacoes').forEach(function (n) { if (n.user_id === eu().id && !n.lida_em) n.lida_em = S.agoraISO(); }); S.salvar(); voltar('Todas as notificações foram marcadas como lidas.', location.hash); };

  /* Sobre a demonstração */
  UI.rota('/sobre', function () {
    return UI.cab('Sobre esta demonstração', 'Protótipo navegável publicado no GitHub Pages.') +
      '<div class="card"><p>Esta página reproduz as telas e regras de negócio do <strong>Sistema de Diagnóstico da Gestão Tributária Municipal</strong> para avaliação pelo cliente. ' +
      'A aplicação oficial é desenvolvida em <strong>Laravel + Blade + SQL Server</strong>, empacotada em <strong>Docker</strong> (veja o repositório e a documentação).</p>' +
      '<ul><li>Os dados são <strong>fictícios</strong> e ficam salvos <strong>somente neste navegador</strong> (localStorage). As datas são ajustadas para que o ciclo 2026 esteja sempre aberto.</li>' +
      '<li>Integrações (GOV.BR, Certificado ICP-Brasil, CADUN, Calendário Oficial, SFG, repositório de arquivos e e-mail) estão <strong>simuladas</strong>.</li>' +
      '<li>Exportações: PDF usa a impressão do navegador; XLS é gerado no navegador. Na aplicação real, PDF (DomPDF) e XLSX (PhpSpreadsheet) são gerados no servidor.</li></ul>' +
      '<p><a href="docs/">Documentação do projeto</a> · <a href="docs/07-fluxo-de-validacao.html">Fluxo de validação (roteiro de testes)</a></p>' +
      '<button class="btn btn-perigo" data-acao="resetar" data-confirm="Restaurar a base de demonstração? Todas as alterações feitas neste navegador serão perdidas.">Restaurar dados de demonstração</button></div>';
  });
  UI.acoes.resetar = function () { localStorage.removeItem('sdgt-demo-v1'); S.carregar(true).then(function () { var u = S.porId('users', eu().id); S.definirUsuario(u); voltar('Base de demonstração restaurada.', '#/'); }); };
})();
