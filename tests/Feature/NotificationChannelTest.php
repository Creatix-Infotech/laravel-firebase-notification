<?php

namespace Creatix\AppNotificationManager\Tests\Feature;

use Creatix\AppNotificationManager\Channels\AppNotificationChannel;
use Creatix\AppNotificationManager\Contracts\HasDeviceTokens;
use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Messages\Presets\ChatMessagePreset;
use Creatix\AppNotificationManager\Responses\PushResponse;
use Creatix\AppNotificationManager\Tests\TestCase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class DummyUser implements HasDeviceTokens
{
    public string $device_token = '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
    public string $device_type = 'ios';

    public function routeNotificationForSocialPush(): array|string
    {
        return [
            ['token' => $this->device_token, 'platform' => 'ios'],
        ];
    }
}

class SampleOrderNotification extends Notification
{
    public function via($notifiable): array
    {
        return [AppNotificationChannel::class];
    }

    public function toAppNotification($notifiable): NotificationMessageInterface
    {
        return ChatMessagePreset::create()
            ->sender('Store Bot')
            ->message('Your order has shipped!')
            ->badge(1);
    }
}

class NotificationChannelTest extends TestCase
{
    public function test_channel_processes_notification_for_notifiable(): void
    {
        $user = new DummyUser();
        $notification = new SampleOrderNotification();

        $message = $notification->toAppNotification($user);

        $this->assertEquals('Store Bot', $message->getTitle());
        $this->assertEquals('Your order has shipped!', $message->getBody());
        $this->assertEquals(1, $message->getBadge());
    }
}

