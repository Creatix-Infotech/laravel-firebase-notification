<?php

namespace Creatix\AppNotificationManager\Facades;

use Creatix\AppNotificationManager\Contracts\NotificationDriverInterface;
use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Drivers\ApnsHttp2Driver;
use Creatix\AppNotificationManager\Drivers\FcmV1Driver;
use Creatix\AppNotificationManager\NotificationManager;
use Creatix\AppNotificationManager\PendingNotification;
use Creatix\AppNotificationManager\Responses\BatchPushResponse;
use Creatix\AppNotificationManager\Responses\PushResponse;
use Illuminate\Support\Facades\Facade;

/**
 * @method static NotificationDriverInterface driver(?string $name = null)
 * @method static FcmV1Driver fcm()
 * @method static ApnsHttp2Driver apns()
 * @method static PendingNotification to(string|array $tokens, ?string $platform = null)
 * @method static PushResponse|BatchPushResponse send(string|array $tokens, NotificationMessageInterface $message, ?string $platform = null)
 * @method static string detectPlatform(string $token)
 *
 * @see \Creatix\AppNotificationManager\NotificationManager
 */
class AppNotification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationManager::class;
    }
}

