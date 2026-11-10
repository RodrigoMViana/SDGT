<!doctype html>
<html lang="pt-BR">
<body style="margin:0;background:#F5F6F8;font-family:Montserrat,Arial,sans-serif;color:#2b2f33">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F5F6F8;padding:24px 0">
    <tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;max-width:600px">
            <tr><td style="background:#00467D;color:#fff;padding:18px 24px">
                <div style="font-size:12px;opacity:.85">{{ config('sdgt.orgao.nome') }}</div>
                <div style="font-size:16px;font-weight:bold">Sistema de {{ config('sdgt.orgao.sistema') }}</div>
            </td></tr>
            <tr><td style="padding:24px">
                <p style="margin:0 0 8px">Prezado(a) {{ $notificacao->user->name }},</p>
                <h2 style="font-size:18px;color:#00467D;margin:12px 0">{{ $notificacao->titulo }}</h2>
                <p style="line-height:1.6;white-space:pre-line">{{ $notificacao->mensagem }}</p>
                @if ($notificacao->link)
                    <p style="margin:24px 0"><a href="{{ $notificacao->link }}" style="background:#0066B0;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:bold">Acessar o sistema</a></p>
                @endif
                <p style="font-size:12px;color:#707070">Esta é uma mensagem automática. Não responda este e-mail.</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
