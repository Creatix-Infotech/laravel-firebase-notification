<?php

namespace Creatix\AppNotificationManager\Events;

use Illuminate\Queue\SerializesModels;

class InvalidTokenDetected
{
    use SerializesModels;

    public function __construct(
        public string $platform,
        public string $deviceToken,
        public ?string $reason = null
    ) {}
}

