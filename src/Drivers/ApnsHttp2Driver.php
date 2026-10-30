<?php

namespace Creatix\AppNotificationManager\Drivers;

use Creatix\AppNotificationManager\Auth\ApnsJwtManager;
use Creatix\AppNotificationManager\Contracts\NotificationDriverInterface;
use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Events\InvalidTokenDetected;
use Creatix\AppNotificationManager\Events\NotificationFailed;
use Creatix\AppNotificationManager\Events\NotificationSent;
use Creatix\AppNotificationManager\Exceptions\InvalidConfigurationException;
use Creatix\AppNotificationManager\Responses\BatchPushResponse;
use Creatix\AppNotificationManager\Responses\PushResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class ApnsHttp2Driver implements NotificationDriverInterface
{
    protected ApnsJwtManager $jwtManager;

    public function __construct(
        protected array $config,
        ?ApnsJwtManager $jwtManager = null
    ) {
        $this->jwtManager = $jwtManager ?? new ApnsJwtManager($config);
    }

    public function getPlatform(): string
    {
        return 'apns';
    }

    public function send(string $deviceToken, NotificationMessageInterface $message): PushResponse
    {
        $bundleId = $this->config['bundle_id'] ?? null;
        if (! $bundleId) {
            throw InvalidConfigurationException::missingApnsCredentials();
        }

        $isProduction = (bool) ($this->config['production'] ?? false);
        $baseUrl = $isProduction
            ? 'https://api.push.apple.com'
            : 'https://api.sandbox.push.apple.com';

        // Normalize device token (remove spaces and angle brackets if present)
        $cleanToken = trim(str_replace([' ', '<', '>'], '', $deviceToken));
        $url = "{$baseUrl}/3/device/{$cleanToken}";

        $payload = json_encode($message->toApnsPayload());
        $customHeaders = $message->toApnsHeaders($bundleId);

        $authToken = $this->jwtManager->getToken();

        $httpHeaders = [
            "authorization: bearer {$authToken}",
            'content-type: application/json',
        ];

        foreach ($customHeaders as $key => $val) {
            $httpHeaders[] = "{$key}: {$val}";
        }

        $result = $this->executeCurlHttp2($url, $payload, $httpHeaders);

        // If provider token expired, refresh and retry once
        if ($result['status'] === 403 && str_contains($result['body'], 'ExpiredProviderToken')) {
            $authToken = $this->jwtManager->refreshToken();
            $httpHeaders[0] = "authorization: bearer {$authToken}";
            $result = $this->executeCurlHttp2($url, $payload, $httpHeaders);
        }

        $statusCode = $result['status'];
        $bodyData = json_decode($result['body'], true) ?? [];
        $apnsId = $result['apns_id'] ?? null;

        if ($statusCode === 200) {
            $pushResponse = PushResponse::success($deviceToken, $apnsId, [
                'apns_id' => $apnsId,
                'status' => 200,
            ]);
            Event::dispatch(new NotificationSent('ios', $deviceToken, $message, $pushResponse));
            $this->logInfo("APNs notification sent successfully to {$deviceToken} (ID: {$apnsId})");
            return $pushResponse;
        }

        $reason = $bodyData['reason'] ?? "APNs HTTP {$statusCode}";
        $isInvalid = in_array($reason, ['BadDeviceToken', 'Unregistered', 'DeviceTokenNotForTopic']);

        $pushResponse = PushResponse::failure(
            deviceToken: $deviceToken,
            error: $reason,
            statusCode: $statusCode,
            isInvalidToken: $isInvalid,
            raw: $bodyData
        );

        if ($isInvalid) {
            Event::dispatch(new InvalidTokenDetected('ios', $deviceToken, $reason));
        }

        Event::dispatch(new NotificationFailed('ios', $deviceToken, $message, $pushResponse));
        $this->logError("APNs delivery failed for {$deviceToken}: {$reason} (status {$statusCode})", $bodyData);

        return $pushResponse;
    }

    public function sendBatch(array $deviceTokens, NotificationMessageInterface $message): BatchPushResponse
    {
        $batchResponse = new BatchPushResponse();

        foreach ($deviceTokens as $token) {
            $batchResponse->add($this->send($token, $message));
        }

        return $batchResponse;
    }

    /**
     * Perform HTTP/2 POST request using cURL.
     */
    protected function executeCurlHttp2(string $url, string $payload, array $headers): array
    {
        $ch = curl_init();

        $responseHeaders = [];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_PORT => 443,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTP_VERSION => defined('CURL_HTTP_VERSION_2_0') ? CURL_HTTP_VERSION_2_0 : 3,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            },
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return [
                'status' => 0,
                'body' => json_encode(['reason' => "cURL error: {$error}"]),
                'apns_id' => null,
            ];
        }

        return [
            'status' => $httpCode,
            'body' => (string) $body,
            'apns_id' => $responseHeaders['apns-id'] ?? null,
        ];
    }

    protected function logInfo(string $message, array $context = []): void
    {
        if (! empty($this->config['logging']['enabled'])) {
            Log::channel($this->config['logging']['channel'] ?? 'daily')->info("[AppNotification APNs] {$message}", $context);
        }
    }

    protected function logError(string $message, array $context = []): void
    {
        if (! empty($this->config['logging']['enabled'])) {
            Log::channel($this->config['logging']['channel'] ?? 'daily')->error("[AppNotification APNs] {$message}", $context);
        }
    }
}

