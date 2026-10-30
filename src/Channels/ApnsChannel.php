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

        $token = $notifiable->routeNotificationFor('apns', $notification)
            ?? ($notifiable->apns_token ?? ($notifiable->device_token ?? null));

        if (! $token) {
            return null;
        }

        return AppNotification::apns()->send($token, $message);
    }
}

