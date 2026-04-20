<?php

declare(strict_types=1);

namespace Hitaqnia\Haykal\Tests\Fixtures;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * In-process stand-in for the Huwiya IdP used in tests.
 *
 * Generates an RSA keypair, exposes a JWKS payload matching the public key,
 * and issues signed JWTs that pass Huwiya's RS256 verification. Mirrors the
 * signing/verification shape used by `hitaqnia/huwiya-laravel` (RS256 over
 * SHA-256 via OpenSSL, RSA `n`/`e` in base64url) so tokens issued here are
 * accepted by the real SDK without any special-casing.
 */
final class FakeHuwiyaIdP
{
    public const DEFAULT_ISSUER = 'https://huwiya.test';

    public const DEFAULT_PROJECT_ID = 'test-project';

    public readonly string $kid;

    public readonly string $privateKey;

    public readonly string $publicKey;

    public function __construct(
        public readonly string $issuer = self::DEFAULT_ISSUER,
        public readonly string $projectId = self::DEFAULT_PROJECT_ID,
        ?string $kid = null,
    ) {
        $this->kid = $kid ?? 'test-kid-'.bin2hex(random_bytes(4));

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new RuntimeException('Failed to generate RSA keypair for FakeHuwiyaIdP.');
        }

        $privateKey = '';
        openssl_pkey_export($resource, $privateKey);
        $this->privateKey = $privateKey;

        $details = openssl_pkey_get_details($resource);
        $this->publicKey = $details['key'];
    }

    /**
     * Build the JWKS payload this IdP publishes.
     *
     * @return array{keys: list<array{kty: string, kid: string, use: string, alg: string, n: string, e: string}>}
     */
    public function jwks(): array
    {
        $publicResource = openssl_pkey_get_public($this->publicKey);
        $details = openssl_pkey_get_details($publicResource);

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'kid' => $this->kid,
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => self::base64UrlEncode($details['rsa']['n']),
                    'e' => self::base64UrlEncode($details['rsa']['e']),
                ],
            ],
        ];
    }

    /**
     * JWKS URI matching the pattern Huwiya constructs from url + project_id.
     */
    public function jwksUri(): string
    {
        return rtrim($this->issuer, '/').'/'.$this->projectId.'/.well-known/jwks.json';
    }

    /**
     * Register a fake HTTP response so the Huwiya SDK finds this IdP's keys
     * when it fetches the JWKS. Caller is responsible for configuring the
     * SDK to point at the matching `huwiya.url` / `huwiya.project_id`.
     */
    public function fakeJwksEndpoint(): void
    {
        Http::fake([
            $this->jwksUri() => Http::response($this->jwks(), 200),
        ]);
    }

    /**
     * Issue a signed JWT with the given claims. Sensible defaults are
     * applied for iss, aud, iat, exp — callers override per test need.
     *
     * @param  array<string, mixed>  $claims
     */
    public function issueToken(array $claims = []): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'RS256',
            'kid' => $this->kid,
        ];

        $now = time();
        $payload = array_merge([
            'iss' => $this->issuer,
            'aud' => $this->projectId,
            'iat' => $now,
            'exp' => $now + 3600,
        ], $claims);

        $headerSegment = self::base64UrlEncode((string) json_encode($header));
        $payloadSegment = self::base64UrlEncode((string) json_encode($payload));
        $signatureInput = $headerSegment.'.'.$payloadSegment;

        $privateKey = openssl_pkey_get_private($this->privateKey);
        $signature = '';
        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $signatureInput.'.'.self::base64UrlEncode($signature);
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url input.');
        }

        return $decoded;
    }
}
