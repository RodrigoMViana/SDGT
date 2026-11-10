/*
 * SDGT — Protótipo navegável (GitHub Pages)
 * Camada de dados + regras de negócio (porta fiel de app/Services/*.php).
 * Os dados ficam no localStorage do navegador; a base inicial é exportada pela aplicação
 * Laravel (php artisan sdgt:exportar-demo) e as datas são deslocadas para "hoje".
 */
(function () {
  'use strict';
  var CHAVE = 'sdgt-demo-v1';
  var db = null;

  /* ---------------- datas ---------------- */
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function hojeISO() { var d = new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function agoraISO() { var d = new Date(); return hojeISO() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()); }
  function parseData(s) { var p = String(s).slice(0, 10).split('-'); return new Date(Date.UTC(+p[0], +p[1] - 1, +p[2])); }
  function fmtISO(d) { return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()); }
  function somarDias(s, n) { var d = parseData(s); d.setUTCDate(d.getUTCDate() + n); return fmtISO(d) + String(s).slice(10); }
  function diffDias(a, b) { return Math.round((parseData(b) - parseData(a)) / 86400000); }
  function br(s) { if (!s) return '—'; var p = String(s).slice(0, 10).split('-'); return p[2] + '/' + p[1] + '/' + p[0]; }
  function brHora(s) { if (!s) return '—'; return br(s) + ' ' + String(s).slice(11, 16); }

  /* Calendário (driver local): fins de semana + feriados nacionais fixos */
  var FERIADOS = ['01-01', '04-21', '05-01', '09-07', '10-05', '10-12', '11-02', '11-15', '11-20', '12-25'];
  function diaUtil(s) { var d = parseData(s), w = d.getUTCDay(); return w !== 0 && w !== 6 && FERIADOS.indexOf(String(s).slice(5, 10)) < 0; }
  function somarDiasUteis(s, n) { var d = String(s).slice(0, 10), k = 0; while (k < n) { d = somarDias(d, 1); if (diaUtil(d)) k++; } return d; }

  /* ---------------- persistência ---------------- */
  function deslocar(seed) {
    var dias = diffDias(seed.gerado_em, hojeISO());
    if (!dias) return seed;
    var re = /^\d{4}-\d{2}-\d{2}/;
    Object.keys(seed).forEach(function (t) {
      if (!Array.isArray(seed[t])) return;
      seed[t].forEach(function (r) {
        Object.keys(r).forEach(function (k) {
          if (typeof r[k] === 'string' && re.test(r[k]) && r[k].length <= 19) r[k] = somarDias(r[k], dias);
          if (r[k] && typeof r[k] === 'object' && !Array.isArray(r[k]) && (k === 'dados_anteriores' || k === 'dados_novos')) {
            Object.keys(r[k]).forEach(function (kk) { var v = r[k][kk]; if (typeof v === 'string' && re.test(v) && v.length <= 19) r[k][kk] = somarDias(v, dias); });
          }
        });
      });
    });
    seed.gerado_em = hojeISO();
    return seed;
  }
  function salvar() { try { localStorage.setItem(CHAVE, JSON.stringify(db)); } catch (e) { console.warn('Não foi possível persistir no navegador', e); } }
  function carregar(forcar) {
    if (!forcar) {
      try { var s = localStorage.getItem(CHAVE); if (s) { db = JSON.parse(s); return Promise.resolve(db); } } catch (e) { /* segue */ }
    }
    return fetch('data/seed.json', { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (seed) {
      db = deslocar(seed);
      ['pergunta_dependencias', 'questionario_pergunta', 'questionario_municipio', 'preenchimento_respostas'].forEach(function (t) {
        db[t].forEach(function (r, i) { r.id = r.id || i + 1; });
      });
      salvar();
      return db;
    });
  }
  function t(nome) { return db[nome] || (db[nome] = []); }
  function porId(nome, id) { id = +id; return t(nome).find(function (r) { return r.id === id; }) || null; }
  function novoId(nome) { return t(nome).reduce(function (m, r) { return Math.max(m, r.id || 0); }, 0) + 1; }
  function inserir(nome, obj) { obj.id = novoId(nome); if (!obj.created_at) obj.created_at = agoraISO(); t(nome).push(obj); return obj; }
  function remover(nome, pred) { db[nome] = t(nome).filter(function (r) { return !pred(r); }); }

  /* ---------------- regras: questionário ---------------- */
  var SIT = { rascunho: 'Rascunho', agendado: 'Publicado (aguardando início)', aberto: 'Em preenchimento', assinatura: 'Período de assinatura', encerrado: 'Encerrado' };
  function situacao(q) {
    var h = hojeISO();
    if (q.status === 'rascunho') return 'rascunho';
    if (q.status === 'encerrado') return 'encerrado';
    if (h < q.data_inicio.slice(0, 10)) return 'agendado';
    if (h <= q.data_fim.slice(0, 10)) return 'aberto';
    if (h <= q.data_limite_assinatura.slice(0, 10)) return 'assinatura';
    return 'encerrado';
  }
  function publicados() { return t('questionarios').filter(function (q) { return q.status !== 'rascunho'; }).sort(function (a, b) { return a.data_inicio < b.data_inicio ? 1 : -1; }); }
  function municipiosAlvo(q) {
    if (q.abrangencia === 'todos') return t('municipios').slice();
    var ids = t('questionario_municipio').filter(function (x) { return x.questionario_id === q.id; }).map(function (x) { return x.municipio_id; });
    return t('municipios').filter(function (m) { return ids.indexOf(m.id) >= 0; });
  }
  function abrange(q, munId) { return q.abrangencia === 'todos' || t('questionario_municipio').some(function (x) { return x.questionario_id === q.id && x.municipio_id === munId; }); }

  function opcoesDe(pid) { return t('opcoes_resposta').filter(function (o) { return o.pergunta_id === pid; }).sort(function (a, b) { return a.ordem - b.ordem || a.id - b.id; }); }
  function habilitadoras(pid) { return t('pergunta_dependencias').filter(function (d) { return d.pergunta_id === pid; }).map(function (d) { return d.opcao_resposta_id; }); }
  function temOpcoes(p) { return p.tipo === 'unica' || p.tipo === 'multipla'; }
  function perguntasDo(qid) {
    return t('questionario_pergunta').filter(function (x) { return x.questionario_id === +qid; }).sort(function (a, b) { return a.ordem - b.ordem; })
      .map(function (x) { return porId('perguntas', x.pergunta_id); }).filter(Boolean);
  }
  function pontMax(p) {
    if (!temOpcoes(p)) return 0;
    var ops = opcoesDe(p.id);
    if (!ops.length) return 0;
    return p.tipo === 'unica' ? Math.max.apply(null, ops.map(function (o) { return o.pontuacao; }))
      : ops.filter(function (o) { return o.pontuacao > 0; }).reduce(function (s, o) { return s + o.pontuacao; }, 0);
  }
  function pontPergunta(p, ids) {
    if (!temOpcoes(p) || !ids || !ids.length) return 0;
    var soma = opcoesDe(p.id).filter(function (o) { return ids.indexOf(o.id) >= 0; }).reduce(function (s, o) { return s + o.pontuacao; }, 0);
    if (p.tipo === 'multipla') soma = Math.min(soma, pontMax(p));
    return Math.round(Math.max(soma, 0) * p.peso * 100) / 100;
  }
  function habilitada(p, perguntas, mapa, prof) {
    prof = prof || 0;
    if (!p.pergunta_pai_id || prof > 10) return true;
    var pai = perguntas.find(function (x) { return x.id === p.pergunta_pai_id; });
    if (!pai) return true;
    if (!habilitada(pai, perguntas, mapa, prof + 1)) return false;
    var marcadas = ((mapa[pai.id] || {}).opcoes_ids) || [];
    var hab = habilitadoras(p.id);
    return marcadas.some(function (id) { return hab.indexOf(id) >= 0; });
  }
  function respostasMapa(preId) {
    var m = {};
    t('preenchimento_respostas').forEach(function (r) { if (r.preenchimento_id === +preId) m[r.pergunta_id] = { opcoes_ids: r.opcoes_ids || null, valor_texto: r.valor_texto }; });
    return m;
  }
  function calcular(qid, mapa) {
    var ps = perguntasDo(qid), ob = 0, mx = 0, porTema = {};
    ps.forEach(function (p) {
      if (!temOpcoes(p) || !habilitada(p, ps, mapa)) return;
      var max = Math.round(pontMax(p) * p.peso * 100) / 100, pts = pontPergunta(p, (mapa[p.id] || {}).opcoes_ids || []);
      ob += pts; mx += max;
      porTema[p.tema_id] = porTema[p.tema_id] || { obtida: 0, maxima: 0 };
      porTema[p.tema_id].obtida += pts; porTema[p.tema_id].maxima += max;
    });
    var indices = {};
    Object.keys(porTema).forEach(function (k) { var x = porTema[k]; indices[k] = x.maxima > 0 ? Math.round(x.obtida / x.maxima * 10000) / 100 : null; });
    return { obtida: Math.round(ob * 100) / 100, maxima: Math.round(mx * 100) / 100, indice: mx > 0 ? Math.round(ob / mx * 10000) / 100 : null, por_tema: indices };
  }
  function recalcular(pre) {
    var r = calcular(pre.questionario_id, respostasMapa(pre.id));
    pre.pontuacao_obtida = r.obtida; pre.pontuacao_maxima = r.maxima; pre.indice_aderencia = r.indice; pre.indices_por_tema = r.por_tema;
  }
  function faixa(i) {
    if (i === null || i === undefined) return null;
    var f = db.config.faixas;
    return i <= f.baixa_ate ? 'baixa' : (i <= f.media_ate ? 'media' : 'alta');
  }
  var FAIXA_LABEL = { baixa: 'Baixa aderência', media: 'Média aderência', alta: 'Alta aderência' };

  function evidenciaExigida(p, r) {
    if (!r || ((!r.opcoes_ids || !r.opcoes_ids.length) && (r.valor_texto === null || r.valor_texto === undefined))) return false;
    if (p.exige_evidencia) return true;
    var m = r.opcoes_ids || [];
    return opcoesDe(p.id).some(function (o) { return m.indexOf(o.id) >= 0 && o.solicita_evidencia; });
  }
  function evidenciasDe(preId, pid) { return t('evidencias').filter(function (e) { return e.preenchimento_id === +preId && (pid === undefined || e.pergunta_id === pid); }); }
  function pendencias(pre) {
    var ps = perguntasDo(pre.questionario_id), mapa = respostasMapa(pre.id), out = [];
    ps.forEach(function (p) {
      if (!habilitada(p, ps, mapa)) return;
      var r = mapa[p.id], ok = r && ((r.opcoes_ids && r.opcoes_ids.length) || (r.valor_texto !== null && r.valor_texto !== undefined && r.valor_texto !== ''));
      if (p.obrigatoria && !ok) { out.push(p.codigo + ': resposta obrigatória não informada.'); return; }
      if (evidenciaExigida(p, r) && !evidenciasDe(pre.id, p.id).length) out.push(p.codigo + ': anexe o documento comprobatório (evidência).');
    });
    return out;
  }
  function editavel(pre) { var q = porId('questionarios', pre.questionario_id); return (pre.status === 'em_preenchimento' || pre.status === 'devolvido') && situacao(q) === 'aberto'; }
  function validavel(pre) { var q = porId('questionarios', pre.questionario_id), s = situacao(q); return pre.status === 'aguardando_validacao' && (s === 'aberto' || s === 'assinatura'); }

  /* ---------------- notificações / auditoria ---------------- */
  var USUARIO = null;
  function usuarioAtual() { return USUARIO; }
  function definirUsuario(u) { USUARIO = u; }
  function notificar(usuarios, tipo, titulo, msg, link, qid, unica) {
    var n = 0;
    usuarios.forEach(function (u) {
      if (!u.ativo) return;
      if (unica && qid && t('notificacoes').some(function (x) { return x.user_id === u.id && x.tipo === tipo && x.questionario_id === qid; })) return;
      inserir('notificacoes', { user_id: u.id, tipo: tipo, titulo: titulo, mensagem: msg, link: link || null, questionario_id: qid || null, lida_em: null, email_simulado: true });
      n++;
    });
    return n;
  }
  function destinatarios(q, perfis, munIds) {
    perfis = perfis || ['responsavel', 'gestor'];
    var alvo = municipiosAlvo(q).map(function (m) { return m.id; });
    return t('users').filter(function (u) {
      return u.ativo && perfis.indexOf(u.perfil) >= 0 && u.municipio_id && alvo.indexOf(u.municipio_id) >= 0 && (!munIds || munIds.indexOf(u.municipio_id) >= 0);
    });
  }
  function usuariosMunicipio(munId, perfil) { return t('users').filter(function (u) { return u.municipio_id === munId && u.perfil === perfil && u.ativo; }); }
  function auditar(acao, tipo, id, desc, antes, depois, just) {
    inserir('auditorias', { acao: acao, entidade_tipo: tipo, entidade_id: id, entidade_descricao: desc, user_nome: USUARIO ? USUARIO.name : 'Sistema (rotina automática)',
      justificativa: just || null, dados_anteriores: antes || null, dados_novos: depois || null, ip: '127.0.0.1 (navegador)' });
  }

  /* ---------------- ações de ciclo de vida ---------------- */
  function erro(msgs) { var e = new Error(Array.isArray(msgs) ? msgs.join(' ') : msgs); e.lista = Array.isArray(msgs) ? msgs : [msgs]; throw e; }
  function publicar(q) {
    if (q.status !== 'rascunho') erro('Somente questionários em rascunho podem ser publicados.');
    var ps = perguntasDo(q.id), es = [];
    if (!ps.length) es.push('Associe ao menos uma pergunta ao questionário.');
    var sem = ps.filter(function (p) { return temOpcoes(p) && !opcoesDe(p.id).length; }).map(function (p) { return p.codigo; });
    if (sem.length) es.push('Perguntas de escolha sem respostas cadastradas: ' + sem.join(', ') + '.');
    if (q.data_fim.slice(0, 10) < hojeISO()) es.push('A data de encerramento do preenchimento já passou.');
    if (q.abrangencia === 'selecionados' && !municipiosAlvo(q).length) es.push('Selecione ao menos um município.');
    if (es.length) erro(es);
    q.status = 'publicado'; q.publicado_em = agoraISO();
    auditar('publicacao', 'Questionario', q.id, q.titulo, { status: 'rascunho' }, { status: 'publicado', data_inicio: q.data_inicio, data_fim: q.data_fim,
      data_limite_assinatura: q.data_limite_assinatura, abrangencia: q.abrangencia, total_perguntas: ps.length });
    if (q.data_inicio.slice(0, 10) <= hojeISO()) notificarInicio(q);
    salvar();
  }
  function notificarInicio(q) {
    return notificar(destinatarios(q), 'inicio_preenchimento', 'Início do preenchimento: ' + q.titulo,
      'Está aberto o período de preenchimento do questionário "' + q.titulo + '", de ' + br(q.data_inicio) + ' a ' + br(q.data_fim) +
      '. A validação/assinatura pelo gestor deve ocorrer até ' + br(q.data_limite_assinatura) + '.', '#/municipio/questionarios', q.id, true);
  }
  function prorrogar(q, fim, ass, just) {
    if (q.status === 'rascunho') erro('Questionário em rascunho deve ser alterado pela edição.');
    if (fim <= q.data_fim.slice(0, 10)) erro('A nova data de encerramento deve ser posterior à atual (' + br(q.data_fim) + ').');
    if (fim < hojeISO()) erro('A nova data de encerramento não pode estar no passado.');
    if (ass < fim) erro('O prazo de assinatura deve ser igual ou posterior ao encerramento.');
    if (!just || just.length < 10) erro('Informe a justificativa (mínimo de 10 caracteres).');
    var antes = { status: q.status, data_fim: q.data_fim, data_limite_assinatura: q.data_limite_assinatura };
    q.data_fim = fim; q.data_limite_assinatura = ass; q.status = 'publicado';
    auditar('prorrogacao', 'Questionario', q.id, q.titulo, antes, { status: 'publicado', data_fim: fim, data_limite_assinatura: ass }, just);
    notificar(destinatarios(q), 'prorrogacao', 'Prazo prorrogado: ' + q.titulo, 'O prazo de preenchimento do questionário "' + q.titulo +
      '" foi prorrogado até ' + br(fim) + '. O prazo final para validação/assinatura passa a ser ' + br(ass) + '.', '#/municipio/questionarios', q.id);
    salvar();
  }
  function duplicar(q) {
    var n = JSON.parse(JSON.stringify(q));
    delete n.id; n.titulo = (q.titulo + ' (cópia)').slice(0, 200); n.status = 'rascunho'; n.publicado_em = null; n.origem_id = q.id; n.created_at = null;
    inserir('questionarios', n);
    t('questionario_pergunta').filter(function (x) { return x.questionario_id === q.id; }).forEach(function (x) { inserir('questionario_pergunta', { questionario_id: n.id, pergunta_id: x.pergunta_id, ordem: x.ordem }); });
    t('questionario_municipio').filter(function (x) { return x.questionario_id === q.id; }).forEach(function (x) { inserir('questionario_municipio', { questionario_id: n.id, municipio_id: x.municipio_id }); });
    salvar();
    return n;
  }
  function pendentesMunicipios(q) {
    var concl = t('preenchimentos').filter(function (p) { return p.questionario_id === q.id && (p.status === 'aguardando_validacao' || p.status === 'validado'); }).map(function (p) { return p.municipio_id; });
    return municipiosAlvo(q).filter(function (m) { return concl.indexOf(m.id) < 0; }).map(function (m) { return m.id; });
  }
  function processarMarcos() {
    var h = hojeISO(), r = { inicio: 0, lembrete: 0, encerramento: 0, encerrados: 0 }, diasL = db.config.prazos.dias_lembrete_encerramento;
    t('questionarios').filter(function (q) { return q.status === 'publicado'; }).forEach(function (q) {
      var ini = q.data_inicio.slice(0, 10), fim = q.data_fim.slice(0, 10), ass = q.data_limite_assinatura.slice(0, 10);
      if (ini <= h && fim >= h) r.inicio += notificarInicio(q);
      if (fim >= h && ini <= h && diffDias(h, fim) <= diasL) {
        r.lembrete += notificar(destinatarios(q, null, pendentesMunicipios(q)), 'lembrete_encerramento', 'Lembrete: preenchimento encerra em ' + br(fim),
          'O período de preenchimento do questionário "' + q.titulo + '" encerra em ' + br(fim) + '. Conclua o preenchimento e encaminhe para validação do gestor.', '#/municipio/questionarios', q.id, true);
      }
      if (fim < h) {
        r.encerramento += notificar(destinatarios(q), 'encerramento_preenchimento', 'Encerrado o preenchimento: ' + q.titulo,
          'O período de preenchimento do questionário "' + q.titulo + '" foi encerrado em ' + br(fim) + '.', '#/municipio/questionarios', q.id, true);
      }
      if (ass < h) { q.status = 'encerrado'; auditar('encerramento', 'Questionario', q.id, q.titulo, { status: 'publicado' }, { status: 'encerrado' }); r.encerrados++; }
    });
    salvar();
    return r;
  }

  /* ---------------- preenchimento ---------------- */
  function obterOuCriar(q, munId, u) {
    var p = t('preenchimentos').find(function (x) { return x.questionario_id === q.id && x.municipio_id === munId; });
    if (!p) p = inserir('preenchimentos', { questionario_id: q.id, municipio_id: munId, status: 'em_preenchimento', responsavel_id: u.id, iniciado_em: agoraISO(), indice_aderencia: null, indices_por_tema: null });
    salvar();
    return p;
  }
  function salvarRespostas(pre, dados) {
    if (!editavel(pre)) erro('Este questionário não está disponível para edição.');
    var ps = perguntasDo(pre.questionario_id), mapa = respostasMapa(pre.id), novas = {};
    ps.forEach(function (p) {
      if (!dados[p.id]) return;
      var d = dados[p.id], v;
      if (p.tipo === 'unica') v = { opcoes_ids: d.opcao ? [+d.opcao] : null, valor_texto: null };
      else if (p.tipo === 'multipla') v = { opcoes_ids: (d.opcoes || []).map(Number), valor_texto: null };
      else {
        var s = String(d.valor || '').trim();
        if (p.tipo === 'numero') { s = s.replace(',', '.'); if (s !== '' && isNaN(+s)) erro('Informe um número válido na pergunta ' + p.codigo + '.'); }
        v = { opcoes_ids: null, valor_texto: s === '' ? null : s };
      }
      if (v.opcoes_ids && !v.opcoes_ids.length) v.opcoes_ids = null;
      novas[p.id] = v; mapa[p.id] = v;
    });
    ps.forEach(function (p) {
      if (!habilitada(p, ps, mapa)) { remover('preenchimento_respostas', function (r) { return r.preenchimento_id === pre.id && r.pergunta_id === p.id; }); delete mapa[p.id]; return; }
      if (!novas[p.id]) return;
      var r = t('preenchimento_respostas').find(function (x) { return x.preenchimento_id === pre.id && x.pergunta_id === p.id; });
      if (!r) r = inserir('preenchimento_respostas', { preenchimento_id: pre.id, pergunta_id: p.id });
      r.opcoes_ids = novas[p.id].opcoes_ids; r.valor_texto = novas[p.id].valor_texto;
    });
    if (!pre.responsavel_id && USUARIO) pre.responsavel_id = USUARIO.id;
    recalcular(pre); salvar();
  }
  function finalizar(pre) {
    if (!editavel(pre)) erro('Este questionário não está disponível para finalização.');
    var pend = pendencias(pre);
    if (pend.length) erro(pend);
    pre.status = 'aguardando_validacao'; pre.finalizado_em = agoraISO(); pre.responsavel_id = USUARIO.id; pre.observacao_devolucao = null;
    recalcular(pre);
    var q = porId('questionarios', pre.questionario_id);
    notificar(usuariosMunicipio(pre.municipio_id, 'gestor'), 'aguardando_validacao', 'Questionário aguardando sua validação',
      'O responsável municipal ' + USUARIO.name + ' finalizou o preenchimento do questionário "' + q.titulo + '". Revise e valide até ' + br(q.data_limite_assinatura) + '.',
      '#/municipio/preenchimentos/' + pre.id, q.id);
    salvar();
  }
  function sha256(txt) {
    if (window.crypto && crypto.subtle) {
      return crypto.subtle.digest('SHA-256', new TextEncoder().encode(txt)).then(function (b) {
        return Array.prototype.map.call(new Uint8Array(b), function (x) { return ('0' + x.toString(16)).slice(-2); }).join('');
      });
    }
    return Promise.resolve('indisponivel');
  }
  function validar(pre) {
    if (!validavel(pre)) erro('Este questionário não está disponível para validação.');
    var q = porId('questionarios', pre.questionario_id), m = porId('municipios', pre.municipio_id);
    var linhas = ['questionario=' + q.id, 'municipio=' + m.codigo_ibge];
    t('preenchimento_respostas').filter(function (r) { return r.preenchimento_id === pre.id; }).sort(function (a, b) { return a.pergunta_id - b.pergunta_id; })
      .forEach(function (r) { linhas.push(r.pergunta_id + '=' + JSON.stringify(r.opcoes_ids) + '|' + (r.valor_texto || '')); });
    var conteudo = linhas.join('\n'), quando = agoraISO();
    return sha256(conteudo + '|' + USUARIO.id + '|' + quando).then(function (hash) {
      recalcular(pre);
      pre.status = 'validado'; pre.gestor_id = USUARIO.id; pre.validado_em = quando; pre.assinatura_metodo = 'eletronica_simples'; pre.assinatura_hash = hash;
      pre.assinatura_detalhes = 'Assinado eletronicamente por ' + USUARIO.name + ' em ' + brHora(quando) + ' via ' + (USUARIO.metodo || 'GOVBR').toUpperCase() + '. Hash SHA-256: ' + hash;
      auditar('validacao', 'Preenchimento', pre.id, m.nome + ' — ' + q.titulo, {}, { status: 'validado', assinatura_hash: hash, indice_aderencia: pre.indice_aderencia });
      notificar(usuariosMunicipio(pre.municipio_id, 'responsavel'), 'validacao', 'Questionário validado pelo gestor',
        'O questionário "' + q.titulo + '" foi validado e assinado por ' + USUARIO.name + '.', '#/municipio/preenchimentos/' + pre.id, q.id);
      salvar();
    });
  }
  function devolver(pre, motivo) {
    if (!validavel(pre)) erro('Este questionário não pode ser devolvido.');
    var q = porId('questionarios', pre.questionario_id);
    if (situacao(q) !== 'aberto') erro('O período de preenchimento já encerrou; não é possível devolver.');
    if (!motivo || motivo.length < 10) erro('Informe o motivo (mínimo de 10 caracteres).');
    pre.status = 'devolvido'; pre.observacao_devolucao = motivo; pre.finalizado_em = null;
    notificar(usuariosMunicipio(pre.municipio_id, 'responsavel'), 'devolucao', 'Questionário devolvido para ajustes',
      'O gestor ' + USUARIO.name + ' devolveu o questionário "' + q.titulo + '" para ajustes. Motivo: ' + motivo, '#/municipio/preenchimentos/' + pre.id + '/editar', q.id);
    salvar();
  }

  /* ---------------- consolidações (painel) ---------------- */
  var CONCL = ['aguardando_validacao', 'validado'];
  function linhas(q, f) {
    f = f || {};
    var pres = {};
    t('preenchimentos').forEach(function (p) { if (p.questionario_id === q.id) pres[p.municipio_id] = p; });
    var out = municipiosAlvo(q).filter(function (m) {
      return (!f.relatoria || m.relatoria_id === +f.relatoria) && (!f.regiao || m.regiao_imediata === f.regiao) && (!f.municipios || f.municipios.indexOf(m.id) >= 0);
    }).sort(function (a, b) { return a.nome.localeCompare(b.nome, 'pt'); }).map(function (m) {
      var p = pres[m.id], c = !!(p && CONCL.indexOf(p.status) >= 0), i = c ? p.indice_aderencia : null;
      return { municipio: m, preenchimento: p || null, status: p ? p.status : 'nao_iniciado', concluido: c, indice: i, faixa: faixa(i), por_tema: c ? (p.indices_por_tema || {}) : {} };
    });
    if (f.faixa) out = out.filter(function (l) { return l.faixa === f.faixa; });
    if (f.status) out = out.filter(function (l) { return l.status === f.status; });
    return out;
  }
  function media(vs) { vs = vs.filter(function (v) { return v !== null && v !== undefined; }); return vs.length ? Math.round(vs.reduce(function (s, v) { return s + v; }, 0) / vs.length * 10) / 10 : null; }
  function temasDo(q) {
    var ids = []; perguntasDo(q.id).forEach(function (p) { if (ids.indexOf(p.tema_id) < 0) ids.push(p.tema_id); });
    return t('temas').filter(function (x) { return ids.indexOf(x.id) >= 0; }).sort(function (a, b) { return a.ordem - b.ordem; });
  }

  window.SDGT = {
    carregar: carregar, salvar: salvar, db: function () { return db; }, t: t, porId: porId, inserir: inserir, remover: remover,
    hojeISO: hojeISO, agoraISO: agoraISO, somarDias: somarDias, somarDiasUteis: somarDiasUteis, diffDias: diffDias, br: br, brHora: brHora,
    SIT: SIT, situacao: situacao, publicados: publicados, municipiosAlvo: municipiosAlvo, abrange: abrange,
    opcoesDe: opcoesDe, habilitadoras: habilitadoras, temOpcoes: temOpcoes, perguntasDo: perguntasDo, pontMax: pontMax, habilitada: habilitada,
    respostasMapa: respostasMapa, calcular: calcular, recalcular: recalcular, faixa: faixa, FAIXA_LABEL: FAIXA_LABEL,
    evidenciaExigida: evidenciaExigida, evidenciasDe: evidenciasDe, pendencias: pendencias, editavel: editavel, validavel: validavel,
    usuarioAtual: usuarioAtual, definirUsuario: definirUsuario, notificar: notificar, auditar: auditar, erro: erro,
    publicar: publicar, prorrogar: prorrogar, duplicar: duplicar, processarMarcos: processarMarcos,
    obterOuCriar: obterOuCriar, salvarRespostas: salvarRespostas, finalizar: finalizar, validar: validar, devolver: devolver,
    linhas: linhas, media: media, temasDo: temasDo, CONCL: CONCL
  };
})();
