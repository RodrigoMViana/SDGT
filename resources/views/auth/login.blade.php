<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso · SDGT — {{ config('sdgt.orgao.sigla') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/sdgt.css') }}?v=1">
</head>
<body>
<div class="pagina-login">
    <section class="login-arte">
        <div>
            <div style="font-weight:700;opacity:.9">{{ config('sdgt.orgao.nome') }}</div>
        </div>
        <div>
            <h1>Sistema de {{ config('sdgt.orgao.sistema') }}</h1>
            <p>Acompanhamento da adequação dos municípios jurisdicionados às exigências da Reforma Tributária, por meio de questionários eletrônicos, evidências e indicadores.</p>
            <div class="lista-dimensoes">
                <span>Legislação Tributária</span><span>Recursos Humanos</span><span>Infraestrutura e TI</span>
                <span>Fiscalização</span><span>Cobrança e Contabilização</span><span>Governança da Transição</span>
            </div>
        </div>
        <small style="opacity:.8">EC nº 132/2023 · LC nº 214/2025</small>
    </section>
    <section class="login-form">
        <h2>Acesse o sistema</h2>
        @include('partials.alertas')

        <p class="muted" style="margin-bottom:8px"><strong>Municípios</strong> (responsável e gestor municipal):</p>
        <a href="{{ route('login.govbr') }}" class="btn btn-govbr">Entrar com <strong>gov.br</strong></a>
        <div style="height:10px"></div>
        <a href="{{ route('login.certificado') }}" class="btn btn-secundario btn-cert">@icon('cert') Entrar com Certificado Digital ICP-Brasil</a>

        <div class="separador">Tribunal — usuário e senha</div>
        <form method="post" action="{{ route('login.senha') }}" class="form-grade" style="gap:12px">
            @csrf
            <div class="campo @error('usuario') com-erro @enderror">
                <label for="usuario">Usuário (e-mail ou CPF)</label>
                <input id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" autocomplete="username" required>
            </div>
            <div class="campo">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <div class="campo"><label class="check"><input type="checkbox" name="lembrar" value="1"> Manter conectado</label></div>
            <div class="campo"><button class="btn btn-primario" type="submit">Entrar</button></div>
        </form>

        @if ($simulados->isNotEmpty())
            <div class="caixa-simulado">
                <strong>@icon('alerta') Acesso simulado (homologação)</strong>
                <p class="texto-pequeno muted" style="margin:6px 0 10px">GOV.BR: <b>{{ $govbrDriver }}</b> · Certificado: <b>{{ $certDriver }}</b>. Escolha um usuário de teste para entrar sem credenciais. Desative em produção (<code>SDGT_LOGIN_SIMULADO=false</code>).</p>
                <form method="post" action="{{ route('login.simulado') }}" class="filtros">
                    @csrf
                    <div class="campo">
                        <label for="user_id" class="sr-only">Usuário de teste</label>
                        <select name="user_id" id="user_id">
                            @foreach ($simulados->groupBy(fn ($s) => $s->perfil->label()) as $perfil => $lista)
                                <optgroup label="{{ $perfil }}">
                                    @foreach ($lista as $s)<option value="{{ $s->id }}">{{ $s->name }}@if($s->municipio) ({{ $s->municipio->nome }})@endif</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="campo curto">
                        <select name="metodo" aria-label="Método simulado">
                            <option value="">Método padrão</option><option value="govbr">GOV.BR</option><option value="certificado">Certificado</option><option value="senha">Senha</option>
                        </select>
                    </div>
                    <button class="btn btn-neutro" type="submit">Entrar como</button>
                </form>
            </div>
        @endif
    </section>
</div>
</body>
</html>
