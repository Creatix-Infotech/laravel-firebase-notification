<?php

namespace Creatix\AppNotificationManager;

use Creatix\AppNotificationManager\Contracts\NotificationDriverInterface;
use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Exceptions\DeviceTokenException;
use Creatix\AppNotificationManager\Messages\AppNotificationMessage;
use Creatix\AppNotificationManager\Responses\BatchPushResponse;
use Creatix\AppNotificationManager\Responses\PushResponse;

class PendingNotification
{
    protected array $recipients = []; // list of ['token' => string, 'platform' => string]
    protected ?NotificationMessageInterface $message = null;

    public function __construct(
        protected NotificationManager $manager,
        array|string $tokens = [],
        ?string $platform = null
    ) {
        $this->addRecipients($tokens, $platform);
    }

    /**
     * Add recipient token(s) with optional platform hint.
     */
    public function addRecipients(array|string $tokens, ?string $platform = null): static
    {
        $tokens = is_array($tokens) ? $tokens : [$tokens];

        foreach ($tokens as $item) {
            if (is_array($item)) {
                $token = $item['token'] ?? ($item['device_token'] ?? null);
                $plat = $item['platform'] ?? $platform;
                if ($token) {
                    $this->recipients[] = [
                        'token' => $token,
                        'platform' => $plat ?? $this->manager->detectPlatform($token),
                    ];
                }
            } elseif (is_string($item) && ! empty(trim($item))) {
                $token = trim($item);
                $this->recipients[] = [
                    'token' => $token,
                    'platform' => $platform ?? $this->manager->detectPlatform($token),
                ];
            }
        }

        return $this;
    }

    /**
     * Set the message to be sent.
     */
    public function message(NotificationMessageInterface $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Send the notification.
     *
     * @return BatchPushResponse|PushResponse
     * @throws DeviceTokenException
     */
    public function send(?NotificationMessageInterface $message = null): BatchPushResponse|PushResponse
    {
        $messageToDeliver = $message ?? $this->message;

        if (! $messageToDeliver) {
            $messageToDeliver = new AppNotificationMessage();
        }

        if (empty($this->recipients)) {
            throw DeviceTokenException::missingTokens();
        }

        $batchResponse = new BatchPushResponse();

        foreach ($this->recipients as $recipient) {
            $token = $recipient['token'];
            $platform = strtolower($recipient['platform'] ?? 'fcm');

            $driverName = match ($platform) {
                'ios', 'apns', 'apple' => 'apns',
                default => 'fcm',
            };

            $driver = $this->manager->driver($driverName);
            $response = $driver->send($token, $messageToDeliver);
            $batchResponse->add($response);
        }

        // If only 1 recipient was targeted, return the single PushResponse for simplicity
        if (count($this->recipients) === 1) {
            return $batchResponse->all()[0];
        }

        return $batchResponse;
    }
}

