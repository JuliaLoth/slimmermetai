<?php

namespace App\Infrastructure\Security;

use App\Infrastructure\Config\Config;

final class JwtService
{
    private string $secret;
    private int $expiration;
    private string $algo = 'HS256';
/**
     * JwtService constructor.
     * De configuratie wordt via Dependency Injection meegegeven zodat er geen
     * statische aanroepen meer nodig zijn.
     */
    public function __construct(Config $config, ?string $secret = null, ?int $expiration = null)
    {
        $this->secret     = $secret ?? $config->get('jwt_secret', 'change_me');
        $this->expiration = $expiration ?? $config->get('jwt_expiration', 3600);
    }

    public function generate(array $payload, ?int $exp = null): string
    {
        $header = $this->base64url(json_encode(['typ' => 'JWT', 'alg' => $this->algo]));
        $expTime = time() + ($exp ?? $this->expiration);
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => $expTime,
            'nbf' => time(),
            'jti' => bin2hex(random_bytes(8)),
        ]);
        $payloadEnc = $this->base64url(json_encode($payload));
        $signature = $this->sign("$header.$payloadEnc");
        return "$header.$payloadEnc.$signature";
    }

    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$h, $p, $s] = $parts;
        $calcSig = $this->sign("$h.$p");
        if (!hash_equals($calcSig, $s)) {
            return null;
        }
        $payload = json_decode($this->base64urlDecode($p), true);
        if (!$payload) {
            return null;
        }
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return null;
        }
        return $payload;
    }

    private function sign(string $data): string
    {
        return $this->base64url(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
