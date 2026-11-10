<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · SDGT</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/sdgt.css') }}">
</head>
<body>
<header class="topo"><a href="{{ url('/') }}" class="marca"><span class="sistema" style="border:0;padding:0">{{ config('sdgt.orgao.sistema') }}</span></a></header>
<main class="conteudo" style="margin:40px auto;max-width:640px;text-align:center">
    <div class="card">
        <div style="font-size:var(--fs-xxl);font-weight:800;color:var(--azul)">@yield('code')</div>
        <h2>@yield('message')</h2>
        <p class="muted">{{ $exception?->getMessage() && ! in_array($exception->getMessage(), ['', 'Not Found', 'Forbidden', 'This action is unauthorized.']) ? $exception->getMessage() : '' }}</p>
        <a href="{{ url('/') }}" class="btn btn-primario">Voltar ao início</a>
    </div>
</main>
</body>
</html>
