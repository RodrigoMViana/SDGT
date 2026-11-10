@if (session('sucesso'))
    <div class="alerta sucesso" role="status">@icon('check') <div>{{ session('sucesso') }}</div></div>
@endif
@if (session('aviso'))
    <div class="alerta aviso" role="status">@icon('alerta') <div>{{ session('aviso') }}</div></div>
@endif
@if (session('erro'))
    <div class="alerta erro" role="alert">@icon('alerta') <div>{{ session('erro') }}</div></div>
@endif
@if ($errors->any())
    <div class="alerta erro" role="alert">@icon('alerta')
        <div><strong>Verifique os itens abaixo:</strong>
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    </div>
@endif
