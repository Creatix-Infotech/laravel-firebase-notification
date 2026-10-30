<?php

namespace Creatix\AppNotificationManager\Events;

class InvalidTokenDetected
{
    public function __construct(
        public string $platform,
        public string $deviceToken,
        public ?string $reason = null
    ) {}
}
