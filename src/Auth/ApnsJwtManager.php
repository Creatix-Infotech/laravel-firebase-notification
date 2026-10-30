<?php

namespace Creatix\AppNotificationManager\Auth;

use Creatix\AppNotificationManager\Exceptions\InvalidConfigurationException;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;

class ApnsJwtManager
{
    public function __construct(
        protected array $config
    ) {}

    /**
     * Retrieve a signed JWT token for APNs HTTP/2 authentication.
     * Caches the token for 50 minutes.
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getToken(): string
    {
        $keyId = $this->config['key_id'] ?? null;
        $teamId = $this->config['team_id'] ?? null;

        if (! $keyId || ! $teamId) {
            throw InvalidConfigurationException::missingApnsCredentials();
        }

        $cacheKey = 'app_notification_apns_jwt_' . md5("{$keyId}_{$teamId}");

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            return $this->generateToken();
        });
    }

    /**
     * Force refresh the APNs JWT token.
     */
    public function refreshToken(): string
    {
        $keyId = $this->config['key_id'] ?? null;
        $teamId = $this->config['team_id'] ?? null;
        $cacheKey = 'app_notification_apns_jwt_' . md5("{$keyId}_{$teamId}");

        Cache::forget($cacheKey);
        return $this->getToken();
    }

    /**
     * Resolve the .p8 private key content.
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    public function resolvePrivateKey(): string
    {
        if (! empty($this->config['private_key_content'])) {
            return str_replace('\n', "\n", $this->config['private_key_content']);
        }

        if (! empty($this->config['private_key_path']) && file_exists($this->config['private_key_path'])) {
            return file_get_contents($this->config['private_key_path']);
        }

        throw InvalidConfigurationException::missingApnsCredentials();
    }

    /**
     * Generate an ES256 signed JWT token for APNs.
     */
    protected function generateToken(): string
    {
        $keyId = $this->config['key_id'];
        $teamId = $this->config['team_id'];
        $privateKey = $this->resolvePrivateKey();

        $payload = [
            'iss' => $teamId,
            'iat' => time(),
        ];

        return JWT::encode($payload, $privateKey, 'ES256', $keyId);
    }
}

