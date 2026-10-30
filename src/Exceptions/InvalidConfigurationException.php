<?php

namespace Creatix\AppNotificationManager\Exceptions;

class InvalidConfigurationException extends AppNotificationException
{
    public static function missingFcmProjectId(): static
    {
        return new static('Firebase Cloud Messaging requires a valid FCM_PROJECT_ID.');
    }

    public static function missingFcmCredentials(): static
    {
        return new static('Firebase service account credentials file or json is missing. Please configure FCM_CREDENTIALS_PATH or FCM_CREDENTIALS_JSON.');
    }

    public static function missingApnsCredentials(): static
    {
        return new static('Apple Push Notification service requires APNS_KEY_ID, APNS_TEAM_ID, APNS_BUNDLE_ID, and a valid .p8 private key.');
    }
}

