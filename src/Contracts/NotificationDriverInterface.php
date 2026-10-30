<?php

namespace Creatix\AppNotificationManager\Contracts;

use Creatix\AppNotificationManager\Responses\PushResponse;
use Creatix\AppNotificationManager\Responses\BatchPushResponse;

interface NotificationDriverInterface
{
    /**
     * Send a notification message to a single device token.
     *
     * @param string $deviceToken
     * @param NotificationMessageInterface $message
     * @return PushResponse
     */
    public function send(string $deviceToken, NotificationMessageInterface $message): PushResponse;

    /**
     * Send a notification message to multiple device tokens (multicast/batch).
     *
     * @param array<string> $deviceTokens
     * @param NotificationMessageInterface $message
     * @return BatchPushResponse
     */
    public function sendBatch(array $deviceTokens, NotificationMessageInterface $message): BatchPushResponse;

    /**
     * Get the driver's platform identifier ('fcm' or 'apns').
     *
     * @return string
     */
    public function getPlatform(): string;
}

