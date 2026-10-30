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

        $token = $notifiable->routeNotificationFor('fcm', $notification)
            ?? ($notifiable->fcm_token ?? ($notifiable->device_token ?? null));

        if (! $token) {
            return null;
        }

        return AppNotification::fcm()->send($token, $message);
    }
}

