<?php

namespace Creatix\AppNotificationManager\Tests\Unit;

use Creatix\AppNotificationManager\Auth\ApnsJwtManager;
use Creatix\AppNotificationManager\Tests\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtManagerTest extends TestCase
{
    protected function getOpensslConfig(): array
    {
        $cnf = 'C:/wamp64/bin/php/php8.5.0/extras/ssl/openssl.cnf';
        return file_exists($cnf) ? ['config' => $cnf] : [];
    }

    public function test_apns_jwt_token_generation_with_ec_key(): void
    {
        $cnf = $this->getOpensslConfig();
        $ec = openssl_pkey_new(array_merge([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ], $cnf));
        openssl_pkey_export($ec, $ecPrivateKey, null, $cnf);

        $manager = new ApnsJwtManager([
            'key_id' => 'ABC1234567',
            'team_id' => 'TEAM123456',
            'private_key_content' => $ecPrivateKey,
        ]);

        $token = $manager->getToken();
        $this->assertNotEmpty($token);

        // Verify with public key
        $details = openssl_pkey_get_details($ec);
        $publicKey = $details['key'];

        $decoded = JWT::decode($token, new Key($publicKey, 'ES256'));
        $this->assertEquals('TEAM123456', $decoded->iss);
    }

    public function test_google_assertion_jwt_encoding_with_rsa_key(): void
    {
        $cnf = $this->getOpensslConfig();
        $rsa = openssl_pkey_new(array_merge([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ], $cnf));
        openssl_pkey_export($rsa, $rsaPrivateKey, null, $cnf);

        $payload = [
            'iss' => 'test-service-account@example.iam.gserviceaccount.com',
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $jwt = JWT::encode($payload, $rsaPrivateKey, 'RS256');
        $this->assertNotEmpty($jwt);

        $details = openssl_pkey_get_details($rsa);
        $publicKey = $details['key'];

        $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));
        $this->assertEquals('test-service-account@example.iam.gserviceaccount.com', $decoded->iss);
    }
}
