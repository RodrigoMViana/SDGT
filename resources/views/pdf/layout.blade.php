<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>@yield('titulo')</title>
<style>
    @page { margin: 90px 36px 60px 36px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #2b2f33; }
    header { position: fixed; top: -72px; left: 0; right: 0; height: 56px; border-bottom: 3px solid #0066B0; }
    header .orgao { font-size: 8pt; color: #707070; }
    header .sistema { font-size: 11pt; font-weight: bold; color: #00467D; }
    footer { position: fixed; bottom: -44px; left: 0; right: 0; height: 30px; font-size: 7.5pt; color: #898989; border-top: 1px solid #EAEAEA; padding-top: 4px; }
    footer .pagina:after { content: "Página " counter(page); }
    h1 { font-size: 14pt; color: #00467D; margin: 0 0 6px; }
    h2 { font-size: 11pt; color: #fff; background: #0066B0; padding: 5px 8px; margin: 14px 0 0; }
    h3 { font-size: 10pt; color: #00467D; margin: 12px 0 4px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #EBF7FF; color: #00467D; text-align: left; padding: 5px; font-size: 8pt; border-bottom: 1.5px solid #ADDDFF; }
    td { padding: 5px; border-bottom: 1px solid #EAEAEA; vertical-align: top; font-size: 8.5pt; }
    .num { text-align: right; }
    .muted { color: #707070; }
    .pergunta { padding: 6px 8px; border: 1px solid #EAEAEA; border-top: 0; page-break-inside: avoid; }
    .codigo { background: #D6EDFF; color: #00467D; padding: 1px 4px; font-weight: bold; font-size: 8pt; }
    .resposta { background: #F5F6F8; border-left: 3px solid #0066B0; padding: 4px 6px; margin-top: 4px; }
    .na { color: #898989; font-style: italic; }
    .caixa { border: 1px solid #ADDDFF; background: #EBF7FF; padding: 8px; margin: 8px 0; }
    .baixa { color: #931D19; } .media { color: #8a6500; } .alta { color: #2E7D32; }
    .dl td:first-child { width: 32%; font-weight: bold; color: #707070; }
</style>
</head>
<body>
<header>
    <div class="orgao">{{ config('sdgt.orgao.nome') }}</div>
    <div class="sistema">Sistema de {{ config('sdgt.orgao.sistema') }}</div>
</header>
<footer>
    <span class="pagina"></span> · Gerado em {{ ($geradoEm ?? now())->format('d/m/Y H:i') }}@if(! empty($geradoPor)) por {{ $geradoPor }}@endif
</footer>
<main>@yield('conteudo')</main>
</body>
</html>
