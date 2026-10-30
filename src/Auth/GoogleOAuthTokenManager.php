<?php

namespace Creatix\AppNotificationManager\Auth;

use Carbon\Carbon;
use Creatix\AppNotificationManager\Exceptions\InvalidConfigurationException;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class GoogleOAuthTokenManager
{
    protected const GOOGLE_OAUTH2_TOKEN_URI = 'https://oauth2.googleapis.com/token';
    protected const FIREBASE_MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    protected Client $httpClient;

    public function __construct(
        protected array $config,
        ?Client $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? new Client(['timeout' => 10.0]);
    }

    /**
     * Retrieve a valid OAuth2 Bearer Access Token for FCM HTTP v1.
     * Caches the token for 50 minutes.
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'app_notification_fcm_oauth_token_' . md5($this->config['project_id'] ?? 'default');

        return Cache::remember($cacheKey, Carbon::now()->addMinutes(50), function () {
            return $this->fetchNewAccessToken();
        });
    }

    /**
     * Force-refresh the cached access token.
     */
    public function refreshAccessToken(): string
    {
        $cacheKey = 'app_notification_fcm_oauth_token_' . md5($this->config['project_id'] ?? 'default');
        Cache::forget($cacheKey);
        return $this->getAccessToken();
    }

    /**
     * Resolve credentials from path, raw json, or explicit config keys.
     *
     * @return array
     * @throws InvalidConfigurationException
     */
    public function resolveCredentials(): array
    {
        if (! empty($this->config['credentials_json'])) {
            $parsed = json_decode($this->config['credentials_json'], true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        if (! empty($this->config['credentials_path']) && file_exists($this->config['credentials_path'])) {
            $content = file_get_contents($this->config['credentials_path']);
            $parsed = json_decode((string) $content, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        if (! empty($this->config['client_email']) && ! empty($this->config['private_key'])) {
            return [
                'client_email' => $this->config['client_email'],
                'private_key' => str_replace('\n', "\n", $this->config['private_key']),
            ];
        }

        throw InvalidConfigurationException::missingFcmCredentials();
    }

    /**
     * Generate an assertion JWT and exchange it for a Google OAuth2 Access Token.
     */
    protected function fetchNewAccessToken(): string
    {
        $credentials = $this->resolveCredentials();
        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;

        if (! $clientEmail || ! $privateKey) {
            throw InvalidConfigurationException::missingFcmCredentials();
        }

        $now = time();
        $payload = [
            'iss' => $clientEmail,
            'scope' => self::FIREBASE_MESSAGING_SCOPE,
            'aud' => self::GOOGLE_OAUTH2_TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        // Format private key properly
        $privateKey = str_replace('\n', "\n", (string) $privateKey);

        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        $response = $this->httpClient->post(self::GOOGLE_OAUTH2_TOKEN_URI, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
            'http_errors' => false,
        ]);

        $body = json_decode((string) $response->getBody(), true);

        if ($response->getStatusCode() !== 200 || empty($body['access_token'])) {
            $error = $body['error_description'] ?? ($body['error'] ?? 'OAuth authorization failed');
            throw new InvalidConfigurationException("Failed to obtain FCM OAuth2 token: {$error}");
        }

        return $body['access_token'];
    }
}
