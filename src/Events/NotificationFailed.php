<?php

namespace Creatix\AppNotificationManager\Events;

use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Responses\PushResponse;
use Illuminate\Queue\SerializesModels;

class NotificationFailed
{
    use SerializesModels;

    public function __construct(
        public string $platform,
        public string $deviceToken,
        public NotificationMessageInterface $message,
        public PushResponse $response
    ) {}
}

