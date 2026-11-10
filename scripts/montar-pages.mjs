// Monta o site estático do GitHub Pages:
//   _site/            ← demo/ (protótipo navegável)
//   _site/css, vendor ← mesmos assets da aplicação Laravel (public/)
//   _site/docs/       ← docs/*.md convertidos para HTML
// Uso: node scripts/montar-pages.mjs [_site]
import { cpSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const raiz = join(dirname(fileURLToPath(import.meta.url)), '..');
const saida = join(raiz, process.argv[2] || '_site');

let marked;
try { ({ marked } = await import('marked')); } catch {
  console.error('Pacote "marked" não encontrado. Rode: npm install --no-save marked@12.0.2');
  process.exit(1);
}

rmSync(saida, { recursive: true, force: true });
mkdirSync(saida, { recursive: true });
cpSync(join(raiz, 'demo'), saida, { recursive: true });
mkdirSync(join(saida, 'css'), { recursive: true });
mkdirSync(join(saida, 'vendor'), { recursive: true });
cpSync(join(raiz, 'public/css/sdgt.css'), join(saida, 'css/sdgt.css'));
cpSync(join(raiz, 'public/vendor/chart.umd.min.js'), join(saida, 'vendor/chart.umd.min.js'));
if (!existsSync(join(saida, 'data/seed.json'))) {
  console.error('ATENÇÃO: demo/data/seed.json ausente. Gere com: php artisan sdgt:exportar-demo demo/data/seed.json');
}

const docs = join(raiz, 'docs');
const destDocs = join(saida, 'docs');
mkdirSync(destDocs, { recursive: true });
const arquivos = readdirSync(docs).filter((f) => f.endsWith('.md')).sort();
const titulo = (md) => (md.match(/^#\s+(.+)$/m) || [, 'Documento'])[1];
const estilo = `
  body{background:#fff} .doc{max-width:980px;margin:0 auto;padding:24px 20px 64px}
  .doc h1{font-size:var(--fs-xl);border-bottom:3px solid var(--azul);padding-bottom:8px}
  .doc h2{margin-top:32px;border-bottom:1px solid var(--cinza-200);padding-bottom:4px} .doc h3{margin-top:24px}
  .doc table{border-collapse:collapse;width:100%;margin:12px 0;font-size:var(--fs-sm);display:block;overflow-x:auto}
  .doc th,.doc td{border:1px solid var(--cinza-200);padding:6px 10px;text-align:left;vertical-align:top} .doc th{background:var(--azul-50);color:var(--azul-escuro)}
  .doc code{background:var(--cinza-100);padding:1px 5px;border-radius:4px;font-size:.92em}
  .doc pre{background:#0d2238;color:#e8f1fa;padding:14px;border-radius:8px;overflow-x:auto} .doc pre code{background:none;color:inherit}
  .doc blockquote{border-left:4px solid var(--azul);background:var(--azul-50);margin:12px 0;padding:8px 14px}
  .navdocs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;font-size:var(--fs-xs)} .navdocs a{padding:4px 10px;border:1px solid var(--azul-100);border-radius:999px;text-decoration:none}
  .navdocs a.ativo{background:var(--azul);color:#fff}`;
const nav = (atual) => '<nav class="navdocs"><a href="./">Índice</a>' + arquivos.filter((f) => f !== 'README.md')
  .map((f) => `<a href="${f.replace(/\.md$/, '.html')}"${f === atual ? ' class="ativo"' : ''}>${titulo(readFileSync(join(docs, f), 'utf8')).replace(/^\d+\s*[—-]\s*/, '')}</a>`).join('') + '<a href="../">Demonstração ›</a></nav>';
const pagina = (t, corpo, atual) => `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>${t} · SDGT</title><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap"><link rel="stylesheet" href="../css/sdgt.css"><style>${estilo}</style></head>
<body><header class="topo"><a href="../" class="marca"><span class="brasao">TCETO</span><span class="sistema" style="border:0;padding:0">SDGT — Documentação</span></a></header>
<main class="doc">${nav(atual)}${corpo}</main>
<script type="module">
  const blocos = document.querySelectorAll('code.language-mermaid');
  if (blocos.length) {
    const { default: mermaid } = await import('https://cdn.jsdelivr.net/npm/mermaid@10.9.1/dist/mermaid.esm.min.mjs');
    blocos.forEach((c) => { const d = document.createElement('div'); d.className = 'mermaid'; d.textContent = c.textContent; c.parentElement.replaceWith(d); });
    mermaid.initialize({ startOnLoad: false, theme: 'neutral' });
    await mermaid.run();
  }
</script></body></html>`;

for (const f of arquivos) {
  const md = readFileSync(join(docs, f), 'utf8').replace(/\]\((\.\/)?([\w-]+)\.md(#[\w-]+)?\)/g, (_, __, n, a) => `](${n === 'README' ? 'index' : n}.html${a || ''})`);
  const nome = f === 'README.md' ? 'index.html' : f.replace(/\.md$/, '.html');
  writeFileSync(join(destDocs, nome), pagina(titulo(md), marked.parse(md), f));
}
writeFileSync(join(saida, '.nojekyll'), '');
console.log(`Site montado em ${saida} (${arquivos.length} documentos).`);
