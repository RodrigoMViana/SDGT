/* SDGT — protótipo: Administração (EP02, EP03, EP04, EP11, EP12, usuários, municípios). */
(function () {
  'use strict';
  var S = window.SDGT, UI = window.UI, h = UI.h, ADM = ['administrador'];
  var TIPOS = { unica: 'Escolha única', multipla: 'Múltipla escolha', texto: 'Texto livre', numero: 'Numérica', data: 'Data' };
  var CATS = { cartilha: 'Cartilha', manual: 'Manual', normativo: 'Normativo / Portaria', outro: 'Outro' };
  var ACOES = { publicacao: 'Publicação de questionário', prorrogacao: 'Prorrogação de prazo', encerramento: 'Encerramento automático', validacao: 'Validação/assinatura de questionário' };
  function voltar(msg, destino) { UI.flash('sucesso', msg); UI.ir(destino); }
  function filtrar(lista, busca, campos) { if (!busca) return lista; busca = busca.toLowerCase(); return lista.filter(function (x) { return campos.some(function (c) { return String(x[c] || '').toLowerCase().indexOf(busca) >= 0; }); }); }
  function bloqueada(p) {
    return S.t('questionario_pergunta').some(function (x) { if (x.pergunta_id !== p.id) return false; var q = S.porId('questionarios', x.questionario_id); return q && q.status !== 'rascunho'; });
  }

  /* ===================== EP02 Temas ===================== */
  UI.rota('/admin/temas', function (p, q) {
    var lista = filtrar(S.t('temas'), q.busca, ['nome']).filter(function (t) { return !q.ativo || String(t.ativo ? 1 : 0) === q.ativo; }).sort(function (a, b) { return a.ordem - b.ordem; });
    return UI.cab('Temas de avaliação', 'Dimensões temáticas que organizam as perguntas dos questionários.', '<a href="#/admin/temas/novo" class="btn btn-primario">' + UI.ic('mais') + ' Cadastrar tema</a>') +
      '<form class="card filtros" data-form="filtro" data-destino="#/admin/temas"><div class="campo"><label for="tb">Buscar</label><input id="tb" type="search" name="busca" value="' + h(q.busca) + '" placeholder="Nome do tema"></div>' +
      '<div class="campo curto"><label for="ta">Situação</label><select id="ta" name="ativo">' + UI.opts([['1', 'Ativos'], ['0', 'Inativos']], q.ativo, 'Todas') + '</select></div><button class="btn btn-secundario">' + UI.ic('filtro') + ' Filtrar</button></form>' +
      UI.tabela([{ t: 'Ordem', cls: 'num' }, 'Tema', { t: 'Perguntas', cls: 'num' }, 'Situação', { t: 'Ações', cls: 'acoes-col' }], lista.map(function (t) {
        var n = S.t('perguntas').filter(function (p) { return p.tema_id === t.id; }).length;
        return '<tr><td class="num">' + t.ordem + '</td><td><strong>' + h(t.nome) + '</strong><br><span class="muted texto-pequeno">' + h(UI.limitar(t.descricao, 140)) + '</span></td><td class="num">' + n + '</td>' +
          '<td>' + (t.ativo ? '<span class="etiqueta verde">Ativo</span>' : '<span class="etiqueta cinza">Inativo</span>') + '</td><td class="acoes-col"><a class="btn btn-link" href="#/admin/temas/' + t.id + '/editar">' + UI.ic('editar') + ' Editar</a>' +
          '<button class="btn btn-link" data-acao="alternarTema" data-id="' + t.id + '" data-confirm="Deseja realmente ' + (t.ativo ? 'inativar' : 'ativar') + ' o tema?">' + (t.ativo ? 'Inativar' : 'Ativar') + '</button>' +
          '<button class="btn btn-link perigo" data-acao="excluirTema" data-id="' + t.id + '" data-confirm="Excluir o tema ' + h(t.nome) + '?">' + UI.ic('lixo') + ' Excluir</button></td></tr>';
      }), 'Nenhum tema encontrado.');
  }, ADM);
  function formTema(t) {
    return UI.cab(t.id ? 'Editar tema de avaliação' : 'Cadastrar tema de avaliação', null, null, '<a href="#/admin/temas">Temas de avaliação</a> › ' + (t.id ? 'Editar' : 'Cadastrar')) +
      '<form class="card" data-form="salvarTema"><input type="hidden" name="id" value="' + (t.id || '') + '"><div class="form-grade">' +
      '<div class="campo c8"><label for="tn" class="obrigatorio">Nome</label><input id="tn" name="nome" maxlength="150" value="' + h(t.nome) + '" required></div>' +
      '<div class="campo c4"><label for="to" class="obrigatorio">Ordem de exibição</label><input id="to" name="ordem" type="number" min="0" value="' + (t.ordem || 0) + '" required></div>' +
      '<div class="campo"><label for="td">Descrição</label><textarea id="td" name="descricao">' + h(t.descricao) + '</textarea></div>' +
      '<div class="campo"><label class="check"><input type="checkbox" name="ativo" value="1"' + (t.ativo !== false ? ' checked' : '') + '> Tema ativo (disponível para novas perguntas)</label></div></div>' +
      '<div class="acoes" style="margin-top:20px"><button class="btn btn-primario">Salvar</button><a class="btn btn-neutro" href="#/admin/temas">Cancelar</a></div></form>';
  }
  UI.rota('/admin/temas/novo', function () { return formTema({ ativo: true, ordem: S.t('temas').length + 1 }); }, ADM);
  UI.rota('/admin/temas/:id/editar', function (p) { return formTema(S.porId('temas', p.id)); }, ADM);
  UI.forms.salvarTema = function (d) {
    if (S.t('temas').some(function (t) { return t.nome.toLowerCase() === d.nome.trim().toLowerCase() && t.id !== +d.id; })) S.erro('Já existe um tema com este nome.');
    var t = d.id ? S.porId('temas', d.id) : S.inserir('temas', {});
    t.nome = d.nome.trim(); t.ordem = +d.ordem; t.descricao = d.descricao; t.ativo = d.ativo === '1';
    S.salvar(); voltar(d.id ? 'Tema de avaliação atualizado.' : 'Tema de avaliação cadastrado.', '#/admin/temas');
  };
  UI.acoes.alternarTema = function (a) { var t = S.porId('temas', a.dataset.id); t.ativo = !t.ativo; S.salvar(); voltar('Tema ' + (t.ativo ? 'ativado' : 'inativado') + '.', location.hash); };
  UI.acoes.excluirTema = function (a) {
    var id = +a.dataset.id;
    if (S.t('perguntas').some(function (p) { return p.tema_id === id; })) S.erro('Não é possível excluir: o tema possui perguntas vinculadas. Inative-o.');
    S.remover('temas', function (t) { return t.id === id; }); S.salvar(); voltar('Tema excluído.', '#/admin/temas');
  };

  /* ===================== EP04 Perguntas e respostas ===================== */
  UI.rota('/admin/perguntas', function (p, q) {
    var lista = filtrar(S.t('perguntas'), q.busca, ['codigo', 'enunciado']).filter(function (x) { return (!q.tema || x.tema_id === +q.tema) && (!q.tipo || x.tipo === q.tipo); })
      .sort(function (a, b) { return a.tema_id - b.tema_id || a.codigo.localeCompare(b.codigo); });
    var pg = UI.pagina(lista, 25, function (x) {
      var t = S.porId('temas', x.tema_id);
      return '<tr><td><span class="etiqueta azul">' + h(x.codigo) + '</span></td><td>' + h(UI.limitar(x.enunciado, 120)) + '</td><td class="muted">' + h(t ? t.nome : '') + '</td><td>' + TIPOS[x.tipo] + '</td>' +
        '<td class="num">' + (S.temOpcoes(x) ? S.opcoesDe(x.id).length : '—') + '</td><td>' + (x.obrigatoria ? '<span class="etiqueta cinza">Obrigatória</span> ' : '') + (x.pergunta_pai_id ? '<span class="etiqueta amarelo">Condicional</span> ' : '') + (!x.ativo ? '<span class="etiqueta vermelho">Inativa</span>' : '') + '</td>' +
        '<td class="acoes-col"><a class="btn btn-link" href="#/admin/perguntas/' + x.id + '">' + UI.ic('olho') + ' Respostas</a><a class="btn btn-link" href="#/admin/perguntas/' + x.id + '/editar">' + UI.ic('editar') + ' Editar</a>' +
        '<button class="btn btn-link perigo" data-acao="excluirPergunta" data-id="' + x.id + '" data-confirm="Excluir a pergunta ' + h(x.codigo) + '?">' + UI.ic('lixo') + ' Excluir</button></td></tr>';
    });
    return UI.cab('Perguntas e respostas', 'Banco de perguntas reutilizáveis entre questionários.', '<a href="#/admin/perguntas/nova" class="btn btn-primario">' + UI.ic('mais') + ' Cadastrar pergunta</a>') +
      '<form class="card filtros" data-form="filtro" data-destino="#/admin/perguntas"><div class="campo"><label for="pb">Buscar</label><input id="pb" type="search" name="busca" value="' + h(q.busca) + '" placeholder="Código ou enunciado"></div>' +
      '<div class="campo"><label for="pt">Tema</label><select id="pt" name="tema">' + UI.opts(S.t('temas').map(function (t) { return [t.id, t.nome]; }), q.tema, 'Todos') + '</select></div>' +
      '<div class="campo curto"><label for="pp">Tipo</label><select id="pp" name="tipo">' + UI.opts(Object.keys(TIPOS).map(function (k) { return [k, TIPOS[k]]; }), q.tipo, 'Todos') + '</select></div><button class="btn btn-secundario">' + UI.ic('filtro') + ' Filtrar</button></form>' +
      UI.tabela(['Código', 'Enunciado', 'Tema', 'Tipo', { t: 'Respostas', cls: 'num' }, 'Regras', { t: 'Ações', cls: 'acoes-col' }], pg.itens, 'Nenhuma pergunta encontrada.') + pg.nav;
  }, ADM);
  function formPergunta(x) {
    var bl = x.id && bloqueada(x), dis = bl ? ' disabled' : '';
    return UI.cab(x.id ? 'Editar pergunta ' + x.codigo : 'Cadastrar pergunta', null, null, '<a href="#/admin/perguntas">Perguntas</a> › ' + (x.id ? h(x.codigo) : 'Cadastrar')) +
      (bl ? '<div class="alerta aviso">' + UI.ic('cadeado') + '<div>Esta pergunta integra questionário publicado. Somente a <strong>ajuda contextual</strong> e o <strong>link de apoio</strong> podem ser alterados.</div></div>' : '') +
      '<form class="card" data-form="salvarPergunta"><input type="hidden" name="id" value="' + (x.id || '') + '"><div class="form-grade">' +
      '<div class="campo c3"><label class="obrigatorio" for="pc">Código</label><input id="pc" name="codigo" maxlength="20" value="' + h(x.codigo) + '" required' + dis + '></div>' +
      '<div class="campo c6"><label class="obrigatorio" for="pte">Tema de avaliação</label><select id="pte" name="tema_id" required' + dis + '>' + UI.opts(S.t('temas').filter(function (t) { return t.ativo || t.id === x.tema_id; }).map(function (t) { return [t.id, t.nome]; }), x.tema_id, 'Selecione…') + '</select></div>' +
      '<div class="campo c3"><label class="obrigatorio" for="pti">Tipo de resposta</label><select id="pti" name="tipo"' + dis + '>' + UI.opts(Object.keys(TIPOS).map(function (k) { return [k, TIPOS[k]]; }), x.tipo) + '</select></div>' +
      '<div class="campo"><label class="obrigatorio" for="pe">Enunciado</label><textarea id="pe" name="enunciado" required' + dis + '>' + h(x.enunciado) + '</textarea></div>' +
      '<div class="campo"><label for="pa">Ajuda contextual (orientação ao município)</label><textarea id="pa" name="ajuda">' + h(x.ajuda) + '</textarea></div>' +
      '<div class="campo c8"><label for="pl">Link para cartilha/material de apoio</label><input id="pl" name="link_apoio" type="url" value="' + h(x.link_apoio) + '" placeholder="https://"></div>' +
      '<div class="campo c4"><label class="obrigatorio" for="pw">Peso no índice</label><input id="pw" name="peso" type="number" step="0.01" min="0" value="' + (x.peso === undefined ? 1 : x.peso) + '"' + dis + '><span class="ajuda-campo">0 = informativa (não pontua).</span></div>' +
      '<div class="campo c4"><label class="check"><input type="checkbox" name="obrigatoria" value="1"' + (x.obrigatoria ? ' checked' : '') + dis + '> Resposta obrigatória</label></div>' +
      '<div class="campo c4"><label class="check"><input type="checkbox" name="exige_evidencia" value="1"' + (x.exige_evidencia ? ' checked' : '') + dis + '> Sempre exigir evidência</label></div>' +
      '<div class="campo c4"><label class="check"><input type="checkbox" name="ativo" value="1"' + (x.ativo ? ' checked' : '') + dis + '> Pergunta ativa</label></div></div>' +
      '<div class="acoes" style="margin-top:20px"><button class="btn btn-primario">Salvar</button><a class="btn btn-neutro" href="#/admin/perguntas' + (x.id ? '/' + x.id : '') + '">Cancelar</a></div></form>';
  }
  UI.rota('/admin/perguntas/nova', function () { return formPergunta({ tipo: 'unica', obrigatoria: true, ativo: true, peso: 1 }); }, ADM);
  UI.rota('/admin/perguntas/:id/editar', function (p) { return formPergunta(S.porId('perguntas', p.id)); }, ADM);
  UI.forms.salvarPergunta = function (d) {
    var x = d.id ? S.porId('perguntas', d.id) : null;
    if (x && bloqueada(x)) { x.ajuda = d.ajuda; x.link_apoio = d.link_apoio; S.salvar(); return voltar('Orientações atualizadas. Demais campos bloqueados.', '#/admin/perguntas/' + x.id); }
    if (S.t('perguntas').some(function (p) { return p.codigo === d.codigo.trim() && (!x || p.id !== x.id); })) S.erro('Já existe pergunta com o código ' + d.codigo + '.');
    if (x && !S.temOpcoes({ tipo: d.tipo }) && S.opcoesDe(x.id).length) S.erro('Exclua as respostas (opções) antes de alterar para um tipo sem opções.');
    x = x || S.inserir('perguntas', { pergunta_pai_id: null });
    Object.assign(x, { codigo: d.codigo.trim(), tema_id: +d.tema_id, tipo: d.tipo, enunciado: d.enunciado, ajuda: d.ajuda, link_apoio: d.link_apoio, peso: +d.peso, obrigatoria: d.obrigatoria === '1', exige_evidencia: d.exige_evidencia === '1', ativo: d.ativo === '1' });
    S.salvar(); voltar('Pergunta salva.' + (S.temOpcoes(x) && !S.opcoesDe(x.id).length ? ' Cadastre agora as respostas (opções).' : ''), '#/admin/perguntas/' + x.id);
  };
  UI.acoes.excluirPergunta = function (a) {
    var id = +a.dataset.id;
    if (S.t('questionario_pergunta').some(function (x) { return x.pergunta_id === id; })) S.erro('Não é possível excluir: a pergunta está associada a questionário(s).');
    if (S.t('perguntas').some(function (x) { return x.pergunta_pai_id === id; })) S.erro('Não é possível excluir: outras perguntas dependem desta.');
    var ops = S.opcoesDe(id).map(function (o) { return o.id; });
    S.remover('pergunta_dependencias', function (x) { return x.pergunta_id === id || ops.indexOf(x.opcao_resposta_id) >= 0; });
    S.remover('opcoes_resposta', function (o) { return o.pergunta_id === id; }); S.remover('perguntas', function (p) { return p.id === id; });
    S.salvar(); voltar('Pergunta excluída.', '#/admin/perguntas');
  };
  UI.rota('/admin/perguntas/:id', function (p) {
    var x = S.porId('perguntas', p.id), bl = bloqueada(x), dis = bl ? ' disabled' : '', t = S.porId('temas', x.tema_id), ops = S.opcoesDe(x.id), pai = x.pergunta_pai_id ? S.porId('perguntas', x.pergunta_pai_id) : null, hab = S.habilitadoras(x.id);
    var qs = S.t('questionario_pergunta').filter(function (r) { return r.pergunta_id === x.id; }).map(function (r) { return S.porId('questionarios', r.questionario_id).titulo; });
    var cand = S.t('perguntas').filter(function (c) { return S.temOpcoes(c) && c.id !== x.id; }).sort(function (a, b) { return a.codigo.localeCompare(b.codigo); });
    var html = UI.cab('Pergunta ' + x.codigo, h(t ? t.nome : '') + ' · ' + TIPOS[x.tipo], '<a href="#/admin/perguntas/' + x.id + '/editar" class="btn btn-secundario">' + UI.ic('editar') + ' Editar pergunta</a>', '<a href="#/admin/perguntas">Perguntas</a> › ' + h(x.codigo)) +
      (bl ? '<div class="alerta aviso">' + UI.ic('cadeado') + '<div>Pergunta em uso por questionário publicado: respostas e dependência estão bloqueadas para alteração.</div></div>' : '') +
      '<div class="card"><p style="font-size:var(--fs-md);font-weight:600">' + h(x.enunciado) + '</p><dl class="dl"><dt>Obrigatória</dt><dd>' + (x.obrigatoria ? 'Sim' : 'Não') + '</dd><dt>Peso no índice</dt><dd>' + UI.num(x.peso, 2) + '</dd>' +
      '<dt>Evidência</dt><dd>' + (x.exige_evidencia ? 'Sempre exigida' : 'Conforme a resposta selecionada') + '</dd><dt>Ajuda contextual</dt><dd>' + h(x.ajuda || '—') + '</dd><dt>Questionários</dt><dd>' + h(qs.join('; ') || 'Nenhum') + '</dd></dl></div>';
    if (S.temOpcoes(x)) {
      html += '<div class="card"><div class="card-titulo"><h3>Respostas (opções)</h3><span class="muted texto-pequeno">Pontuação máxima: ' + UI.num(S.pontMax(x), 2) + '</span></div>' +
        UI.tabela(['Respostas cadastradas'], ops.map(function (o) {
          return '<tr><td style="padding:6px 8px"><form class="filtros" style="gap:8px;align-items:center" data-form="salvarOpcao"><input type="hidden" name="id" value="' + o.id + '">' +
            '<input aria-label="Ordem" type="number" name="ordem" value="' + o.ordem + '" style="width:70px"' + dis + '><input aria-label="Texto" name="texto" value="' + h(o.texto) + '" style="flex:1;min-width:200px" required' + dis + '>' +
            '<input aria-label="Pontuação" type="number" step="0.01" name="pontuacao" value="' + o.pontuacao + '" style="width:100px"' + dis + '><label class="check"><input type="checkbox" name="solicita_evidencia" value="1"' + (o.solicita_evidencia ? ' checked' : '') + dis + '> Solicita evidência</label>' +
            (bl ? '' : '<button class="btn btn-link">Salvar</button><button type="button" class="btn btn-link perigo" data-acao="excluirOpcao" data-id="' + o.id + '" data-confirm="Excluir a resposta?">' + UI.ic('lixo') + '</button>') + '</form></td></tr>';
        }), 'Nenhuma resposta cadastrada. Cadastre ao menos duas opções.') +
        (bl ? '' : '<h4 style="margin-top:20px">Cadastrar nova resposta</h4><form class="filtros" data-form="novaOpcao"><input type="hidden" name="pergunta_id" value="' + x.id + '"><div class="campo"><label class="obrigatorio" for="ot">Texto</label><input id="ot" name="texto" required></div>' +
          '<div class="campo curto"><label class="obrigatorio" for="op">Pontuação</label><input id="op" type="number" step="0.01" name="pontuacao" value="0" required></div><div class="campo curto"><label class="check" style="margin-bottom:10px"><input type="checkbox" name="solicita_evidencia" value="1"> Solicita evidência</label></div>' +
          '<button class="btn btn-primario">' + UI.ic('mais') + ' Adicionar</button></form>') + '</div>';
    }
    html += '<div class="card"><div class="card-titulo"><h3>Dependência entre perguntas</h3></div><p class="muted">A pergunta permanece <strong>visível</strong> ao município, mas só fica <strong>habilitada</strong> quando a pergunta condicionante recebe uma das respostas selecionadas.</p>' +
      (pai ? '<div class="alerta info">' + UI.ic('info') + '<div>Atualmente condicionada a <strong>' + h(pai.codigo) + '</strong> = ' + h(hab.map(function (id) { var o = S.porId('opcoes_resposta', id); return o ? o.texto : ''; }).join(' ou ')) + '.</div></div>' : '') +
      '<form data-form="dependencia"><input type="hidden" name="id" value="' + x.id + '"><div class="form-grade"><div class="campo c6"><label for="dp">Pergunta condicionante</label><select id="dp" name="pai"' + dis + ' onchange="document.querySelectorAll(\'.opcoes-pai\').forEach(function(d){d.hidden=d.dataset.pai!==this.value}.bind(this))">' +
      UI.opts(cand.map(function (c) { return [c.id, c.codigo + ' — ' + UI.limitar(c.enunciado, 80)]; }), x.pergunta_pai_id || '', '— Sem dependência —') + '</select></div><div class="campo c6"><span class="rotulo-campo">Respostas que habilitam esta pergunta</span>' +
      cand.map(function (c) { return '<div class="opcoes-pai" data-pai="' + c.id + '"' + (c.id !== x.pergunta_pai_id ? ' hidden' : '') + '>' + S.opcoesDe(c.id).map(function (o) { return '<label class="check"><input type="checkbox" name="op_' + c.id + '[]" value="' + o.id + '"' + (hab.indexOf(o.id) >= 0 ? ' checked' : '') + dis + '> ' + h(o.texto) + '</label>'; }).join('') + '</div>'; }).join('') +
      '</div></div>' + (bl ? '' : '<div class="acoes" style="margin-top:16px"><button class="btn btn-primario">Salvar dependência</button></div>') + '</form></div>';
    return html;
  }, ADM);
  UI.forms.salvarOpcao = function (d) { var o = S.porId('opcoes_resposta', d.id); o.texto = d.texto; o.pontuacao = +d.pontuacao; o.ordem = +d.ordem; o.solicita_evidencia = d.solicita_evidencia === '1'; S.salvar(); voltar('Resposta atualizada.', location.hash); };
  UI.forms.novaOpcao = function (d) { var pid = +d.pergunta_id; S.inserir('opcoes_resposta', { pergunta_id: pid, texto: d.texto, pontuacao: +d.pontuacao, ordem: S.opcoesDe(pid).length + 1, solicita_evidencia: d.solicita_evidencia === '1' }); S.salvar(); voltar('Resposta cadastrada.', location.hash); };
  UI.acoes.excluirOpcao = function (a) { var id = +a.dataset.id; S.remover('pergunta_dependencias', function (x) { return x.opcao_resposta_id === id; }); S.remover('opcoes_resposta', function (o) { return o.id === id; }); S.salvar(); voltar('Resposta excluída.', location.hash); };
  UI.forms.dependencia = function (d) {
    var x = S.porId('perguntas', d.id);
    if (!d.pai) { x.pergunta_pai_id = null; S.remover('pergunta_dependencias', function (r) { return r.pergunta_id === x.id; }); S.salvar(); return voltar('Dependência removida.', location.hash); }
    var pai = +d.pai, ops = (d['op_' + pai] || []).map(Number);
    if (!ops.length) S.erro('Selecione ao menos uma resposta que habilita esta pergunta.');
    for (var c = S.porId('perguntas', pai), i = 0; c && i < 20; i++) { if (c.id === x.id) S.erro('Dependência circular não permitida.'); c = c.pergunta_pai_id ? S.porId('perguntas', c.pergunta_pai_id) : null; }
    x.pergunta_pai_id = pai; S.remover('pergunta_dependencias', function (r) { return r.pergunta_id === x.id; });
    ops.forEach(function (o) { S.inserir('pergunta_dependencias', { pergunta_id: x.id, opcao_resposta_id: o }); });
    S.salvar(); voltar('Dependência configurada.', location.hash);
  };

  /* ===================== EP03 Questionários ===================== */
  UI.rota('/admin/questionarios', function (p, q) {
    var lista = filtrar(S.t('questionarios'), q.busca, ['titulo']).filter(function (x) { return (!q.ciclo || x.ciclo === q.ciclo) && (!q.status || x.status === q.status); }).sort(function (a, b) { return a.data_inicio < b.data_inicio ? 1 : -1; });
    var ciclos = []; S.t('questionarios').forEach(function (x) { if (ciclos.indexOf(x.ciclo) < 0) ciclos.push(x.ciclo); });
    return UI.cab('Questionários de diagnóstico', 'Cadastro, publicação, prorrogação e acompanhamento dos ciclos.', '<a href="#/admin/questionarios/novo" class="btn btn-primario">' + UI.ic('mais') + ' Cadastrar questionário</a>') +
      '<form class="card filtros" data-form="filtro" data-destino="#/admin/questionarios"><div class="campo"><label for="qb">Buscar</label><input id="qb" type="search" name="busca" value="' + h(q.busca) + '" placeholder="Título"></div>' +
      '<div class="campo curto"><label for="qc">Ciclo</label><select id="qc" name="ciclo">' + UI.opts(ciclos.sort().reverse(), q.ciclo, 'Todos') + '</select></div>' +
      '<div class="campo curto"><label for="qs">Status</label><select id="qs" name="status">' + UI.opts([['rascunho', 'Rascunho'], ['publicado', 'Publicado'], ['encerrado', 'Encerrado']], q.status, 'Todos') + '</select></div><button class="btn btn-secundario">' + UI.ic('filtro') + ' Filtrar</button></form>' +
      UI.tabela(['Questionário', 'Ciclo', 'Período de preenchimento', 'Assinatura até', { t: 'Perguntas', cls: 'num' }, { t: 'Concluídos', cls: 'num' }, 'Situação', { t: 'Ações', cls: 'acoes-col' }], lista.map(function (x) {
        var np = S.perguntasDo(x.id).length, nc = S.t('preenchimentos').filter(function (r) { return r.questionario_id === x.id && S.CONCL.indexOf(r.status) >= 0; }).length;
        return '<tr><td><a href="#/admin/questionarios/' + x.id + '"><strong>' + h(x.titulo) + '</strong></a></td><td>' + h(x.ciclo) + '</td><td>' + S.br(x.data_inicio) + ' a ' + S.br(x.data_fim) + '</td><td>' + S.br(x.data_limite_assinatura) + '</td>' +
          '<td class="num">' + np + '</td><td class="num">' + nc + '</td><td>' + UI.etq('questionario', S.situacao(x)) + '</td><td class="acoes-col"><a class="btn btn-link" href="#/admin/questionarios/' + x.id + '">' + UI.ic('olho') + ' Visualizar</a></td></tr>';
      }), 'Nenhum questionário cadastrado.');
  }, ADM);
  function formQuest(x) {
    var sel = S.t('questionario_municipio').filter(function (r) { return r.questionario_id === x.id; }).map(function (r) { return r.municipio_id; });
    return UI.cab(x.id ? 'Editar questionário' : 'Cadastrar questionário', null, null, '<a href="#/admin/questionarios">Questionários</a> › ' + (x.id ? 'Editar' : 'Cadastrar')) +
      '<form class="card" data-form="salvarQuest"><input type="hidden" name="id" value="' + (x.id || '') + '"><div class="form-grade">' +
      '<div class="campo c8"><label class="obrigatorio" for="qt">Título</label><input id="qt" name="titulo" maxlength="200" value="' + h(x.titulo) + '" required></div>' +
      '<div class="campo c4"><label class="obrigatorio" for="qcy">Ciclo / exercício</label><input id="qcy" name="ciclo" value="' + h(x.ciclo) + '" required></div>' +
      '<div class="campo"><label for="qd">Descrição</label><textarea id="qd" name="descricao">' + h(x.descricao) + '</textarea></div>' +
      '<div class="campo"><label for="qa">Apresentação inicial (normativos, portarias e contextualização da obrigatoriedade)</label><textarea id="qa" name="apresentacao" style="min-height:140px">' + h(x.apresentacao) + '</textarea></div>' +
      '<div class="campo c4"><label class="obrigatorio" for="qi">Início do preenchimento</label><input id="qi" type="date" name="data_inicio" value="' + (x.data_inicio || '').slice(0, 10) + '" required></div>' +
      '<div class="campo c4"><label class="obrigatorio" for="qf">Encerramento do preenchimento</label><input id="qf" type="date" name="data_fim" value="' + (x.data_fim || '').slice(0, 10) + '" required><span class="ajuda-campo">Padrão: 15 dias corridos.</span></div>' +
      '<div class="campo c4"><label for="qas">Prazo final para validação/assinatura</label><input id="qas" type="date" name="data_limite_assinatura" value="' + (x.data_limite_assinatura || '').slice(0, 10) + '"><span class="ajuda-campo">Em branco: encerramento + 1 dia útil.</span></div>' +
      '<div class="campo"><span class="rotulo-campo obrigatorio">Abrangência</span><label class="check"><input type="radio" name="abrangencia" value="todos" data-abrangencia' + (x.abrangencia !== 'selecionados' ? ' checked' : '') + '> Todos os municípios jurisdicionados</label>' +
      '<label class="check"><input type="radio" name="abrangencia" value="selecionados" data-abrangencia' + (x.abrangencia === 'selecionados' ? ' checked' : '') + '> Somente municípios selecionados (questionário personalizado)</label></div>' +
      '<div class="campo" id="box-municipios"' + (x.abrangencia !== 'selecionados' ? ' hidden' : '') + '><label for="qm">Municípios (Ctrl/Cmd + clique)</label><select id="qm" name="municipios[]" multiple>' +
      S.t('municipios').map(function (m) { return '<option value="' + m.id + '"' + (sel.indexOf(m.id) >= 0 ? ' selected' : '') + '>' + h(m.nome) + '</option>'; }).join('') + '</select></div></div>' +
      '<div class="acoes" style="margin-top:20px"><button class="btn btn-primario">' + (x.id ? 'Salvar' : 'Salvar e associar perguntas') + '</button><a class="btn btn-neutro" href="#/admin/questionarios">Cancelar</a></div></form>';
  }
  UI.rota('/admin/questionarios/novo', function () {
    var ini = S.somarDias(S.hojeISO(), 1), fim = S.somarDias(ini, 14);
    return formQuest({ ciclo: String(new Date().getFullYear()), data_inicio: ini, data_fim: fim, data_limite_assinatura: S.somarDiasUteis(fim, 1), abrangencia: 'todos' });
  }, ADM);
  UI.rota('/admin/questionarios/:id/editar', function (p) {
    var x = S.porId('questionarios', p.id);
    if (x.status !== 'rascunho') { UI.flash('erro', 'Questionário publicado não pode ser editado. Utilize "Prorrogar prazo" ou "Duplicar".'); setTimeout(function () { UI.ir('#/admin/questionarios/' + x.id); }); return ''; }
    return formQuest(x);
  }, ADM);
  UI.forms.salvarQuest = function (d) {
    if (d.data_fim < d.data_inicio) S.erro('O encerramento deve ser igual ou posterior ao início.');
    var munis = (d.municipios || []).map(Number);
    if (d.abrangencia === 'selecionados' && !munis.length) S.erro('Selecione os municípios quando a abrangência for "municípios selecionados".');
    var ass = d.data_limite_assinatura || S.somarDiasUteis(d.data_fim, 1);
    if (ass < d.data_fim) S.erro('O prazo de assinatura deve ser igual ou posterior ao encerramento.');
    var x = d.id ? S.porId('questionarios', d.id) : S.inserir('questionarios', { status: 'rascunho', publicado_em: null, origem_id: null });
    Object.assign(x, { titulo: d.titulo, ciclo: d.ciclo, descricao: d.descricao, apresentacao: d.apresentacao, data_inicio: d.data_inicio, data_fim: d.data_fim, data_limite_assinatura: ass, abrangencia: d.abrangencia });
    S.remover('questionario_municipio', function (r) { return r.questionario_id === x.id; });
    if (d.abrangencia === 'selecionados') munis.forEach(function (m) { S.inserir('questionario_municipio', { questionario_id: x.id, municipio_id: m }); });
    S.salvar(); voltar(d.id ? 'Questionário atualizado.' : 'Questionário cadastrado. Associe agora as perguntas.', d.id ? '#/admin/questionarios/' + x.id : '#/admin/questionarios/' + x.id + '/perguntas');
  };
  UI.rota('/admin/questionarios/:id', function (p) {
    var x = S.porId('questionarios', p.id); if (!x) return '<div class="card vazio">Questionário não encontrado.</div>';
    var sit = S.situacao(x), rasc = x.status === 'rascunho', pres = S.t('preenchimentos').filter(function (r) { return r.questionario_id === x.id; });
    var cnt = function (s) { return pres.filter(function (r) { return s.indexOf(r.status) >= 0; }).length; };
    var origem = x.origem_id ? S.porId('questionarios', x.origem_id) : null, munis = S.municipiosAlvo(x);
    var acoes = (rasc ? '<a href="#/admin/questionarios/' + x.id + '/editar" class="btn btn-neutro">' + UI.ic('editar') + ' Editar</a><a href="#/admin/questionarios/' + x.id + '/perguntas" class="btn btn-neutro">' + UI.ic('pergunta') + ' Associar perguntas</a>' +
      '<button class="btn btn-sucesso" data-acao="publicar" data-id="' + x.id + '" data-confirm="Publicar o questionário? Após a publicação as perguntas não poderão ser alteradas e os municípios serão notificados.">' + UI.ic('publicar') + ' Publicar</button>'
      : '<button class="btn btn-secundario" data-abrir-modal="modal-prorrogar">' + UI.ic('relogio') + ' Prorrogar prazo</button>') +
      '<button class="btn btn-neutro" data-acao="duplicar" data-id="' + x.id + '" data-confirm="Criar uma cópia deste questionário em rascunho?">' + UI.ic('copiar') + ' Duplicar</button><button class="btn btn-neutro" data-acao="imprimir">' + UI.ic('download') + ' Baixar PDF</button>' +
      (rasc ? '<button class="btn btn-perigo" data-acao="excluirQuest" data-id="' + x.id + '" data-confirm="Excluir definitivamente este questionário?">' + UI.ic('lixo') + ' Excluir</button>' : '');
    var np = S.perguntasDo(x.id).length;
    return UI.cab(x.titulo, 'Ciclo ' + h(x.ciclo) + ' · ' + UI.etq('questionario', sit), acoes, '<a href="#/admin/questionarios">Questionários</a> › Visualizar') +
      (rasc ? '' : '<div class="grade grade-4" style="margin-bottom:20px"><div class="kpi"><div class="rotulo">Municípios abrangidos</div><div class="valor">' + munis.length + '</div></div><div class="kpi amarelo"><div class="rotulo">Em preenchimento</div><div class="valor">' + cnt(['em_preenchimento', 'devolvido']) + '</div></div>' +
        '<div class="kpi"><div class="rotulo">Aguardando validação</div><div class="valor">' + cnt(['aguardando_validacao']) + '</div></div><div class="kpi verde"><div class="rotulo">Validados</div><div class="valor">' + cnt(['validado']) + '</div><div class="detalhe"><a href="#/resultados?q=' + x.id + '">Ver resultados por município ›</a></div></div></div>') +
      '<div class="card"><h3>Informações gerais</h3><dl class="dl"><dt>Período de preenchimento</dt><dd>' + S.br(x.data_inicio) + ' a ' + S.br(x.data_fim) + '</dd><dt>Prazo para validação/assinatura</dt><dd>' + S.br(x.data_limite_assinatura) + '</dd>' +
      '<dt>Abrangência</dt><dd>' + (x.abrangencia === 'todos' ? 'Todos os municípios' : h(munis.map(function (m) { return m.nome; }).join(', '))) + '</dd><dt>Publicação</dt><dd>' + (x.publicado_em ? S.brHora(x.publicado_em) : 'Não publicado') + '</dd>' +
      (origem ? '<dt>Duplicado de</dt><dd><a href="#/admin/questionarios/' + origem.id + '">' + h(origem.titulo) + '</a></dd>' : '') + '<dt>Apresentação</dt><dd class="texto-pre">' + h(x.apresentacao || '—') + '</dd></dl></div>' +
      '<div class="card-titulo"><h2>Perguntas (' + np + ')</h2></div>' + (np ? '' : '<div class="alerta aviso">' + UI.ic('alerta') + '<div>Nenhuma pergunta associada. <a href="#/admin/questionarios/' + x.id + '/perguntas">Associar perguntas</a>.</div></div>') + UI.questoesLeitura(x.id, null) +
      UI.modal('modal-prorrogar', 'Prorrogar prazo de preenchimento', '<input type="hidden" name="id" value="' + x.id + '"><div class="form-grade"><p class="campo muted">Encerramento atual: <strong>' + S.br(x.data_fim) + '</strong> · Assinatura até <strong>' + S.br(x.data_limite_assinatura) + '</strong>. A operação é registrada em auditoria e os municípios são notificados.</p>' +
        '<div class="campo c6"><label class="obrigatorio" for="pf">Novo encerramento</label><input id="pf" type="date" name="data_fim" required></div><div class="campo c6"><label class="obrigatorio" for="pa">Novo prazo de assinatura</label><input id="pa" type="date" name="data_limite_assinatura" required></div>' +
        '<div class="campo"><label class="obrigatorio" for="pj">Justificativa</label><textarea id="pj" name="justificativa" minlength="10" required></textarea></div></div>', '<button class="btn btn-primario">Prorrogar</button>', 'prorrogar');
  }, ADM);
  UI.acoes.publicar = function (a) { S.publicar(S.porId('questionarios', a.dataset.id)); voltar('Questionário publicado. Os municípios foram/serão notificados no início do período.', location.hash); };
  UI.acoes.duplicar = function (a) { var n = S.duplicar(S.porId('questionarios', a.dataset.id)); voltar('Questionário duplicado. Ajuste título, datas e abrangência.', '#/admin/questionarios/' + n.id + '/editar'); };
  UI.acoes.excluirQuest = function (a) {
    var id = +a.dataset.id;
    if (S.t('preenchimentos').some(function (r) { return r.questionario_id === id; })) S.erro('Somente questionários sem preenchimentos podem ser excluídos.');
    S.remover('questionario_pergunta', function (r) { return r.questionario_id === id; }); S.remover('questionario_municipio', function (r) { return r.questionario_id === id; });
    S.remover('questionarios', function (r) { return r.id === id; }); S.salvar(); voltar('Questionário excluído.', '#/admin/questionarios');
  };
  UI.forms.prorrogar = function (d) { document.getElementById('modal-prorrogar').close(); S.prorrogar(S.porId('questionarios', d.id), d.data_fim, d.data_limite_assinatura, d.justificativa); voltar('Prazo prorrogado e registrado em auditoria.', location.hash); };
  UI.rota('/admin/questionarios/:id/perguntas', function (p) {
    var x = S.porId('questionarios', p.id), ed = x.status === 'rascunho', dis = ed ? '' : ' disabled', ord = {};
    S.t('questionario_pergunta').forEach(function (r) { if (r.questionario_id === x.id) ord[r.pergunta_id] = r.ordem; });
    return UI.cab('Associar perguntas ao questionário', 'Selecione as perguntas do banco. A ordem segue o tema e o código; ajuste se necessário.', null, '<a href="#/admin/questionarios">Questionários</a> › <a href="#/admin/questionarios/' + x.id + '">' + h(UI.limitar(x.titulo, 50)) + '</a> › Perguntas') +
      (ed ? '' : '<div class="alerta aviso">' + UI.ic('cadeado') + '<div>Questionário publicado: a associação de perguntas está bloqueada.</div></div>') +
      '<form data-form="associar"><input type="hidden" name="id" value="' + x.id + '">' + S.t('temas').slice().sort(function (a, b) { return a.ordem - b.ordem; }).map(function (t) {
        var ps = S.t('perguntas').filter(function (q) { return q.tema_id === t.id && q.ativo; }).sort(function (a, b) { return a.codigo.localeCompare(b.codigo); });
        if (!ps.length) return '';
        return '<div class="card"><div class="card-titulo"><h3>' + h(t.nome) + '</h3><label class="check texto-pequeno"><input type="checkbox" data-marcar-todos=".pt-' + t.id + '"' + dis + '> Marcar todas</label></div>' +
          UI.tabela(['', 'Ordem', 'Código', 'Enunciado', 'Tipo'], ps.map(function (q) {
            var pai = q.pergunta_pai_id ? S.porId('perguntas', q.pergunta_pai_id) : null;
            return '<tr><td><input type="checkbox" class="pt-' + t.id + '" name="perguntas[]" value="' + q.id + '"' + (q.id in ord ? ' checked' : '') + dis + '></td><td><input type="number" name="ordem[' + q.id + ']" value="' + (ord[q.id] || '') + '" style="width:70px"' + dis + '></td>' +
              '<td><span class="etiqueta azul">' + h(q.codigo) + '</span></td><td>' + h(q.enunciado) + (pai ? '<br><span class="etiqueta amarelo">Depende de ' + h(pai.codigo) + '</span>' : '') + '</td><td class="muted">' + TIPOS[q.tipo] + '</td></tr>';
          })) + '</div>';
      }).join('') + (ed ? '<div class="acoes"><button class="btn btn-primario">Salvar associação</button><a class="btn btn-neutro" href="#/admin/questionarios/' + x.id + '">Cancelar</a></div>' : '') + '</form>';
  }, ADM);
  UI.forms.associar = function (d) {
    var ids = (d.perguntas || []).map(Number), ordem = d.ordem || {};
    var falt = ids.map(function (i) { return S.porId('perguntas', i); }).filter(function (q) { return q.pergunta_pai_id && ids.indexOf(q.pergunta_pai_id) < 0; }).map(function (q) { return q.codigo; });
    if (falt.length) S.erro('As perguntas ' + falt.join(', ') + ' dependem de outras perguntas que não foram selecionadas.');
    var temaOrd = {}; S.t('temas').forEach(function (t) { temaOrd[t.id] = t.ordem; });
    var ps = ids.map(function (i) { return S.porId('perguntas', i); }).sort(function (a, b) { return ((+ordem[a.id] || 9999) - (+ordem[b.id] || 9999)) || (temaOrd[a.tema_id] - temaOrd[b.tema_id]) || a.codigo.localeCompare(b.codigo); });
    S.remover('questionario_pergunta', function (r) { return r.questionario_id === +d.id; });
    ps.forEach(function (q, i) { S.inserir('questionario_pergunta', { questionario_id: +d.id, pergunta_id: q.id, ordem: i + 1 }); });
    S.salvar(); voltar(ps.length + ' pergunta(s) associada(s) ao questionário.', '#/admin/questionarios/' + d.id);
  };

  /* ===================== EP12 Auditoria ===================== */
  function auditFiltrada(q) {
    return S.t('auditorias').filter(function (a) {
      return (!q.acao || a.acao === q.acao) && (!q.de || a.created_at.slice(0, 10) >= q.de) && (!q.ate || a.created_at.slice(0, 10) <= q.ate) &&
        (!q.busca || (a.entidade_descricao + ' ' + a.user_nome).toLowerCase().indexOf(q.busca.toLowerCase()) >= 0);
    }).sort(function (a, b) { return a.created_at < b.created_at ? 1 : -1; });
  }
  UI.rota('/admin/auditoria', function (p, q) {
    var cor = { publicacao: 'verde', prorrogacao: 'amarelo', validacao: 'azul' };
    var pg = UI.pagina(auditFiltrada(q), 20, function (a) {
      return '<tr><td>' + S.brHora(a.created_at) + '</td><td><span class="etiqueta ' + (cor[a.acao] || 'cinza') + '">' + (ACOES[a.acao] || a.acao) + '</span></td><td>' + h(a.entidade_descricao) + '</td><td>' + h(a.user_nome) + '</td><td class="muted">' + h(UI.limitar(a.justificativa, 80) || '—') + '</td>' +
        '<td class="acoes-col"><a class="btn btn-link" href="#/admin/auditoria/' + a.id + '">' + UI.ic('olho') + ' Detalhes</a></td></tr>';
    });
    return UI.cab('Auditoria', 'Registros de publicação, prorrogação, validação e encerramento de questionários.', '<button class="btn btn-neutro" data-acao="imprimir">' + UI.ic('download') + ' Exportar PDF</button><button class="btn btn-neutro" data-acao="exportarAuditoria">' + UI.ic('download') + ' Exportar XLS</button>') +
      '<form class="card filtros" data-form="filtro" data-destino="#/admin/auditoria"><div class="campo"><label for="ab">Questionário ou usuário</label><input id="ab" type="search" name="busca" value="' + h(q.busca) + '"></div>' +
      '<div class="campo curto"><label for="aa">Ação</label><select id="aa" name="acao">' + UI.opts(Object.keys(ACOES).map(function (k) { return [k, ACOES[k]]; }), q.acao, 'Todas') + '</select></div>' +
      '<div class="campo curto"><label for="ad">De</label><input id="ad" type="date" name="de" value="' + h(q.de) + '"></div><div class="campo curto"><label for="at">Até</label><input id="at" type="date" name="ate" value="' + h(q.ate) + '"></div><button class="btn btn-secundario">' + UI.ic('filtro') + ' Filtrar</button></form>' +
      UI.tabela(['Data/hora', 'Ação', 'Questionário / objeto', 'Usuário', 'Justificativa', { t: 'Ações', cls: 'acoes-col' }], pg.itens, 'Nenhum registro de auditoria.') + pg.nav;
  }, ADM);
  UI.acoes.exportarAuditoria = function () {
    var res = function (o) { return o ? Object.keys(o).map(function (k) { return k + ': ' + (typeof o[k] === 'object' ? JSON.stringify(o[k]) : o[k]); }).join('; ') : ''; };
    UI.exportarXls('Auditoria - Publicação e Prorrogação', ['ID', 'Data/hora', 'Ação', 'Questionário', 'Usuário', 'Justificativa', 'Dados anteriores', 'Dados novos', 'IP'],
      auditFiltrada(UI.q()).map(function (a) { return [a.id, S.brHora(a.created_at), ACOES[a.acao], a.entidade_descricao, a.user_nome, a.justificativa, res(a.dados_anteriores), res(a.dados_novos), a.ip]; }), 'auditoria.xls');
  };
  UI.rota('/admin/auditoria/:id', function (p) {
    var a = S.porId('auditorias', p.id);
    var bloco = function (t, o) { return '<div class="card"><h3>' + t + '</h3>' + (o && Object.keys(o).length ? '<dl class="dl">' + Object.keys(o).map(function (k) { var v = o[k]; return '<dt>' + h(k.replace(/_/g, ' ')) + '</dt><dd>' + h(v === null ? '—' : (typeof v === 'object' ? JSON.stringify(v) : (/^\d{4}-\d{2}-\d{2}$/.test(v) ? S.br(v) : v))) + '</dd>'; }).join('') + '</dl>' : '<p class="muted">—</p>') + '</div>'; };
    return UI.cab('Registro de auditoria #' + a.id, ACOES[a.acao], '<button class="btn btn-neutro" data-acao="imprimir">' + UI.ic('download') + ' Exportar PDF</button>', '<a href="#/admin/auditoria">Auditoria</a> › Registro #' + a.id) +
      '<div class="card"><dl class="dl"><dt>Data/hora</dt><dd>' + S.brHora(a.created_at) + '</dd><dt>Ação</dt><dd>' + ACOES[a.acao] + '</dd><dt>Objeto</dt><dd>' + h(a.entidade_tipo + ' #' + a.entidade_id + ' — ' + a.entidade_descricao) + '</dd>' +
      '<dt>Usuário</dt><dd>' + h(a.user_nome) + '</dd><dt>Endereço IP</dt><dd>' + h(a.ip || '—') + '</dd><dt>Justificativa</dt><dd class="texto-pre">' + h(a.justificativa || '—') + '</dd></dl></div>' +
      '<div class="grade grade-2">' + bloco('Dados anteriores', a.dados_anteriores) + bloco('Dados novos', a.dados_novos) + '</div><a href="#/admin/auditoria" class="btn btn-neutro">' + UI.ic('voltar') + ' Voltar</a>';
  }, ADM);

  /* ===================== Usuários / municípios / rotina ===================== */
  UI.rota('/admin/usuarios', function (p, q) {
    var lista = filtrar(S.t('users'), q.busca, ['name', 'email', 'cpf']).filter(function (u) { return !q.perfil || u.perfil === q.perfil; }).sort(function (a, b) { return a.name.localeCompare(b.name); });
    return UI.cab('Usuários', 'Cadastro provisório até a integração com o CADUN (Cadastro Único do Tribunal).', '<a href="#/admin/usuarios/novo" class="btn btn-primario">' + UI.ic('mais') + ' Cadastrar usuário</a>') +
      '<form class="card filtros" data-form="filtro" data-destino="#/admin/usuarios"><div class="campo"><label for="ub">Buscar</label><input id="ub" type="search" name="busca" value="' + h(q.busca) + '" placeholder="Nome, e-mail ou CPF"></div>' +
      '<div class="campo curto"><label for="up">Perfil</label><select id="up" name="perfil">' + UI.opts(Object.keys(UI.PERFIL).map(function (k) { return [k, UI.PERFIL[k]]; }), q.perfil, 'Todos') + '</select></div><button class="btn btn-secundario">' + UI.ic('filtro') + ' Filtrar</button></form>' +
      UI.tabela(['Nome', 'E-mail', 'CPF', 'Perfil', 'Município', 'Último acesso', 'Situação', { t: 'Ações', cls: 'acoes-col' }], lista.map(function (u) {
        var m = u.municipio_id ? S.porId('municipios', u.municipio_id) : null, cpf = u.cpf ? u.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : '—';
        return '<tr><td><strong>' + h(u.name) + '</strong>' + (u.cargo ? '<br><span class="muted texto-pequeno">' + h(u.cargo) + '</span>' : '') + '</td><td>' + h(u.email) + '</td><td>' + cpf + '</td><td>' + UI.PERFIL[u.perfil] + '</td><td>' + h(m ? m.nome : '—') + '</td>' +
          '<td>' + (u.ultimo_acesso_em ? S.brHora(u.ultimo_acesso_em) + '<br><span class="muted texto-pequeno">via ' + h(u.ultimo_metodo_login) + '</span>' : '—') + '</td><td>' + (u.ativo ? '<span class="etiqueta verde">Ativo</span>' : '<span class="etiqueta cinza">Inativo</span>') + '</td>' +
          '<td class="acoes-col"><a class="btn btn-link" href="#/admin/usuarios/' + u.id + '/editar">' + UI.ic('editar') + ' Editar</a></td></tr>';
      }));
  }, ADM);
  function formUsuario(u) {
    return UI.cab(u.id ? 'Editar usuário' : 'Cadastrar usuário', null, null, '<a href="#/admin/usuarios">Usuários</a> › ' + (u.id ? 'Editar' : 'Cadastrar')) +
      '<div class="alerta info">' + UI.ic('info') + '<div>Usuários municipais acessam via <strong>GOV.BR</strong> ou <strong>Certificado Digital</strong> (CPF). Usuários do Tribunal acessam com e-mail/CPF e senha.</div></div>' +
      '<form class="card" data-form="salvarUsuario"><input type="hidden" name="id" value="' + (u.id || '') + '"><div class="form-grade">' +
      '<div class="campo c6"><label class="obrigatorio" for="un">Nome</label><input id="un" name="name" value="' + h(u.name) + '" required></div><div class="campo c6"><label class="obrigatorio" for="ue">E-mail</label><input id="ue" type="email" name="email" value="' + h(u.email) + '" required></div>' +
      '<div class="campo c4"><label for="uc">CPF</label><input id="uc" name="cpf" value="' + h(u.cpf) + '" placeholder="Somente números"></div><div class="campo c4"><label class="obrigatorio" for="upf">Perfil</label><select id="upf" name="perfil">' + UI.opts(Object.keys(UI.PERFIL).map(function (k) { return [k, UI.PERFIL[k]]; }), u.perfil) + '</select></div>' +
      '<div class="campo c4"><label for="um">Município (perfis municipais)</label><select id="um" name="municipio_id">' + UI.opts(S.t('municipios').map(function (m) { return [m.id, m.nome]; }), u.municipio_id, '—') + '</select></div>' +
      '<div class="campo c6"><label for="ucg">Cargo</label><input id="ucg" name="cargo" value="' + h(u.cargo) + '"></div><div class="campo"><label class="check"><input type="checkbox" name="ativo" value="1"' + (u.ativo !== false ? ' checked' : '') + '> Usuário ativo</label></div></div>' +
      '<div class="acoes" style="margin-top:20px"><button class="btn btn-primario">Salvar</button><a class="btn btn-neutro" href="#/admin/usuarios">Cancelar</a></div></form>';
  }
  UI.rota('/admin/usuarios/novo', function () { return formUsuario({ perfil: 'responsavel', ativo: true }); }, ADM);
  UI.rota('/admin/usuarios/:id/editar', function (p) { return formUsuario(S.porId('users', p.id)); }, ADM);
  UI.forms.salvarUsuario = function (d) {
    var cpf = (d.cpf || '').replace(/\D/g, ''), mun = d.perfil === 'responsavel' || d.perfil === 'gestor';
    if (cpf && cpf.length !== 11) S.erro('CPF deve ter 11 dígitos.');
    if (mun && !cpf) S.erro('CPF é obrigatório para usuários municipais (login GOV.BR/Certificado).');
    if (mun && !d.municipio_id) S.erro('Informe o município para perfis municipais.');
    if (S.t('users').some(function (u) { return u.id !== +d.id && (u.email === d.email || (cpf && u.cpf === cpf)); })) S.erro('E-mail ou CPF já cadastrado.');
    var eu = S.usuarioAtual();
    if (+d.id === eu.id && (d.perfil !== 'administrador' || d.ativo !== '1')) S.erro('Você não pode remover seu próprio perfil de administrador nem se inativar.');
    var u = d.id ? S.porId('users', d.id) : S.inserir('users', {});
    Object.assign(u, { name: d.name, email: d.email, cpf: cpf || null, perfil: d.perfil, municipio_id: mun ? +d.municipio_id : null, cargo: d.cargo, ativo: d.ativo === '1' });
    S.salvar(); voltar('Usuário salvo.', '#/admin/usuarios');
  };
  UI.rota('/admin/municipios', function (p, q) {
    var lista = filtrar(S.t('municipios'), q.busca, ['nome']).filter(function (m) { return !q.relatoria || m.relatoria_id === +q.relatoria; });
    var pg = UI.pagina(lista, 30, function (m) {
      var n = S.t('users').filter(function (u) { return u.municipio_id === m.id; }).length;
      return '<tr><td>' + m.codigo_ibge + '</td><td><strong>' + h(m.nome) + '</strong></td><td>' + h(m.regiao_imediata) + '</td><td class="num">' + n + '</td><td><form data-form="relatoriaMun"><input type="hidden" name="id" value="' + m.id + '"><select name="relatoria_id" data-auto aria-label="Relatoria">' +
        UI.opts(S.t('relatorias').map(function (r) { return [r.id, r.nome]; }), m.relatoria_id, '—') + '</select></form></td></tr>';
    });
    return UI.cab('Municípios e relatorias', 'Municípios jurisdicionados (fonte IBGE) e vinculação à relatoria responsável.') +
      '<div class="alerta aviso">' + UI.ic('alerta') + '<div>A distribuição inicial dos municípios por relatoria é <strong>provisória</strong>. Ajuste conforme a distribuição oficial do Tribunal.</div></div>' +
      '<form class="card filtros" data-form="filtro" data-destino="#/admin/municipios"><div class="campo"><label for="mb">Município</label><input id="mb" type="search" name="busca" value="' + h(q.busca) + '"></div>' +
      '<div class="campo curto"><label for="mr">Relatoria</label><select id="mr" name="relatoria">' + UI.opts(S.t('relatorias').map(function (r) { return [r.id, r.nome]; }), q.relatoria, 'Todas') + '</select></div><button class="btn btn-secundario">' + UI.ic('filtro') + ' Filtrar</button></form>' +
      UI.tabela(['Código IBGE', 'Município', 'Região imediata', { t: 'Usuários', cls: 'num' }, 'Relatoria'], pg.itens) + pg.nav;
  }, ADM);
  UI.forms.relatoriaMun = function (d) { var m = S.porId('municipios', d.id); m.relatoria_id = d.relatoria_id ? +d.relatoria_id : null; S.salvar(); voltar('Relatoria de ' + m.nome + ' atualizada.', location.hash); };
  UI.rota('/admin/rotina', function () {
    return UI.cab('Rotina diária de marcos', 'Na aplicação real, o agendador executa diariamente o comando sdgt:processar-marcos.') +
      '<div class="card"><p>A rotina: (1) notifica o <strong>início</strong> do período aos responsáveis e gestores; (2) envia <strong>lembrete</strong> aos municípios pendentes quando faltam ' + S.db().config.prazos.dias_lembrete_encerramento + ' dias ou menos; (3) notifica o <strong>encerramento</strong>; (4) <strong>encerra</strong> questionários após o prazo de assinatura (registrando em auditoria).</p>' +
      '<p class="muted">Na demonstração os e-mails não são enviados — as notificações aparecem no sino de cada usuário.</p><button class="btn btn-primario" data-acao="rotina">' + UI.ic('relogio') + ' Executar agora</button></div>';
  }, ADM);
  UI.acoes.rotina = function () { var r = S.processarMarcos(); voltar('Rotina executada — início: ' + r.inicio + ' · lembretes: ' + r.lembrete + ' · encerramento: ' + r.encerramento + ' · questionários encerrados: ' + r.encerrados, '#/admin/rotina'); };

  /* ===================== EP11 Materiais ===================== */
  UI.rota('/materiais', function (p, q) {
    var adm = S.usuarioAtual().perfil === 'administrador';
    var lista = filtrar(S.t('materiais_apoio'), q.busca, ['titulo']).filter(function (m) { return (adm || m.ativo) && (!q.categoria || m.categoria === q.categoria); });
    return UI.cab('Materiais de apoio', 'Cartilhas, manuais e normativos para auxiliar no preenchimento dos questionários.', adm ? '<a href="#/admin/materiais/novo" class="btn btn-primario">' + UI.ic('mais') + ' Cadastrar material</a>' : '') +
      '<form class="card filtros" data-form="filtro" data-destino="#/materiais"><div class="campo"><label for="mtb">Buscar</label><input id="mtb" type="search" name="busca" value="' + h(q.busca) + '" placeholder="Título"></div>' +
      '<div class="campo curto"><label for="mtc">Categoria</label><select id="mtc" name="categoria">' + UI.opts(Object.keys(CATS).map(function (k) { return [k, CATS[k]]; }), q.categoria, 'Todas') + '</select></div><button class="btn btn-secundario">' + UI.ic('filtro') + ' Filtrar</button></form>' +
      UI.tabela(['Título', 'Categoria', 'Descrição'].concat(adm ? ['Situação'] : []).concat([{ t: 'Ações', cls: 'acoes-col' }]), lista.map(function (m) {
        return '<tr><td><strong>' + h(m.titulo) + '</strong></td><td><span class="etiqueta azul">' + CATS[m.categoria] + '</span></td><td class="muted">' + h(m.descricao) + '</td>' + (adm ? '<td>' + (m.ativo ? '<span class="etiqueta verde">Ativo</span>' : '<span class="etiqueta cinza">Inativo</span>') + '</td>' : '') +
          '<td class="acoes-col">' + (m.url_externa && !m.arquivo_nome ? '<a class="btn btn-secundario btn-pequeno" href="' + h(m.url_externa) + '" target="_blank" rel="noopener">' + UI.ic('link') + ' Abrir link</a>' : '<button class="btn btn-secundario btn-pequeno" data-acao="baixarMaterial" data-id="' + m.id + '">' + UI.ic('download') + ' Baixar</button>') +
          (adm ? '<a class="btn btn-link" href="#/admin/materiais/' + m.id + '/editar">' + UI.ic('editar') + ' Editar</a><button class="btn btn-link perigo" data-acao="excluirMaterial" data-id="' + m.id + '" data-confirm="Excluir o material?">' + UI.ic('lixo') + ' Excluir</button>' : '') + '</td></tr>';
      }), 'Nenhum material disponível.');
  });
  UI.acoes.baixarMaterial = function (a) {
    var m = S.porId('materiais_apoio', a.dataset.id);
    if (m.conteudo) fetch(m.conteudo).then(function (r) { return r.blob(); }).then(function (b) { UI.baixarBlob(b, m.arquivo_nome); });
    else UI.baixarBlob(m.titulo + '\n\n' + (m.descricao || '') + '\n\n(Arquivo de demonstração. Na aplicação real é baixado o documento cadastrado.)', (m.arquivo_nome || 'material').replace(/\.\w+$/, '') + '.txt', 'text/plain');
  };
  UI.acoes.excluirMaterial = function (a) { S.remover('materiais_apoio', function (m) { return m.id === +a.dataset.id; }); S.salvar(); voltar('Material de apoio excluído.', '#/materiais'); };
  function formMaterial(m) {
    return UI.cab(m.id ? 'Editar material de apoio' : 'Cadastrar material de apoio', null, null, '<a href="#/materiais">Materiais de apoio</a> › ' + (m.id ? 'Editar' : 'Cadastrar')) +
      '<form class="card" data-form="salvarMaterial"><input type="hidden" name="id" value="' + (m.id || '') + '"><div class="form-grade">' +
      '<div class="campo c8"><label class="obrigatorio" for="mt">Título</label><input id="mt" name="titulo" value="' + h(m.titulo) + '" required></div><div class="campo c4"><label class="obrigatorio" for="mc">Categoria</label><select id="mc" name="categoria">' + UI.opts(Object.keys(CATS).map(function (k) { return [k, CATS[k]]; }), m.categoria) + '</select></div>' +
      '<div class="campo"><label for="md">Descrição</label><textarea id="md" name="descricao">' + h(m.descricao) + '</textarea></div>' +
      '<div class="campo c6"><label for="mf">Arquivo (até 2 MB na demonstração)</label><input id="mf" type="file" name="arquivo">' + (m.arquivo_nome ? '<span class="ajuda-campo">Atual: ' + h(m.arquivo_nome) + '</span>' : '') + '</div>' +
      '<div class="campo c6"><label for="mu">ou link externo</label><input id="mu" type="url" name="url_externa" value="' + h(m.url_externa) + '" placeholder="https://"></div>' +
      '<div class="campo"><label class="check"><input type="checkbox" name="ativo" value="1"' + (m.ativo !== false ? ' checked' : '') + '> Disponível para os municípios</label></div></div>' +
      '<div class="acoes" style="margin-top:20px"><button class="btn btn-primario">Salvar</button><a class="btn btn-neutro" href="#/materiais">Cancelar</a></div></form>';
  }
  UI.rota('/admin/materiais/novo', function () { return formMaterial({ categoria: 'cartilha', ativo: true }); }, ADM);
  UI.rota('/admin/materiais/:id/editar', function (p) { return formMaterial(S.porId('materiais_apoio', p.id)); }, ADM);
  UI.forms.salvarMaterial = function (d, f) {
    var arq = f.querySelector('input[type=file]').files[0], m = d.id ? S.porId('materiais_apoio', d.id) : null;
    if (!arq && !d.url_externa && !(m && m.arquivo_nome)) S.erro('Envie um arquivo ou informe um link externo.');
    if (arq && arq.size > 2097152) S.erro('Na demonstração o arquivo deve ter até 2 MB.');
    var gravar = function (conteudo) {
      m = m || S.inserir('materiais_apoio', {});
      Object.assign(m, { titulo: d.titulo, categoria: d.categoria, descricao: d.descricao, url_externa: d.url_externa || null, ativo: d.ativo === '1' });
      if (conteudo) { m.conteudo = conteudo; m.arquivo_nome = arq.name; }
      S.salvar(); voltar('Material de apoio salvo.', '#/materiais');
    };
    if (!arq) return gravar(null);
    var r = new FileReader(); r.onload = function () { gravar(r.result); }; r.readAsDataURL(arq);
  };
})();
