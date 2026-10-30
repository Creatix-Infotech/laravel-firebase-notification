<?php

namespace Creatix\AppNotificationManager\Channels;

use Creatix\AppNotificationManager\Contracts\HasDeviceTokens;
use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Facades\AppNotification;
use Illuminate\Notifications\Notification;

class AppNotificationChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return mixed
     */
    public function send(mixed $notifiable, Notification $notification): mixed
    {
        $message = null;

        if (method_exists($notification, 'toAppNotification')) {
            $message = $notification->toAppNotification($notifiable);
        } elseif (method_exists($notification, 'toSocialPush')) {
            $message = $notification->toSocialPush($notifiable);
        }

        if (! $message instanceof NotificationMessageInterface) {
            return null;
        }

        $tokens = $this->resolveTokens($notifiable, $notification);

        if (empty($tokens)) {
            return null;
        }

        return AppNotification::to($tokens)->send($message);
    }

    /**
     * Resolve target device tokens from notifiable.
     */
    protected function resolveTokens(mixed $notifiable, Notification $notification): array
    {
        if ($routing = $notifiable->routeNotificationFor('app_notification', $notification)) {
            return is_array($routing) ? $routing : [$routing];
        }

        if ($routing = $notifiable->routeNotificationFor('social_push', $notification)) {
            return is_array($routing) ? $routing : [$routing];
        }

        if ($notifiable instanceof HasDeviceTokens) {
            $tokens = $notifiable->routeNotificationForSocialPush();
            return is_array($tokens) ? $tokens : [$tokens];
        }

        if (! empty($notifiable->device_token)) {
            return [
                [
                    'token' => $notifiable->device_token,
                    'platform' => $notifiable->device_type ?? null,
                ],
            ];
        }

        if (! empty($notifiable->fcm_token)) {
            return [
                [
                    'token' => $notifiable->fcm_token,
                    'platform' => 'android',
                ],
            ];
        }

        if (method_exists($notifiable, 'deviceTokens') && is_iterable($notifiable->deviceTokens)) {
            $tokens = [];
            foreach ($notifiable->deviceTokens as $device) {
                if (! empty($device->token)) {
                    $tokens[] = [
                        'token' => $device->token,
                        'platform' => $device->platform ?? null,
                    ];
                }
            }
            return $tokens;
        }

        return [];
    }
}

