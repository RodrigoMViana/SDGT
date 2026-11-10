/* SDGT — comportamento de interface (sem build/frameworks). */
(function () {
  'use strict';

  // Menu lateral (mobile)
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-menu]');
    if (b) { document.body.classList.toggle('menu-aberto'); return; }
    if (document.body.classList.contains('menu-aberto') && !e.target.closest('.lateral')) {
      document.body.classList.remove('menu-aberto');
    }
  });

  // Confirmação em formulários/botões (componente 5.8 "Você deseja realmente...?")
  document.addEventListener('submit', function (e) {
    var f = e.target;
    var msg = f.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
    var principal = document.getElementById('form-preenchimento');
    // Anexar/excluir evidência com respostas não salvas: salva as respostas antes (evita perda de dados)
    if (f.hasAttribute('data-upload') && principal && window.__sdgtSujo) {
      e.preventDefault();
      window.__sdgtSujo = false;
      fetch(principal.action, { method: 'POST', body: new FormData(principal), credentials: 'same-origin' })
        .catch(function () {}).then(function () { HTMLFormElement.prototype.submit.call(f); });
      return;
    }
    if (f.hasAttribute('data-sujo')) { window.__sdgtSujo = false; }
  });

  // Modais (<dialog>)
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-abrir-modal]');
    if (a) { e.preventDefault(); var d = document.getElementById(a.getAttribute('data-abrir-modal')); if (d) d.showModal(); }
    var c = e.target.closest('[data-fechar-modal]');
    if (c) { e.preventDefault(); c.closest('dialog').close(); }
  });

  // Perguntas condicionais: permanecem visíveis, mas só habilitam quando a condição é atendida
  // (decisão da reunião de 06/11/2026, item 1.2).
  function marcadas(pergId) {
    var ids = [];
    document.querySelectorAll('[name="respostas[' + pergId + '][opcao]"]:checked, [name="respostas[' + pergId + '][opcoes][]"]:checked')
      .forEach(function (i) { ids.push(i.value); });
    return ids;
  }
  function atualizarDependencias() {
    var mudou = true, voltas = 0;
    while (mudou && voltas++ < 10) {
      mudou = false;
      document.querySelectorAll('.pergunta[data-pai]').forEach(function (div) {
        var pai = div.getAttribute('data-pai');
        var habil = (div.getAttribute('data-habilitadoras') || '').split(',');
        var paiDiv = document.getElementById('pergunta-' + pai);
        var paiOk = !paiDiv || !paiDiv.classList.contains('desabilitada');
        var ok = paiOk && marcadas(pai).some(function (id) { return habil.indexOf(id) >= 0; });
        var antes = !div.classList.contains('desabilitada');
        div.classList.toggle('desabilitada', !ok);
        div.querySelectorAll('.opcoes input, .opcoes textarea, .opcoes select').forEach(function (i) {
          i.disabled = !ok;
          if (!ok) { if (i.type === 'radio' || i.type === 'checkbox') i.checked = false; else i.value = ''; }
        });
        var aviso = div.querySelector('.condicao');
        if (aviso) aviso.setAttribute('data-ok', ok ? '1' : '0');
        if (antes !== ok) mudou = true;
      });
    }
  }
  var formQ = document.getElementById('form-preenchimento');
  if (formQ) {
    atualizarDependencias();
    formQ.addEventListener('change', function () { atualizarDependencias(); window.__sdgtSujo = true; });
    formQ.addEventListener('input', function () { window.__sdgtSujo = true; });
    window.addEventListener('beforeunload', function (e) {
      if (window.__sdgtSujo) { e.preventDefault(); e.returnValue = ''; }
    });
  }

  // Seleção "todos" em listas de checkboxes
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches('[data-marcar-todos]')) {
      document.querySelectorAll(t.getAttribute('data-marcar-todos')).forEach(function (c) { c.checked = t.checked; });
    }
    if (t.matches('[data-abrangencia]')) {
      var box = document.getElementById('box-municipios');
      if (box) box.hidden = t.value !== 'selecionados';
    }
    if (t.matches('select[data-auto-submit]')) { t.form.submit(); }
  });
})();

/* Paleta dos gráficos — derivada do guia de estilo */
window.SDGT_CORES = {
  azul: '#0066B0', azulEscuro: '#00467D', azulClaro: '#70C3FF', verde: '#44A12B', vermelho: '#DC332E', amarelo: '#FFCA28',
  serie: ['#0066B0', '#44A12B', '#FF8F00', '#931D19', '#33AAFF', '#B39409', '#707070'],
  faixa: { baixa: '#DC332E', media: '#FFCA28', alta: '#44A12B' }
};
if (window.Chart) {
  Chart.defaults.font.family = "'Montserrat', system-ui, sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#2b2f33';
  Chart.defaults.plugins.legend.labels.boxWidth = 12;
  Chart.defaults.maintainAspectRatio = false;
}
