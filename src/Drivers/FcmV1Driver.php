<?php

namespace Creatix\AppNotificationManager\Drivers;

use Creatix\AppNotificationManager\Auth\GoogleOAuthTokenManager;
use Creatix\AppNotificationManager\Contracts\NotificationDriverInterface;
use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Events\InvalidTokenDetected;
use Creatix\AppNotificationManager\Events\NotificationFailed;
use Creatix\AppNotificationManager\Events\NotificationSent;
use Creatix\AppNotificationManager\Exceptions\InvalidConfigurationException;
use Creatix\AppNotificationManager\Responses\BatchPushResponse;
use Creatix\AppNotificationManager\Responses\PushResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class FcmV1Driver implements NotificationDriverInterface
{
    protected Client $httpClient;
    protected GoogleOAuthTokenManager $tokenManager;

    public function __construct(
        protected array $config,
        ?GoogleOAuthTokenManager $tokenManager = null,
        ?Client $httpClient = null
    ) {
        $this->tokenManager = $tokenManager ?? new GoogleOAuthTokenManager($config);
        $this->httpClient = $httpClient ?? new Client(['timeout' => 15.0]);
    }

    public function getPlatform(): string
    {
        return 'fcm';
    }

    public function send(string $deviceToken, NotificationMessageInterface $message): PushResponse
    {
        $projectId = $this->config['project_id'] ?? null;
        if (! $projectId) {
            throw InvalidConfigurationException::missingFcmProjectId();
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $payload = $message->toFcmPayload($deviceToken);

        $verify = $this->config['ssl_verify'] ?? true;

        try {
            $accessToken = $this->tokenManager->getAccessToken();

            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json; UTF-8',
                ],
                'json' => $payload,
                'verify' => $verify,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true) ?? [];

            // If token expired, refresh and retry once
            if ($statusCode === 401) {
                $refreshedToken = $this->tokenManager->refreshAccessToken();
                $response = $this->httpClient->post($url, [
                    'headers' => [
                        'Authorization' => "Bearer {$refreshedToken}",
                        'Content-Type' => 'application/json; UTF-8',
                    ],
                    'json' => $payload,
                    'verify' => $verify,
                    'http_errors' => false,
                ]);
                $statusCode = $response->getStatusCode();
                $body = json_decode((string) $response->getBody(), true) ?? [];
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                $messageId = $body['name'] ?? null;
                $pushResponse = PushResponse::success($deviceToken, $messageId, $body);
                Event::dispatch(new NotificationSent('android', $deviceToken, $message, $pushResponse));
                $this->logInfo("FCM notification sent successfully to {$deviceToken}", $body);
                return $pushResponse;
            }

            $errorMessage = $body['error']['message'] ?? "FCM HTTP {$statusCode}";
            $errorCode = $body['error']['details'][0]['errorCode'] ?? ($body['error']['status'] ?? '');

            $isInvalid = in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])
                || str_contains(strtolower($errorMessage), 'not registered')
                || str_contains(strtolower($errorMessage), 'invalid registration');

            $pushResponse = PushResponse::failure(
                deviceToken: $deviceToken,
                error: $errorMessage,
                statusCode: $statusCode,
                isInvalidToken: $isInvalid,
                raw: $body
            );

            if ($isInvalid) {
                Event::dispatch(new InvalidTokenDetected('android', $deviceToken, $errorMessage));
            }

            Event::dispatch(new NotificationFailed('android', $deviceToken, $message, $pushResponse));
            $this->logError("FCM notification delivery failed for {$deviceToken}: {$errorMessage}", $body);

            return $pushResponse;
        } catch (RequestException $e) {
            $pushResponse = PushResponse::failure(
                deviceToken: $deviceToken,
                error: $e->getMessage(),
                statusCode: $e->getCode()
            );

            Event::dispatch(new NotificationFailed('android', $deviceToken, $message, $pushResponse));
            $this->logError("FCM request exception: {$e->getMessage()}");

            return $pushResponse;
        }
    }

    public function sendBatch(array $deviceTokens, NotificationMessageInterface $message): BatchPushResponse
    {
        $batchResponse = new BatchPushResponse();

        foreach ($deviceTokens as $token) {
            $batchResponse->add($this->send($token, $message));
        }

        return $batchResponse;
    }

    protected function logInfo(string $message, array $context = []): void
    {
        if (! empty($this->config['logging']['enabled'])) {
            Log::info("[AppNotification FCM] {$message}", $context);
        }
    }

    protected function logError(string $message, array $context = []): void
    {
        if (! empty($this->config['logging']['enabled'])) {
            Log::error("[AppNotification FCM] {$message}", $context);
        }
    }
}

