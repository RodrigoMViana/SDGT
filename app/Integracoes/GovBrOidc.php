<?php

namespace App\Integracoes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Login Único GOV.BR (OpenID Connect, fluxo Authorization Code + PKCE).
 * Documentação: https://manual-roteiro-integracao-login-unico.servicos.gov.br/
 *
 * INTEGRAÇÃO PENDENTE: requer cadastro do serviço no GOV.BR pelo TCE/TO
 * (client_id, client_secret e redirect_uri homologados em staging e produção).
 */
class GovBrOidc
{
    public function configurado(): bool
    {
        return (bool) (config('sdgt.integracoes.govbr.client_id') && config('sdgt.integracoes.govbr.redirect_uri'));
    }

    /** @return array{url: string, state: string, verifier: string, nonce: string} */
    public function iniciar(): array
    {
        if (! $this->configurado()) {
            throw new RuntimeException('GOV.BR não configurado (GOVBR_CLIENT_ID / GOVBR_REDIRECT_URI).');
        }
        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $url = $this->base().'/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('sdgt.integracoes.govbr.client_id'),
            'scope' => config('sdgt.integracoes.govbr.scopes'),
            'redirect_uri' => config('sdgt.integracoes.govbr.redirect_uri'),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        return compact('url', 'state', 'verifier', 'nonce');
    }

    /** Troca o code por token e retorna os dados do cidadão (sub = CPF). */
    public function concluir(string $code, string $verifier): array
    {
        $token = Http::asForm()
            ->withBasicAuth((string) config('sdgt.integracoes.govbr.client_id'), (string) config('sdgt.integracoes.govbr.client_secret'))
            ->post($this->base().'/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('sdgt.integracoes.govbr.redirect_uri'),
                'code_verifier' => $verifier,
            ])->throw()->json();

        $info = Http::withToken($token['access_token'])->get($this->base().'/userinfo/')->throw()->json();

        return [
            'cpf' => preg_replace('/\D/', '', (string) ($info['sub'] ?? '')),
            'nome' => $info['name'] ?? null,
            'email' => $info['email'] ?? null,
        ];
    }

    public function urlLogout(): string
    {
        return $this->base().'/logout?post_logout_redirect_uri='.urlencode(url('/login'));
    }

    private function base(): string
    {
        return rtrim((string) config('sdgt.integracoes.govbr.url_provider'), '/');
    }
}
