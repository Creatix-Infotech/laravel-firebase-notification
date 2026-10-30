<?php

namespace Creatix\AppNotificationManager\Events;

use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Responses\PushResponse;

class NotificationSent
{
    public function __construct(
        public string $platform,
        public string $deviceToken,
        public NotificationMessageInterface $message,
        public PushResponse $response
    ) {}
}
