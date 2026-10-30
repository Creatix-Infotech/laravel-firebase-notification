<?php

namespace Creatix\AppNotificationManager;

use Creatix\AppNotificationManager\Contracts\NotificationDriverInterface;
use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Drivers\ApnsHttp2Driver;
use Creatix\AppNotificationManager\Drivers\FcmV1Driver;
use Creatix\AppNotificationManager\Exceptions\DeviceTokenException;
use Creatix\AppNotificationManager\Responses\BatchPushResponse;
use Creatix\AppNotificationManager\Responses\PushResponse;
use Illuminate\Contracts\Container\Container;

class NotificationManager
{
    /**
     * The array of resolved drivers.
     *
     * @var array<string, NotificationDriverInterface>
     */
    protected array $drivers = [];

    public function __construct(
        protected Container $app
    ) {}

    /**
     * Get a driver instance by name.
     *
     * @param string|null $name
     * @return NotificationDriverInterface
     */
    public function driver(?string $name = null): NotificationDriverInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolveDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Shorthand for Firebase Cloud Messaging (FCM HTTP v1) driver.
     */
    public function fcm(): FcmV1Driver
    {
        /** @var FcmV1Driver $driver */
        $driver = $this->driver('fcm');
        return $driver;
    }

    /**
     * Shorthand for Apple Push Notification service (APNs HTTP/2) driver.
     */
    public function apns(): ApnsHttp2Driver
    {
        /** @var ApnsHttp2Driver $driver */
        $driver = $this->driver('apns');
        return $driver;
    }

    /**
     * Begin a fluent pending notification dispatch.
     *
     * @param string|array $tokens
     * @param string|null $platform
     * @return PendingNotification
     */
    public function to(string|array $tokens, ?string $platform = null): PendingNotification
    {
        return new PendingNotification($this, $tokens, $platform);
    }

    /**
     * Send a notification message directly.
     *
     * @param string|array $tokens
     * @param NotificationMessageInterface $message
     * @param string|null $platform
     * @return PushResponse|BatchPushResponse
     */
    public function send(
        string|array $tokens,
        NotificationMessageInterface $message,
        ?string $platform = null
    ): PushResponse|BatchPushResponse {
        return $this->to($tokens, $platform)->send($message);
    }

    /**
     * Detect mobile platform from token format if not explicitly specified.
     *
     * APNs tokens are typically 64 hex characters (32 bytes).
     * FCM tokens are typically > 100 characters and contain ':' (colon) or base64 chars.
     *
     * @param string $token
     * @return string 'apns' or 'fcm'
     */
    public function detectPlatform(string $token): string
    {
        $clean = trim(str_replace([' ', '<', '>'], '', $token));

        if (preg_match('/^[0-9a-fA-F]{64}$/', $clean)) {
            return 'apns';
        }

        return 'fcm';
    }

    /**
     * Resolve the given driver.
     */
    protected function resolveDriver(string $name): NotificationDriverInterface
    {
        /** @var \Illuminate\Contracts\Config\Repository $configRepo */
        $configRepo = $this->app->make('config');
        $config = (array) $configRepo->get('notification-manager', []);

        return match ($name) {
            'fcm', 'android', 'google' => new FcmV1Driver($config['fcm'] ?? []),
            'apns', 'ios', 'apple' => new ApnsHttp2Driver($config['apns'] ?? []),
            default => throw DeviceTokenException::unsupportedPlatform($name),
        };
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        /** @var \Illuminate\Contracts\Config\Repository $configRepo */
        $configRepo = $this->app->make('config');
        return (string) $configRepo->get('notification-manager.default', 'fcm');
    }
}
