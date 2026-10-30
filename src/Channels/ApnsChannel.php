<?php

namespace Creatix\AppNotificationManager\Channels;

use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Facades\AppNotification;
use Illuminate\Notifications\Notification;

class ApnsChannel
{
    public function send(mixed $notifiable, Notification $notification): mixed
    {
        $message = null;

        if (method_exists($notification, 'toApns')) {
            $message = $notification->toApns($notifiable);
        } elseif (method_exists($notification, 'toAppNotification')) {
            $message = $notification->toAppNotification($notifiable);
        }

        if (! $message instanceof NotificationMessageInterface) {
            return null;
        }

        $token = null;

        if (is_object($notifiable)) {
            if (method_exists($notifiable, 'routeNotificationFor')) {
                $token = $notifiable->routeNotificationFor('apns', $notification);
            }

            if (! $token && isset($notifiable->apns_token)) {
                $token = $notifiable->apns_token;
            }

            if (! $token && isset($notifiable->device_token)) {
                $token = $notifiable->device_token;
            }
        }

        if (! $token || ! is_string($token)) {
            return null;
        }

        return AppNotification::apns()->send($token, $message);
    }
}
