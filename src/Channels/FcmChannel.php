<?php

namespace Creatix\AppNotificationManager\Channels;

use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Facades\AppNotification;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    public function send(mixed $notifiable, Notification $notification): mixed
    {
        $message = null;

        if (method_exists($notification, 'toFcm')) {
            $message = $notification->toFcm($notifiable);
        } elseif (method_exists($notification, 'toAppNotification')) {
            $message = $notification->toAppNotification($notifiable);
        }

        if (! $message instanceof NotificationMessageInterface) {
            return null;
        }

        $token = null;

        if (is_object($notifiable)) {
            if (method_exists($notifiable, 'routeNotificationFor')) {
                $token = $notifiable->routeNotificationFor('fcm', $notification);
            }

            if (! $token && isset($notifiable->fcm_token)) {
                $token = $notifiable->fcm_token;
            }

            if (! $token && isset($notifiable->device_token)) {
                $token = $notifiable->device_token;
            }
        }

        if (! $token || ! is_string($token)) {
            return null;
        }

        return AppNotification::fcm()->send($token, $message);
    }
}
