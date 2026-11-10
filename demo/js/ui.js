/* SDGT — protótipo: núcleo de interface (roteador, layout, componentes, login). */
(function () {
  'use strict';
  var S = window.SDGT;
  var rotas = [], flashMsg = null;
  var UI = window.UI = { acoes: {}, forms: {}, depois: [] };

  /* ---------- utilitários ---------- */
  UI.h = function (s) { return s === null || s === undefined ? '' : String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  UI.num = function (v, d) { return v === null || v === undefined ? '—' : Number(v).toLocaleString('pt-BR', { minimumFractionDigits: d === undefined ? 1 : d, maximumFractionDigits: d === undefined ? 1 : d }); };
  UI.ind = function (v) { return v === null || v === undefined ? '—' : UI.num(v) + '%'; };
  UI.limitar = function (s, n) { s = s || ''; return s.length > n ? s.slice(0, n - 1) + '…' : s; };
  UI.flash = function (tipo, msg) { flashMsg = { tipo: tipo, msg: msg }; };
  UI.ir = function (hash) { if (location.hash === hash) UI.render(); else location.hash = hash; };
  UI.q = function () { var i = location.hash.indexOf('?'), o = {}; if (i < 0) return o; new URLSearchParams(location.hash.slice(i + 1)).forEach(function (v, k) { o[k] = v; }); return o; };
  UI.qs = function (obj) { var p = new URLSearchParams(); Object.keys(obj).forEach(function (k) { if (obj[k] !== '' && obj[k] !== null && obj[k] !== undefined) p.set(k, obj[k]); }); var s = p.toString(); return s ? '?' + s : ''; };

  var IC = {
    painel: '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>',
    comparar: '<path d="M8 3v18M16 3v18M3 8h5M16 16h5M3 16h5M16 8h5"/>', relatoria: '<path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M9 21v-6h6v6"/>',
    evolucao: '<path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/>', relatorio: '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
    questionario: '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/>',
    pergunta: '<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>', tema: '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
    material: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
    auditoria: '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
    usuarios: '<circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-7 7-7s7 3 7 7"/>', municipio: '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
    sino: '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>', menu: '<path d="M3 6h18M3 12h18M3 18h18"/>',
    mais: '<path d="M12 5v14M5 12h14"/>', editar: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>', lixo: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
    olho: '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>', download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
    upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>', clip: '<path d="M21.4 11.1l-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>', alerta: '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
    check: '<path d="M20 6L9 17l-5-5"/>', copiar: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    publicar: '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/>', relogio: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>', cadeado: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    link: '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>', assinar: '<path d="M20 19H4M7 15l9.5-9.5a2.1 2.1 0 0 1 3 3L10 18H7z"/>',
    voltar: '<path d="M19 12H5M12 19l-7-7 7-7"/>', filtro: '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>', cert: '<rect x="3" y="4" width="18" height="12" rx="2"/><circle cx="12" cy="10" r="2.5"/><path d="M9 20l3-2 3 2v-4"/>'
  };
  UI.ic = function (n) { return '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true">' + (IC[n] || IC.info) + '</svg>'; };

  var MAPAS = {
    preenchimento: { nao_iniciado: ['cinza', 'Não iniciado'], em_preenchimento: ['azul', 'Em preenchimento'], aguardando_validacao: ['amarelo', 'Aguardando validação'], devolvido: ['vermelho', 'Devolvido para ajustes'], validado: ['verde', 'Validado'] },
    questionario: { rascunho: ['cinza', 'Rascunho'], agendado: ['azul', 'Publicado — aguardando início'], aberto: ['verde', 'Em preenchimento'], assinatura: ['amarelo', 'Período de assinatura'], encerrado: ['vermelho', 'Encerrado'] },
    faixa: { baixa: ['vermelho', 'Baixa aderência'], media: ['amarelo', 'Média aderência'], alta: ['verde', 'Alta aderência'], '': ['cinza', 'Sem índice'] }
  };
  UI.STATUS_PRE = { nao_iniciado: 'Não iniciado', em_preenchimento: 'Em preenchimento', aguardando_validacao: 'Aguardando validação do gestor', devolvido: 'Devolvido para ajustes', validado: 'Validado e assinado' };
  UI.etq = function (tipo, v) { var x = MAPAS[tipo][v || ''] || ['cinza', v || '—']; return '<span class="etiqueta ' + x[0] + '">' + UI.h(x[1]) + '</span>'; };
  UI.barra = function (v, cor) { return '<div class="barra-indice"><div class="progresso"><span style="width:' + (v || 0) + '%' + (cor ? ';background:var(--' + cor + ')' : '') + '"></span></div><b>' + UI.ind(v) + '</b></div>'; };
  UI.CORF = { baixa: 'vermelho', media: 'laranja', alta: 'verde' };
  UI.PERFIL = { administrador: 'Administrador do Sistema', auditor: 'Auditor / Técnico do Tribunal', responsavel: 'Responsável Municipal', gestor: 'Gestor Municipal' };
  UI.opts = function (lista, sel, vazio) {
    return (vazio !== undefined ? '<option value="">' + UI.h(vazio) + '</option>' : '') + lista.map(function (o) {
      var v = Array.isArray(o) ? o[0] : o, l = Array.isArray(o) ? o[1] : o;
      return '<option value="' + UI.h(v) + '"' + (String(v) === String(sel) ? ' selected' : '') + '>' + UI.h(l) + '</option>';
    }).join('');
  };
  UI.cab = function (titulo, sub, acoes, migalhas) {
    return (migalhas ? '<div class="migalhas">' + migalhas + '</div>' : '') + '<div class="cabecalho-pagina"><div><h1>' + UI.h(titulo) + '</h1>' + (sub ? '<div class="subtitulo">' + sub + '</div>' : '') + '</div>' + (acoes ? '<div class="acoes">' + acoes + '</div>' : '') + '</div>';
  };
  UI.tabela = function (cab, linhas, vazio) {
    return '<div class="tabela-wrap"><table class="tabela"><thead><tr>' + cab.map(function (c) { return '<th' + (c.cls ? ' class="' + c.cls + '"' : '') + '>' + (c.t !== undefined ? c.t : c) + '</th>'; }).join('') +
      '</tr></thead><tbody>' + (linhas.length ? linhas.join('') : '<tr><td colspan="' + cab.length + '" class="vazio">' + (vazio || 'Nenhum registro.') + '</td></tr>') + '</tbody></table></div>';
  };
  UI.pagina = function (lista, por, render) {
    var q = UI.q(), pag = Math.max(1, +(q.pag || 1)), total = lista.length, n = Math.ceil(total / por);
    var itens = lista.slice((pag - 1) * por, pag * por).map(render), nav = '';
    if (n > 1) {
      var base = location.hash.split('?')[0], link = function (p) { q.pag = p; return base + UI.qs(q); };
      nav = '<nav class="paginacao">' + (pag > 1 ? '<a href="' + link(pag - 1) + '">‹ Anterior</a>' : '<span class="desab">‹ Anterior</span>');
      for (var i = 1; i <= n; i++) { if (i === pag) nav += '<span class="atual">' + i + '</span>'; else if (Math.abs(i - pag) < 3 || i === 1 || i === n) nav += '<a href="' + link(i) + '">' + i + '</a>'; else if (Math.abs(i - pag) === 3) nav += '<span class="desab">…</span>'; }
      nav += (pag < n ? '<a href="' + link(pag + 1) + '">Próxima ›</a>' : '<span class="desab">Próxima ›</span>') + '<span class="info">' + ((pag - 1) * por + 1) + '–' + Math.min(pag * por, total) + ' de ' + total + '</span></nav>';
    }
    return { itens: itens, nav: nav };
  };
  UI.modal = function (id, titulo, corpo, rodape, form) {
    return '<dialog class="modal" id="' + id + '"><form data-form="' + form + '"><div class="modal-cab"><h3>' + titulo + '</h3><button class="btn btn-link" type="button" data-fechar-modal aria-label="Fechar">✕</button></div>' +
      '<div class="modal-corpo">' + corpo + '</div><div class="modal-rodape"><button class="btn btn-neutro" type="button" data-fechar-modal>Cancelar</button>' + rodape + '</div></form></dialog>';
  };
  UI.baixarBlob = function (conteudo, nome, tipo) {
    var b = conteudo instanceof Blob ? conteudo : new Blob([conteudo], { type: tipo || 'application/octet-stream' });
    var a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = nome; document.body.appendChild(a); a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  };
  UI.exportarXls = function (titulo, cab, linhas, nome) {
    var html = '<html><head><meta charset="utf-8"></head><body><table border="1"><tr><th colspan="' + cab.length + '">' + UI.h(titulo) + '</th></tr><tr>' +
      cab.map(function (c) { return '<th style="background:#0066B0;color:#fff">' + UI.h(c) + '</th>'; }).join('') + '</tr>' +
      linhas.map(function (l) { return '<tr>' + l.map(function (v) { return '<td>' + UI.h(v === null || v === undefined ? '' : v) + '</td>'; }).join('') + '</tr>'; }).join('') + '</table></body></html>';
    UI.baixarBlob(html, nome, 'application/vnd.ms-excel');
  };
  UI.grafico = function (id, cfg) { UI.depois.push(function () { var el = document.getElementById(id); if (el && window.Chart) new Chart(el, cfg); }); };
  UI.CORES = { azul: '#0066B0', azulClaro: '#70C3FF', verde: '#44A12B', vermelho: '#DC332E', amarelo: '#FFCA28', serie: ['#0066B0', '#44A12B', '#FF8F00', '#931D19', '#33AAFF', '#B39409'], faixa: ['#DC332E', '#FFCA28', '#44A12B'] };

  /* ---------- roteamento ---------- */
  UI.rota = function (padrao, fn, perfis) {
    var nomes = [], re = new RegExp('^' + padrao.replace(/:(\w+)/g, function (_, n) { nomes.push(n); return '([^/]+)'; }) + '$');
    rotas.push({ re: re, nomes: nomes, fn: fn, perfis: perfis });
  };
  UI.render = function () {
    var u = S.usuarioAtual(), caminho = (location.hash.replace(/^#/, '').split('?')[0]) || '/';
    var app = document.getElementById('app');
    UI.depois = [];
    if (!u && caminho !== '/login') { location.replace('#/login'); return; }
    if (caminho === '/' || (u && caminho === '/login')) { location.replace(u ? (u.perfil === 'administrador' || u.perfil === 'auditor' ? '#/painel' : '#/municipio/questionarios') : '#/login'); return; }
    var r = null, params = {};
    for (var i = 0; i < rotas.length; i++) { var m = caminho.match(rotas[i].re); if (m) { r = rotas[i]; r.nomes.forEach(function (n, k) { params[n] = decodeURIComponent(m[k + 1]); }); break; } }
    var html;
    try {
      if (!r) html = '<div class="card vazio"><h2>Página não encontrada</h2><a class="btn btn-primario" href="#/">Voltar ao início</a></div>';
      else if (r.perfis && r.perfis.indexOf(u.perfil) < 0) html = '<div class="card vazio"><h2>403 — Acesso não permitido</h2><p class="muted">Seu perfil não tem permissão para esta funcionalidade.</p><a class="btn btn-primario" href="#/">Voltar</a></div>';
      else html = r.fn(params, UI.q());
    } catch (e) { console.error(e); html = '<div class="alerta erro">' + UI.ic('alerta') + '<div>' + UI.h(e.message) + '</div></div>'; }
    if (caminho === '/login') { app.innerHTML = html; }
    else app.innerHTML = layout(html, caminho);
    document.body.classList.remove('menu-aberto');
    window.scrollTo(0, 0);
    UI.depois.forEach(function (f) { try { f(); } catch (e) { console.error(e); } });
    var titulo = document.querySelector('.conteudo h1, .login-form h2');
    document.title = (titulo ? titulo.textContent + ' · ' : '') + 'SDGT (demonstração)';
  };

  function layout(conteudo, caminho) {
    var u = S.usuarioAtual(), mun = u.municipio_id ? S.porId('municipios', u.municipio_id) : null;
    var nl = S.t('notificacoes').filter(function (n) { return n.user_id === u.id && !n.lida_em; }).length;
    function item(href, ic, txt, ativo) { return '<a href="' + href + '"' + (ativo ? ' class="ativo"' : '') + '>' + UI.ic(ic) + ' ' + txt + '</a>'; }
    var menu = '', trib = u.perfil === 'administrador' || u.perfil === 'auditor';
    if (trib) {
      menu += '<div class="grupo">Painel corporativo</div>' + item('#/painel', 'painel', 'Panorama consolidado', caminho === '/painel') +
        item('#/painel/comparar', 'comparar', 'Comparar municípios', caminho === '/painel/comparar') + item('#/painel/relatorias', 'relatoria', 'Desempenho por relatoria', caminho === '/painel/relatorias') +
        item('#/painel/evolucao', 'evolucao', 'Evolução da adequação', caminho === '/painel/evolucao') + item('#/resultados', 'relatorio', 'Relatório de resultados', caminho === '/resultados');
    }
    if (u.perfil === 'administrador') {
      menu += '<div class="grupo">Diagnóstico</div>' + item('#/admin/questionarios', 'questionario', 'Questionários', caminho.indexOf('/admin/questionarios') === 0) +
        item('#/admin/perguntas', 'pergunta', 'Perguntas e respostas', caminho.indexOf('/admin/perguntas') === 0) + item('#/admin/temas', 'tema', 'Temas de avaliação', caminho.indexOf('/admin/temas') === 0) +
        '<div class="grupo">Administração</div>' + item('#/admin/auditoria', 'auditoria', 'Auditoria', caminho.indexOf('/admin/auditoria') === 0) +
        item('#/admin/usuarios', 'usuarios', 'Usuários', caminho.indexOf('/admin/usuarios') === 0) + item('#/admin/municipios', 'municipio', 'Municípios e relatorias', caminho === '/admin/municipios') +
        item('#/admin/rotina', 'relogio', 'Rotina diária (simular)', caminho === '/admin/rotina');
    }
    if (!trib) menu += '<div class="grupo">' + UI.h(mun ? mun.nome : '') + '</div>' + item('#/municipio/questionarios', 'questionario', 'Meus questionários', caminho.indexOf('/municipio') === 0);
    menu += '<div class="grupo">Geral</div>' + item('#/materiais', 'material', 'Materiais de apoio', caminho.indexOf('materiais') >= 0) +
      '<a href="#/notificacoes"' + (caminho.indexOf('/notificacoes') === 0 ? ' class="ativo"' : '') + '>' + UI.ic('sino') + ' Notificações' + (nl ? '<span class="badge-menu">' + nl + '</span>' : '') + '</a>' +
      item('#/sobre', 'info', 'Sobre esta demonstração', caminho === '/sobre');
    var f = flashMsg; flashMsg = null;
    var alerta = f ? '<div class="alerta ' + f.tipo + '" role="status">' + UI.ic(f.tipo === 'sucesso' ? 'check' : 'alerta') + '<div>' + (Array.isArray(f.msg) ? '<strong>Verifique:</strong><ul>' + f.msg.map(function (m) { return '<li>' + UI.h(m) + '</li>'; }).join('') + '</ul>' : UI.h(f.msg)) + '</div></div>' : '';
    var org = S.db().config.orgao;
    return '<div class="faixa-demo">DEMONSTRAÇÃO — protótipo navegável com dados fictícios salvos apenas neste navegador. Integrações simuladas. <a href="#/sobre">Saiba mais</a></div>' +
      '<header class="topo"><button class="acao-topo btn-menu" data-menu aria-label="Abrir menu">' + UI.ic('menu') + '</button>' +
      '<a href="#/" class="marca"><span class="brasao">TCETO</span><span class="orgao">Tribunal de Contas<br>do Estado do Tocantins</span><span class="sistema">' + UI.h(org.sistema) + '</span></a><span class="espaco"></span>' +
      '<a href="#/notificacoes" class="acao-topo" aria-label="Notificações">' + UI.ic('sino') + (nl ? '<span class="contador">' + (nl > 99 ? '99+' : nl) + '</span>' : '') + '</a>' +
      '<div class="usuario"><strong>' + UI.h(u.name) + '</strong><span>' + UI.PERFIL[u.perfil] + (mun ? ' · ' + UI.h(mun.nome) : '') + '</span></div>' +
      '<button class="sair" data-acao="sair">Sair</button></header>' +
      '<div class="estrutura"><nav class="lateral" aria-label="Menu principal">' + menu + '</nav><main class="conteudo" id="conteudo">' + alerta + conteudo +
      '<div class="rodape">' + UI.h(org.nome) + ' · SDGT — protótipo de demonstração</div></main></div>';
  }

  /* ---------- eventos globais ---------- */
  function lerForm(form) {
    var o = {};
    new FormData(form).forEach(function (v, k) {
      var partes = k.replace(/\]/g, '').split('['), alvo = o, arr = k.slice(-2) === '[]';
      if (arr) partes.pop();
      partes.forEach(function (p, i) {
        if (i === partes.length - 1) { if (arr) { (alvo[p] = alvo[p] || []).push(v); } else alvo[p] = v; }
        else alvo = (alvo[p] = alvo[p] || {});
      });
    });
    form.querySelectorAll('input[type=checkbox]:not([name$="[]"])').forEach(function (c) { if (!c.checked && !(c.name in o)) o[c.name] = ''; });
    return o;
  }
  UI.lerForm = lerForm;
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-acao]');
    if (a) {
      e.preventDefault();
      var conf = a.getAttribute('data-confirm');
      if (conf && !window.confirm(conf)) return;
      try { UI.acoes[a.getAttribute('data-acao')](a, e); } catch (err) { UI.flash('erro', err.lista || err.message); UI.render(); }
      return;
    }
    var m = e.target.closest('[data-abrir-modal]');
    if (m) { e.preventDefault(); var d = document.getElementById(m.getAttribute('data-abrir-modal')); if (d) d.showModal(); return; }
    var c = e.target.closest('[data-fechar-modal]');
    if (c) { e.preventDefault(); c.closest('dialog').close(); return; }
    if (e.target.closest('[data-menu]')) { document.body.classList.toggle('menu-aberto'); return; }
    if (document.body.classList.contains('menu-aberto') && !e.target.closest('.lateral')) document.body.classList.remove('menu-aberto');
  });
  document.addEventListener('submit', function (e) {
    var f = e.target, nome = f.getAttribute('data-form');
    if (!nome) return;
    e.preventDefault();
    var conf = f.getAttribute('data-confirm');
    if (conf && !window.confirm(conf)) return;
    var dados = lerForm(f);
    if (e.submitter && e.submitter.name) dados[e.submitter.name] = e.submitter.value;
    try {
      var r = UI.forms[nome](dados, f);
      if (r && r.then) r.catch(function (err) { UI.flash('erro', err.lista || err.message); UI.render(); });
    } catch (err) { var dl = f.closest('dialog'); if (dl) dl.close(); UI.flash('erro', err.lista || err.message); UI.render(); }
  });
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches('[data-marcar-todos]')) document.querySelectorAll(t.getAttribute('data-marcar-todos')).forEach(function (c) { c.checked = t.checked; });
    if (t.matches('[data-abrangencia]')) { var b = document.getElementById('box-municipios'); if (b) b.hidden = t.value !== 'selecionados'; }
    if (t.matches('[data-auto]')) { var f = t.closest('form'); if (f) f.requestSubmit(); }
  });

  /* Filtros (GET) → hash */
  UI.forms.filtro = function (d, f) { delete d.pag; UI.ir(f.getAttribute('data-destino') + UI.qs(d)); };
  UI.acoes.sair = function () { localStorage.removeItem('sdgt-demo-usuario'); S.definirUsuario(null); UI.ir('#/login'); };

  /* ---------- login ---------- */
  function entrar(u, metodo) {
    u.metodo = metodo; u.ultimo_acesso_em = S.agoraISO(); u.ultimo_metodo_login = metodo; S.salvar();
    S.definirUsuario(u); localStorage.setItem('sdgt-demo-usuario', String(u.id));
    UI.ir(u.perfil === 'administrador' || u.perfil === 'auditor' ? '#/painel' : '#/municipio/questionarios');
  }
  UI.rota('/login', function () {
    var us = S.t('users').filter(function (u) { return u.ativo; }), f = flashMsg; flashMsg = null;
    var munis = us.filter(function (u) { return u.municipio_id; });
    var opt = function (lista) { return lista.map(function (u) { var m = u.municipio_id ? S.porId('municipios', u.municipio_id) : null; return '<option value="' + u.id + '">' + UI.h(u.name) + (m ? ' (' + UI.h(m.nome) + ')' : '') + '</option>'; }).join(''); };
    var grupos = ['administrador', 'auditor', 'gestor', 'responsavel'].map(function (p) { var l = us.filter(function (u) { return u.perfil === p; }); return l.length ? '<optgroup label="' + UI.PERFIL[p] + '">' + opt(l) + '</optgroup>' : ''; }).join('');
    return '<div class="faixa-demo">DEMONSTRAÇÃO — dados fictícios armazenados apenas neste navegador · integrações GOV.BR, certificado, CADUN e e-mail simuladas</div>' +
      '<div class="pagina-login"><section class="login-arte"><div><div style="font-weight:700;opacity:.9">Tribunal de Contas do Estado do Tocantins</div></div><div>' +
      '<h1>Sistema de Diagnóstico da Gestão Tributária Municipal</h1><p>Acompanhamento da adequação dos municípios jurisdicionados às exigências da Reforma Tributária, por meio de questionários eletrônicos, evidências e indicadores.</p>' +
      '<div class="lista-dimensoes"><span>Legislação Tributária</span><span>Recursos Humanos</span><span>Infraestrutura e TI</span><span>Fiscalização</span><span>Cobrança e Contabilização</span><span>Governança da Transição</span></div></div>' +
      '<small style="opacity:.8">EC nº 132/2023 · LC nº 214/2025</small></section><section class="login-form"><h2>Acesse o sistema</h2>' +
      (f ? '<div class="alerta ' + f.tipo + '">' + UI.ic('alerta') + '<div>' + UI.h(f.msg) + '</div></div>' : '') +
      '<p class="muted" style="margin-bottom:8px"><strong>Municípios</strong> (responsável e gestor municipal):</p>' +
      '<button class="btn btn-govbr" data-abrir-modal="modal-govbr">Entrar com <strong>gov.br</strong></button><div style="height:10px"></div>' +
      '<button class="btn btn-secundario btn-cert" data-abrir-modal="modal-cert">' + UI.ic('cert') + ' Entrar com Certificado Digital ICP-Brasil</button>' +
      '<div class="separador">Tribunal — usuário e senha</div><form data-form="loginSenha" class="form-grade" style="gap:12px">' +
      '<div class="campo"><label for="usuario">Usuário (e-mail ou CPF)</label><input id="usuario" name="usuario" type="text" value="admin@tceto.tc.br" autocomplete="username" required></div>' +
      '<div class="campo"><label for="senha">Senha</label><input id="senha" name="senha" type="password" autocomplete="current-password" required></div>' +
      '<div class="campo"><button class="btn btn-primario" type="submit">Entrar</button><span class="ajuda-campo">Demonstração: admin@tceto.tc.br ou auditor@tceto.tc.br · senha <b>Sdgt@2026</b></span></div></form>' +
      '<div class="caixa-simulado"><strong>' + UI.ic('alerta') + ' Acesso simulado (homologação)</strong><p class="texto-pequeno muted" style="margin:6px 0 10px">Escolha qualquer usuário de teste para entrar diretamente.</p>' +
      '<form data-form="loginSimulado" class="filtros"><div class="campo"><select name="user_id" aria-label="Usuário de teste">' + grupos + '</select></div><button class="btn btn-neutro" type="submit">Entrar como</button></form></div>' +
      '</section></div>' +
      UI.modal('modal-govbr', 'gov.br (simulado)', '<p class="muted">Na versão real, você é redirecionado ao Login Único gov.br e retorna autenticado pelo CPF. Selecione o cidadão:</p><select name="user_id">' + opt(munis) + '</select>', '<button class="btn btn-govbr" style="width:auto" type="submit">Autorizar</button>', 'loginGovbr') +
      UI.modal('modal-cert', 'Certificado Digital (simulado)', '<p class="muted">Na versão real, o navegador apresenta o certificado e-CPF ICP-Brasil (TLS mútuo). Selecione o certificado:</p><select name="user_id">' + munis.map(function (u) { return '<option value="' + u.id + '">' + UI.h(u.name.toUpperCase()) + ':' + u.cpf + '</option>'; }).join('') + '</select>', '<button class="btn btn-primario" type="submit">Usar certificado</button>', 'loginCert');
  });
  UI.forms.loginSenha = function (d) {
    var cpf = d.usuario.replace(/\D/g, '');
    var u = S.t('users').find(function (x) { return x.email === d.usuario.trim() || (cpf.length === 11 && x.cpf === cpf); });
    if (!u || d.senha !== 'Sdgt@2026') { UI.flash('erro', 'Usuário ou senha inválidos.'); return UI.render(); }
    if (u.perfil === 'responsavel' || u.perfil === 'gestor') { UI.flash('erro', 'Usuários municipais devem acessar via GOV.BR ou Certificado Digital.'); return UI.render(); }
    entrar(u, 'senha');
  };
  UI.forms.loginSimulado = function (d) { var u = S.porId('users', d.user_id); entrar(u, u.municipio_id ? 'govbr' : 'senha'); };
  UI.forms.loginGovbr = function (d) { entrar(S.porId('users', d.user_id), 'govbr'); };
  UI.forms.loginCert = function (d) { entrar(S.porId('users', d.user_id), 'certificado'); };

  /* ---------- inicialização ---------- */
  UI.iniciar = function () {
    S.carregar().then(function () {
      var id = localStorage.getItem('sdgt-demo-usuario');
      if (id) { var u = S.porId('users', id); if (u) S.definirUsuario(u); }
      window.addEventListener('hashchange', UI.render);
      UI.render();
    }).catch(function (e) {
      document.getElementById('app').innerHTML = '<div class="conteudo"><div class="alerta erro">' + UI.ic('alerta') + '<div>Não foi possível carregar a base de demonstração (data/seed.json). ' + UI.h(e.message) + '</div></div></div>';
    });
  };
})();
