/* SDGT — protótipo: componentes compartilhados + Painel Corporativo (EP07) e Relatórios (EP08). */
(function () {
  'use strict';
  var S = window.SDGT, UI = window.UI, h = UI.h, TRIB = ['administrador', 'auditor'];

  /* ---------- componentes compartilhados ---------- */
  UI.estrutura = function (qid, pre) {
    var ps = S.perguntasDo(qid), mapa = pre ? S.respostasMapa(pre.id) : {}, grupos = [], idx = {};
    ps.forEach(function (p) {
      if (!(p.tema_id in idx)) { idx[p.tema_id] = grupos.length; grupos.push({ tema: S.porId('temas', p.tema_id), itens: [] }); }
      var r = mapa[p.id], ops = S.opcoesDe(p.id), marc = (r && r.opcoes_ids) || [];
      grupos[idx[p.tema_id]].itens.push({
        p: p, ops: ops, hab: S.habilitada(p, ps, mapa), marc: marc, valor: r ? r.valor_texto : null,
        texto: S.temOpcoes(p) ? ops.filter(function (o) { return marc.indexOf(o.id) >= 0; }).map(function (o) { return o.texto; }).join('; ') : (p.tipo === 'data' && r && r.valor_texto ? S.br(r.valor_texto) : (r ? r.valor_texto : null)),
        evs: pre ? S.evidenciasDe(pre.id, p.id) : [], exigida: S.evidenciaExigida(p, r), pai: p.pergunta_pai_id ? S.porId('perguntas', p.pergunta_pai_id) : null,
        habTxt: S.habilitadoras(p.id).map(function (id) { var o = S.porId('opcoes_resposta', id); return o ? o.texto : ''; })
      });
    });
    return grupos;
  };
  UI.questoesLeitura = function (qid, pre) {
    return UI.estrutura(qid, pre).map(function (g) {
      return '<section class="tema-bloco"><h2>' + h(g.tema.nome) + '</h2><div class="perguntas">' + g.itens.map(function (i) {
        var p = i.p, out = '<div class="pergunta' + (pre && !i.hab ? ' desabilitada' : '') + '" id="pergunta-' + p.id + '"><div class="enunciado"><span class="codigo">' + h(p.codigo) + '</span><span>' + h(p.enunciado) + (p.obrigatoria ? '<span class="obrigatorio"></span>' : '') + '</span></div>';
        if (i.pai) out += '<div class="condicao">' + UI.ic('info') + ' Condicionada à pergunta ' + h(i.pai.codigo) + ' (' + h(i.habTxt.join(' / ')) + ')' + (pre && !i.hab ? ' — não se aplica a este município' : '') + '</div>';
        if (pre) {
          if (i.hab) {
            out += '<div class="resposta-lida">' + h(i.texto || 'Não respondida') + '</div>';
            if (i.evs.length || i.exigida) out += '<div class="evidencias"><h4>' + UI.ic('clip') + ' Documentos comprobatórios</h4><ul class="lista-arquivos">' +
              (i.evs.length ? i.evs.map(function (e) { return '<li><a href="#" data-acao="baixarEvidencia" data-id="' + e.id + '">' + UI.ic('download') + ' ' + h(e.nome_original) + '</a> <span class="muted texto-pequeno">' + Math.max(1, Math.round((e.tamanho || 0) / 1024)) + ' KB</span></li>'; }).join('') : '<li class="faixa-baixa">Evidência exigida não anexada.</li>') + '</ul></div>';
          }
        } else if (S.temOpcoes(p)) {
          out += '<ul class="texto-pequeno" style="margin:0;padding-left:18px">' + i.ops.map(function (o) { return '<li>' + h(o.texto) + ' <span class="muted">(' + UI.num(o.pontuacao) + ' pts' + (o.solicita_evidencia ? ' · solicita evidência' : '') + ')</span></li>'; }).join('') + '</ul>';
        } else out += '<span class="muted texto-pequeno">Resposta: ' + ({ texto: 'Texto livre', numero: 'Numérica', data: 'Data' }[p.tipo]) + '</span>';
        return out + '</div>';
      }).join('') + '</div></section>';
    }).join('');
  };
  UI.resumoPreenchimento = function (pre) {
    var m = S.porId('municipios', pre.municipio_id), r = pre.responsavel_id ? S.porId('users', pre.responsavel_id) : null, g = pre.gestor_id ? S.porId('users', pre.gestor_id) : null;
    var f = S.faixa(pre.indice_aderencia), temas = UI.estrutura(pre.questionario_id, null);
    return '<div class="grade grade-2"><div class="card"><h3>Dados do preenchimento</h3><dl class="dl"><dt>Município</dt><dd>' + h(m.nome) + ' (' + m.codigo_ibge + ')</dd>' +
      '<dt>Situação</dt><dd>' + UI.etq('preenchimento', pre.status) + '</dd><dt>Responsável</dt><dd>' + h(r ? r.name : '—') + '</dd><dt>Finalizado em</dt><dd>' + S.brHora(pre.finalizado_em) + '</dd>' +
      '<dt>Gestor (validação)</dt><dd>' + h(g ? g.name : '—') + '</dd><dt>Validado em</dt><dd>' + S.brHora(pre.validado_em) + '</dd>' +
      (pre.status === 'devolvido' && pre.observacao_devolucao ? '<dt>Motivo da devolução</dt><dd class="faixa-baixa">' + h(pre.observacao_devolucao) + '</dd>' : '') + '</dl>' +
      (pre.assinatura_detalhes ? '<div class="alerta info texto-pequeno" style="margin:12px 0 0">' + UI.ic('assinar') + '<div style="word-break:break-all">' + h(pre.assinatura_detalhes) + '</div></div>' : '') + '</div>' +
      '<div class="card"><div class="card-titulo"><h3>Índice de aderência</h3>' + UI.etq('faixa', f) + '</div><div style="font-size:var(--fs-xxl);font-weight:800" class="faixa-' + f + '">' + UI.ind(pre.indice_aderencia) + '</div>' +
      '<p class="muted texto-pequeno">Perguntas não aplicáveis (condicionais) são desconsideradas no cálculo.</p><div class="indice-temas">' +
      temas.map(function (t) { var v = (pre.indices_por_tema || {})[t.tema.id]; return v === undefined || v === null ? '' : '<div class="linha"><span>' + h(t.tema.nome) + '</span>' + UI.barra(v) + '</div>'; }).join('') + '</div></div></div>';
  };
  UI.acoes.baixarEvidencia = function (a) {
    var e = S.porId('evidencias', a.getAttribute('data-id'));
    if (e.conteudo) { fetch(e.conteudo).then(function (r) { return r.blob(); }).then(function (b) { UI.baixarBlob(b, e.nome_original); }); }
    else UI.baixarBlob('Documento comprobatório de demonstração (' + e.nome_original + ').\nNa versão real o arquivo enviado pelo município é armazenado no repositório de arquivos do Tribunal.', e.nome_original.replace(/\.\w+$/, '') + '.txt', 'text/plain');
  };
  UI.seletorQuestionario = function (q, destino, extra) {
    return '<form class="card filtros" data-form="filtro" data-destino="' + destino + '"><div class="campo"><label for="fq">Questionário / ciclo</label><select id="fq" name="q" data-auto>' +
      S.publicados().map(function (x) { return '<option value="' + x.id + '"' + (q && x.id === q.id ? ' selected' : '') + '>' + h(x.titulo) + ' — ' + S.SIT[S.situacao(x)] + '</option>'; }).join('') + '</select></div>' + (extra || '') + '</form>';
  };
  function qSel(query) { return query.q ? S.porId('questionarios', query.q) : S.publicados()[0]; }

  /* ---------- HU07.01 Panorama ---------- */
  UI.rota('/painel', function (p, query) {
    var q = qSel(query);
    if (!q) return UI.cab('Panorama consolidado') + '<div class="alerta info">' + UI.ic('info') + '<div>Nenhum questionário publicado.</div></div>';
    var L = S.linhas(q), C = L.filter(function (l) { return l.concluido; }), temas = S.temasDo(q);
    var idx = S.media(C.map(function (l) { return l.indice; })), fx = { baixa: 0, media: 0, alta: 0 };
    C.forEach(function (l) { fx[l.faixa]++; });
    var st = Object.keys(UI.STATUS_PRE).map(function (k) { return L.filter(function (l) { return l.status === k; }).length; });
    var porTema = temas.map(function (t) { return S.media(C.map(function (l) { return l.por_tema[t.id]; })); });
    var rank = function (lst) { return UI.tabela(['Município', 'Relatoria', 'Índice', ''], lst.map(function (l) { var r = S.porId('relatorias', l.municipio.relatoria_id); return '<tr><td>' + h(l.municipio.nome) + '</td><td class="muted">' + h(r ? r.nome : '') + '</td><td>' + UI.barra(l.indice, UI.CORF[l.faixa]) + '</td><td><a class="btn btn-link btn-pequeno" href="#/tribunal/preenchimento/' + l.preenchimento.id + '">Ver</a></td></tr>'; }), 'Sem questionários concluídos.'); };
    var ord = C.slice().sort(function (a, b) { return a.indice - b.indice; });
    var regioes = {};
    L.forEach(function (l) { var k = l.municipio.regiao_imediata || '—'; (regioes[k] = regioes[k] || []).push(l); });
    UI.grafico('gStatus', { type: 'bar', data: { labels: Object.values(UI.STATUS_PRE), datasets: [{ data: st, backgroundColor: ['#D3D3D3', UI.CORES.azulClaro, UI.CORES.amarelo, '#E77773', UI.CORES.verde] }] }, options: { maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } } });
    UI.grafico('gFaixas', { type: 'doughnut', data: { labels: ['Baixa', 'Média', 'Alta'], datasets: [{ data: [fx.baixa, fx.media, fx.alta], backgroundColor: UI.CORES.faixa }] }, options: { maintainAspectRatio: false, plugins: { legend: { position: 'right' } } } });
    UI.grafico('gTemas', { type: 'bar', data: { labels: temas.map(function (t) { return t.nome; }), datasets: [{ label: 'Índice médio (%)', data: porTema, backgroundColor: UI.CORES.azul, borderRadius: 4 }] }, options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } } });
    var fxb = S.db().config.faixas;
    return UI.cab('Panorama consolidado', 'Estágio de preparação dos municípios para a Reforma Tributária.', '<a class="btn btn-neutro" href="#/resultados?q=' + q.id + '">' + UI.ic('relatorio') + ' Resultados detalhados</a>') +
      UI.seletorQuestionario(q, '#/painel') +
      '<div class="grade grade-4" style="margin-bottom:20px"><div class="kpi"><div class="rotulo">Municípios abrangidos</div><div class="valor">' + L.length + '</div><div class="detalhe">' + S.SIT[S.situacao(q)] + '</div></div>' +
      '<div class="kpi verde"><div class="rotulo">Participação (finalizados)</div><div class="valor">' + UI.num(L.length ? C.length / L.length * 100 : 0) + '%</div><div class="detalhe">' + C.length + ' concluídos · ' + L.filter(function (l) { return l.status === 'validado'; }).length + ' validados</div></div>' +
      '<div class="kpi"><div class="rotulo">Índice médio de aderência</div><div class="valor">' + UI.ind(idx) + '</div><div class="detalhe">' + (S.FAIXA_LABEL[S.faixa(idx)] || 'Sem índice') + '</div></div>' +
      '<div class="kpi vermelho"><div class="rotulo">Baixa aderência</div><div class="valor">' + fx.baixa + '</div><div class="detalhe">municípios em risco</div></div></div>' +
      '<div class="grade grade-2"><div class="card"><div class="card-titulo"><h3>Situação do preenchimento</h3></div><div class="grafico"><canvas id="gStatus"></canvas></div></div>' +
      '<div class="card"><div class="card-titulo"><h3>Distribuição por faixa de aderência</h3><span class="legenda-faixas">Baixa ≤ ' + Math.round(fxb.baixa_ate) + '% · Média ≤ ' + Math.round(fxb.media_ate) + '%</span></div><div class="grafico"><canvas id="gFaixas"></canvas></div></div></div>' +
      '<div class="card"><div class="card-titulo"><h3>Índice médio por tema de avaliação</h3></div><div class="grafico"><canvas id="gTemas"></canvas></div></div>' +
      '<div class="grade grade-2"><div class="card"><h3>Menores índices (atenção prioritária)</h3>' + rank(ord.slice(0, 10)) + '</div><div class="card"><h3>Maiores índices</h3>' + rank(ord.slice(-10).reverse()) + '</div></div>' +
      '<div class="card"><h3>Por região imediata (IBGE)</h3>' + UI.tabela(['Região', { t: 'Municípios', cls: 'num' }, { t: 'Concluídos', cls: 'num' }, 'Índice médio'], Object.keys(regioes).sort().map(function (k) {
        var g = regioes[k], c = g.filter(function (l) { return l.concluido; });
        return '<tr><td>' + h(k) + '</td><td class="num">' + g.length + '</td><td class="num">' + c.length + '</td><td>' + UI.barra(S.media(c.map(function (l) { return l.indice; }))) + '</td></tr>';
      })) + '</div>';
  }, TRIB);

  /* ---------- HU07.02 Comparar ---------- */
  UI.rota('/painel/comparar', function (p, query) {
    var q = qSel(query), ids = (query.m || '').split(',').filter(Boolean).map(Number).slice(0, 6);
    var html = UI.cab('Comparar municípios', 'Compare até 6 municípios por tema de avaliação.') +
      '<form class="card" data-form="comparar"><div class="form-grade"><div class="campo c6"><label for="cq">Questionário / ciclo</label><select id="cq" name="q">' + UI.opts(S.publicados().map(function (x) { return [x.id, x.titulo]; }), q && q.id) + '</select></div>' +
      '<div class="campo c6"><label for="cm">Municípios (Ctrl/Cmd + clique; até 6)</label><select id="cm" name="m[]" multiple style="min-height:120px">' +
      S.t('municipios').map(function (m) { return '<option value="' + m.id + '"' + (ids.indexOf(m.id) >= 0 ? ' selected' : '') + '>' + h(m.nome) + '</option>'; }).join('') + '</select></div></div>' +
      '<div class="acoes" style="margin-top:12px"><button class="btn btn-primario" type="submit">' + UI.ic('comparar') + ' Comparar</button></div></form>';
    if (!q || !ids.length) return html + '<div class="alerta info">' + UI.ic('info') + '<div>Selecione o questionário e os municípios para comparar.</div></div>';
    var L = S.linhas(q, { municipios: ids }), temas = S.temasDo(q);
    UI.grafico('gComp', { type: 'bar', data: { labels: temas.map(function (t) { return t.nome; }), datasets: L.map(function (l, i) { return { label: l.municipio.nome, backgroundColor: UI.CORES.serie[i % 6], borderRadius: 3, data: temas.map(function (t) { return l.por_tema[t.id]; }) }; }) }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } } });
    return html + '<div class="card"><div class="grafico alto"><canvas id="gComp"></canvas></div></div>' +
      UI.tabela(['Tema'].concat(L.map(function (l) { return { t: h(l.municipio.nome), cls: 'num' }; })), [
        '<tr><td><strong>Situação</strong></td>' + L.map(function (l) { return '<td class="num">' + UI.etq('preenchimento', l.status) + '</td>'; }).join('') + '</tr>',
        '<tr><td><strong>Índice geral</strong></td>' + L.map(function (l) { return '<td class="num"><strong class="faixa-' + l.faixa + '">' + UI.ind(l.indice) + '</strong></td>'; }).join('') + '</tr>'
      ].concat(temas.map(function (t) { return '<tr><td>' + h(t.nome) + '</td>' + L.map(function (l) { return '<td class="num">' + UI.ind(l.por_tema[t.id]) + '</td>'; }).join('') + '</tr>'; }))
        .concat(['<tr><td></td>' + L.map(function (l) { return '<td class="num">' + (l.preenchimento ? '<a class="btn btn-link btn-pequeno" href="#/tribunal/preenchimento/' + l.preenchimento.id + '">Ver respostas</a>' : '') + '</td>'; }).join('') + '</tr>']));
  }, TRIB);
  UI.forms.comparar = function (d) { UI.ir('#/painel/comparar' + UI.qs({ q: d.q, m: (d.m || []).slice(0, 6).join(',') })); };

  /* ---------- HU07.03 Relatorias ---------- */
  UI.rota('/painel/relatorias', function (p, query) {
    var q = qSel(query); if (!q) return UI.cab('Desempenho por relatoria');
    var L = S.linhas(q);
    var R = S.t('relatorias').map(function (r) {
      var g = L.filter(function (l) { return l.municipio.relatoria_id === r.id; }), c = g.filter(function (l) { return l.concluido; });
      var cnt = function (f) { return c.filter(function (l) { return l.faixa === f; }).length; };
      return { r: r, total: g.length, c: c, part: g.length ? c.length / g.length * 100 : 0, ind: S.media(c.map(function (l) { return l.indice; })), b: cnt('baixa'), m: cnt('media'), a: cnt('alta'), piores: c.slice().sort(function (x, y) { return x.indice - y.indice; }).slice(0, 3) };
    }).filter(function (x) { return x.total; });
    UI.grafico('gRel', { type: 'bar', data: { labels: R.map(function (x) { return x.r.nome; }), datasets: [{ label: 'Baixa', data: R.map(function (x) { return x.b; }), backgroundColor: UI.CORES.faixa[0] }, { label: 'Média', data: R.map(function (x) { return x.m; }), backgroundColor: UI.CORES.faixa[1] }, { label: 'Alta', data: R.map(function (x) { return x.a; }), backgroundColor: UI.CORES.faixa[2] }] }, options: { maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } } });
    return UI.cab('Desempenho dos municípios por relatoria', 'Subsídio para ações preventivas e colaborativas de cada relatoria.') + UI.seletorQuestionario(q, '#/painel/relatorias') +
      '<div class="card"><div class="grafico"><canvas id="gRel"></canvas></div></div>' +
      UI.tabela(['Relatoria', { t: 'Municípios', cls: 'num' }, { t: 'Participação', cls: 'num' }, 'Índice médio', { t: 'Baixa', cls: 'num' }, { t: 'Média', cls: 'num' }, { t: 'Alta', cls: 'num' }, 'Menores índices', ''], R.map(function (x) {
        return '<tr><td><strong>' + h(x.r.nome) + '</strong></td><td class="num">' + x.total + '</td><td class="num">' + UI.num(x.part) + '%</td><td>' + UI.barra(x.ind) + '</td><td class="num faixa-baixa">' + x.b + '</td><td class="num faixa-media">' + x.m + '</td><td class="num faixa-alta">' + x.a + '</td>' +
          '<td class="texto-pequeno">' + x.piores.map(function (l) { return h(l.municipio.nome) + ' (' + UI.ind(l.indice) + ')'; }).join(', ') + '</td><td><a class="btn btn-link btn-pequeno" href="#/resultados?q=' + q.id + '&relatoria=' + x.r.id + '">Municípios ›</a></td></tr>';
      }));
  }, TRIB);

  /* ---------- HU07.04 Evolução ---------- */
  UI.rota('/painel/evolucao', function (p, query) {
    var munId = +query.m || null, mun = munId ? S.porId('municipios', munId) : null;
    var serie = S.publicados().slice().reverse().map(function (q) {
      var L = S.linhas(q), C = L.filter(function (l) { return l.concluido; }), pct = function (f) { return C.length ? Math.round(C.filter(function (l) { return l.faixa === f; }).length / C.length * 1000) / 10 : 0; };
      var lm = munId ? L.find(function (l) { return l.municipio.id === munId; }) : null;
      return { q: q, part: L.length ? Math.round(C.length / L.length * 1000) / 10 : 0, ind: S.media(C.map(function (l) { return l.indice; })), b: pct('baixa'), m: pct('media'), a: pct('alta'), im: lm ? lm.indice : null };
    });
    var ds = [{ label: 'Média estadual', data: serie.map(function (s) { return s.ind; }), borderColor: UI.CORES.azul, backgroundColor: UI.CORES.azul, tension: .25 }, { label: 'Participação (%)', data: serie.map(function (s) { return s.part; }), borderColor: '#898989', borderDash: [5, 4], tension: .25 }];
    if (mun) ds.push({ label: mun.nome, data: serie.map(function (s) { return s.im; }), borderColor: UI.CORES.verde, backgroundColor: UI.CORES.verde, tension: .25 });
    UI.grafico('gInd', { type: 'line', data: { labels: serie.map(function (s) { return s.q.ciclo; }), datasets: ds }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } } });
    UI.grafico('gFx', { type: 'bar', data: { labels: serie.map(function (s) { return s.q.ciclo; }), datasets: [{ label: 'Baixa', data: serie.map(function (s) { return s.b; }), backgroundColor: UI.CORES.faixa[0] }, { label: 'Média', data: serie.map(function (s) { return s.m; }), backgroundColor: UI.CORES.faixa[1] }, { label: 'Alta', data: serie.map(function (s) { return s.a; }), backgroundColor: UI.CORES.faixa[2] }] }, options: { maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, max: 100 } } } });
    return UI.cab('Evolução da adequação municipal', 'Tendências ao longo dos ciclos de diagnóstico.') +
      '<form class="card filtros" data-form="filtro" data-destino="#/painel/evolucao"><div class="campo"><label for="em">Destacar município (opcional)</label><select id="em" name="m" data-auto>' + UI.opts(S.t('municipios').map(function (m) { return [m.id, m.nome]; }), munId, '— Média estadual —') + '</select></div></form>' +
      '<div class="grade grade-2"><div class="card"><h3>Índice médio de aderência</h3><div class="grafico"><canvas id="gInd"></canvas></div></div><div class="card"><h3>Distribuição por faixa (% dos concluídos)</h3><div class="grafico"><canvas id="gFx"></canvas></div></div></div>' +
      UI.tabela(['Ciclo', 'Questionário', { t: 'Participação', cls: 'num' }, { t: 'Índice médio', cls: 'num' }].concat(mun ? [{ t: h(mun.nome), cls: 'num' }] : []).concat([{ t: 'Baixa', cls: 'num' }, { t: 'Média', cls: 'num' }, { t: 'Alta', cls: 'num' }]), serie.map(function (s) {
        return '<tr><td>' + h(s.q.ciclo) + '</td><td>' + h(s.q.titulo) + '</td><td class="num">' + UI.num(s.part) + '%</td><td class="num"><strong>' + UI.ind(s.ind) + '</strong></td>' + (mun ? '<td class="num">' + UI.ind(s.im) + '</td>' : '') +
          '<td class="num faixa-baixa">' + UI.num(s.b) + '%</td><td class="num faixa-media">' + UI.num(s.m) + '%</td><td class="num faixa-alta">' + UI.num(s.a) + '%</td></tr>';
      }));
  }, TRIB);

  /* ---------- HU07.05 / HU07.06 ---------- */
  UI.rota('/tribunal/preenchimento/:id', function (p) {
    var pre = S.porId('preenchimentos', p.id), q = S.porId('questionarios', pre.questionario_id), m = S.porId('municipios', pre.municipio_id), r = S.porId('relatorias', m.relatoria_id);
    return UI.cab(m.nome, h(q.titulo) + ' · ' + h(r ? r.nome : ''), '<button class="btn btn-secundario" data-acao="imprimir">' + UI.ic('download') + ' Baixar PDF</button>',
      '<a href="#/painel?q=' + q.id + '">Painel</a> › <a href="#/resultados?q=' + q.id + '">Resultados</a> › ' + h(m.nome)) + UI.resumoPreenchimento(pre) + UI.questoesLeitura(q.id, pre);
  }, TRIB);
  UI.acoes.imprimir = function () { window.print(); };

  /* ---------- EP08 Resultados ---------- */
  function filtrosRes(query) { return { relatoria: query.relatoria || '', regiao: query.regiao || '', faixa: query.faixa || '', status: query.status || '' }; }
  UI.rota('/resultados', function (p, query) {
    var q = qSel(query); if (!q) return UI.cab('Resultados dos municípios');
    var f = filtrosRes(query), L = S.linhas(q, f), temas = S.temasDo(q);
    var regioes = []; S.t('municipios').forEach(function (m) { if (m.regiao_imediata && regioes.indexOf(m.regiao_imediata) < 0) regioes.push(m.regiao_imediata); }); regioes.sort();
    return UI.cab('Resultados dos municípios', 'Consulta com filtros e exportação em PDF e XLS.', '<button class="btn btn-neutro" data-acao="imprimir">' + UI.ic('download') + ' Exportar PDF</button><button class="btn btn-neutro" data-acao="exportarResultados">' + UI.ic('download') + ' Exportar XLS</button>') +
      '<form class="card filtros" data-form="filtro" data-destino="#/resultados"><div class="campo"><label for="rq">Questionário</label><select id="rq" name="q">' + UI.opts(S.publicados().map(function (x) { return [x.id, x.titulo]; }), q.id) + '</select></div>' +
      '<div class="campo curto"><label for="rr">Relatoria</label><select id="rr" name="relatoria">' + UI.opts(S.t('relatorias').map(function (r) { return [r.id, r.nome]; }), f.relatoria, 'Todas') + '</select></div>' +
      '<div class="campo curto"><label for="rg">Região</label><select id="rg" name="regiao">' + UI.opts(regioes, f.regiao, 'Todas') + '</select></div>' +
      '<div class="campo curto"><label for="rf">Faixa</label><select id="rf" name="faixa">' + UI.opts([['baixa', 'Baixa'], ['media', 'Média'], ['alta', 'Alta']], f.faixa, 'Todas') + '</select></div>' +
      '<div class="campo curto"><label for="rs">Situação</label><select id="rs" name="status">' + UI.opts(Object.keys(UI.STATUS_PRE).map(function (k) { return [k, UI.STATUS_PRE[k]]; }), f.status, 'Todas') + '</select></div>' +
      '<button class="btn btn-secundario" type="submit">' + UI.ic('filtro') + ' Consultar</button></form><p class="muted">' + L.length + ' município(s).</p>' +
      UI.tabela(['Município', 'Relatoria', 'Situação', 'Índice'].concat(temas.map(function (t) { return { t: '<span title="' + h(t.nome) + '">' + h(UI.limitar(t.nome, 14)) + '</span>', cls: 'num' }; })).concat(['']), L.map(function (l) {
        var r = S.porId('relatorias', l.municipio.relatoria_id);
        return '<tr><td><strong>' + h(l.municipio.nome) + '</strong><br><span class="muted texto-pequeno">' + h(l.municipio.regiao_imediata) + '</span></td><td>' + h(r ? r.nome : '') + '</td><td>' + UI.etq('preenchimento', l.status) + '</td>' +
          '<td><strong class="faixa-' + l.faixa + '">' + UI.ind(l.indice) + '</strong></td>' + temas.map(function (t) { return '<td class="num">' + UI.ind(l.por_tema[t.id]) + '</td>'; }).join('') +
          '<td>' + (l.preenchimento ? '<a class="btn btn-link btn-pequeno" href="#/tribunal/preenchimento/' + l.preenchimento.id + '">' + UI.ic('olho') + ' Ver</a>' : '') + '</td></tr>';
      }), 'Nenhum resultado para os filtros informados.');
  }, TRIB);
  UI.acoes.exportarResultados = function () {
    var query = UI.q(), q = qSel(query), L = S.linhas(q, filtrosRes(query)), temas = S.temasDo(q);
    UI.exportarXls('Resultados - ' + q.titulo, ['Código IBGE', 'Município', 'Relatoria', 'Região imediata', 'Situação', 'Índice (%)', 'Faixa'].concat(temas.map(function (t) { return t.nome + ' (%)'; })),
      L.map(function (l) { var r = S.porId('relatorias', l.municipio.relatoria_id); return [l.municipio.codigo_ibge, l.municipio.nome, r ? r.nome : '', l.municipio.regiao_imediata, UI.STATUS_PRE[l.status], l.indice, S.FAIXA_LABEL[l.faixa] || 'Sem índice'].concat(temas.map(function (t) { return l.por_tema[t.id]; })); }),
      'resultados-' + q.ciclo + '.xls');
  };
})();
