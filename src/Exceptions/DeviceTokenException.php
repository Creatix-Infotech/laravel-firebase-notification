<?php

namespace Creatix\AppNotificationManager\Exceptions;

class DeviceTokenException extends AppNotificationException
{
    public static function missingTokens(): static
    {
        return new static('No target device tokens were provided for push notification.');
    }

    public static function unsupportedPlatform(string $platform): static
    {
        return new static("Unsupported push notification platform: '{$platform}'. Supported platforms are 'android' (fcm) and 'ios' (apns).");
    }
}

