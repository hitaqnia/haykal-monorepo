<?php

declare(strict_types=1);

namespace Hitaqnia\Haykal\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

final class FakeHuwiyaIdPTest extends TestCase
{
    public function test_issues_a_signed_token_whose_signature_verifies_against_the_public_key(): void
    {
        $idp = new FakeHuwiyaIdP;

        $token = $idp->issueToken(['sub' => '01HX1234567890ABCDEFGHJKMN']);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        $signatureInput = $parts[0].'.'.$parts[1];
        $signature = FakeHuwiyaIdP::base64UrlDecode($parts[2]);
        $publicKey = openssl_pkey_get_public($idp->publicKey);

        $this->assertSame(
            1,
            openssl_verify($signatureInput, $signature, $publicKey, OPENSSL_ALGO_SHA256),
            'JWT signature must verify against the IdP public key.',
        );
    }

    public function test_publishes_jwks_with_matching_kid_and_rsa_metadata(): void
    {
        $idp = new FakeHuwiyaIdP;

        $jwks = $idp->jwks();

        $this->assertArrayHasKey('keys', $jwks);
        $this->assertCount(1, $jwks['keys']);

        $key = $jwks['keys'][0];
        $this->assertSame('RSA', $key['kty']);
        $this->assertSame('RS256', $key['alg']);
        $this->assertSame('sig', $key['use']);
        $this->assertSame($idp->kid, $key['kid']);
        $this->assertNotEmpty($key['n']);
        $this->assertNotEmpty($key['e']);
    }

    public function test_issued_token_header_includes_kid_matching_jwks(): void
    {
        $idp = new FakeHuwiyaIdP;

        $token = $idp->issueToken();

        [$headerSegment] = explode('.', $token);
        $header = json_decode(FakeHuwiyaIdP::base64UrlDecode($headerSegment), true);

        $this->assertSame('RS256', $header['alg']);
        $this->assertSame($idp->kid, $header['kid']);
    }

    public function test_claims_default_to_configured_issuer_and_project_id(): void
    {
        $idp = new FakeHuwiyaIdP('https://idp.example', 'project-xyz');

        $token = $idp->issueToken();

        [, $payloadSegment] = explode('.', $token);
        $claims = json_decode(FakeHuwiyaIdP::base64UrlDecode($payloadSegment), true);

        $this->assertSame('https://idp.example', $claims['iss']);
        $this->assertSame('project-xyz', $claims['aud']);
        $this->assertGreaterThan(time() - 5, $claims['iat']);
        $this->assertGreaterThan(time(), $claims['exp']);
    }

    public function test_jwks_uri_uses_huwiya_convention(): void
    {
        $idp = new FakeHuwiyaIdP('https://idp.example/', 'project-xyz');

        $this->assertSame(
            'https://idp.example/project-xyz/.well-known/jwks.json',
            $idp->jwksUri(),
        );
    }
}
