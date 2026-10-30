<?php

namespace Creatix\AppNotificationManager\Tests\Unit;

use Creatix\AppNotificationManager\Messages\AppNotificationMessage;
use Creatix\AppNotificationManager\Messages\Presets\ChatMessagePreset;
use Creatix\AppNotificationManager\Messages\Presets\SocialPostPreset;
use Creatix\AppNotificationManager\Messages\Presets\SnapStoryPreset;
use Creatix\AppNotificationManager\Messages\Presets\CallAlertPreset;
use Creatix\AppNotificationManager\Tests\TestCase;

class MessagePayloadTest extends TestCase
{
    public function test_standard_fcm_payload_generation(): void
    {
        $message = AppNotificationMessage::create()
            ->title('New message')
            ->body('Hey, how are you?')
            ->image('https://example.com/photo.jpg')
            ->avatar('https://example.com/avatar.jpg')
            ->deepLink('app://chat/42')
            ->badge(3)
            ->withData('custom_key', 'custom_value');

        $payload = $message->toFcmPayload('test-device-token');

        $this->assertEquals('test-device-token', $payload['message']['token']);
        $this->assertEquals('New message', $payload['message']['notification']['title']);
        $this->assertEquals('Hey, how are you?', $payload['message']['notification']['body']);
        $this->assertEquals('https://example.com/photo.jpg', $payload['message']['notification']['image']);
        $this->assertEquals('https://example.com/avatar.jpg', $payload['message']['data']['avatar_url']);
        $this->assertEquals('app://chat/42', $payload['message']['data']['deep_link']);
        $this->assertEquals('custom_value', $payload['message']['data']['custom_key']);
        $this->assertEquals(3, $payload['message']['android']['notification']['notification_count']);
    }

    public function test_standard_apns_payload_generation(): void
    {
        $message = AppNotificationMessage::create()
            ->title('Order Shipped')
            ->body('Your package is on its way')
            ->subtitle('Tracking: 123456')
            ->badge(2)
            ->threadId('orders_thread')
            ->image('https://example.com/package.png');

        $payload = $message->toApnsPayload();

        $this->assertEquals('Order Shipped', $payload['aps']['alert']['title']);
        $this->assertEquals('Your package is on its way', $payload['aps']['alert']['body']);
        $this->assertEquals('Tracking: 123456', $payload['aps']['alert']['subtitle']);
        $this->assertEquals(2, $payload['aps']['badge']);
        $this->assertEquals('orders_thread', $payload['aps']['thread-id']);
        $this->assertEquals(1, $payload['aps']['mutable-content']);
        $this->assertEquals('https://example.com/package.png', $payload['image_url']);

        $headers = $message->toApnsHeaders('com.example.testapp');
        $this->assertEquals('com.example.testapp', $headers['apns-topic']);
        $this->assertEquals('alert', $headers['apns-push-type']);
        $this->assertEquals('10', $headers['apns-priority']);
    }

    public function test_silent_sync_notification_payload(): void
    {
        $message = AppNotificationMessage::create()
            ->silent(true)
            ->withData('action', 'SYNC_MESSAGES');

        $fcmPayload = $message->toFcmPayload('token-123');
        $this->assertArrayNotHasKey('notification', $fcmPayload['message']);
        $this->assertEquals('SYNC_MESSAGES', $fcmPayload['message']['data']['action']);

        $apnsPayload = $message->toApnsPayload();
        $this->assertArrayNotHasKey('alert', $apnsPayload['aps']);
        $this->assertEquals(1, $apnsPayload['aps']['content-available']);

        $headers = $message->toApnsHeaders('com.example.testapp');
        $this->assertEquals('background', $headers['apns-push-type']);
        $this->assertEquals('5', $headers['apns-priority']);
    }

    public function test_whatsapp_chat_preset(): void
    {
        $chat = ChatMessagePreset::create()
            ->sender('Sarah Connor', 'https://example.com/sarah.jpg')
            ->message('See you at the dock.')
            ->conversation('room_99', 'Skynet Resistance')
            ->attachment('https://example.com/dock.jpg')
            ->withQuickReply();

        $fcm = $chat->toFcmPayload('token-xyz');
        $this->assertEquals('Sarah Connor', $fcm['message']['notification']['title']);
        $this->assertEquals('See you at the dock.', $fcm['message']['notification']['body']);
        $this->assertEquals('#25D366', $fcm['message']['android']['notification']['color']);
        $this->assertEquals('chat_messages', $fcm['message']['android']['notification']['channel_id']);
        $this->assertEquals('chat_room_99', $fcm['message']['data']['thread_id']);
        $this->assertEquals('CHAT_REPLY', $fcm['message']['data']['category']);

        $apns = $chat->toApnsPayload();
        $this->assertEquals('CHAT_REPLY', $apns['aps']['category']);
        $this->assertEquals('chat_room_99', $apns['aps']['thread-id']);
        $this->assertEquals('Skynet Resistance', $apns['aps']['alert']['subtitle']);
    }

    public function test_instagram_social_post_preset(): void
    {
        $social = SocialPostPreset::create()
            ->actor('john_doe', 'https://example.com/john.jpg')
            ->activity('New Like', 'john_doe liked your photo')
            ->mediaThumbnail('https://example.com/thumb.jpg')
            ->targetPost('post_888');

        $fcm = $social->toFcmPayload('token-abc');
        $this->assertEquals('New Like', $fcm['message']['notification']['title']);
        $this->assertEquals('#E1306C', $fcm['message']['android']['notification']['color']);
        $this->assertEquals('app://posts/post_888', $fcm['message']['data']['deep_link']);
        $this->assertEquals('john_doe', $fcm['message']['data']['actor_username']);
    }

    public function test_snapchat_preset(): void
    {
        $snap = SnapStoryPreset::create()
            ->snapFrom('Emily', 'https://example.com/emily.jpg')
            ->ttl(120);

        $fcm = $snap->toFcmPayload('token-snap');
        $this->assertEquals('New Snap', $fcm['message']['notification']['title']);
        $this->assertEquals('New Snap from Emily', $fcm['message']['notification']['body']);
        $this->assertEquals('120s', $fcm['message']['android']['ttl']);
        $this->assertEquals('#FFFC00', $fcm['message']['android']['notification']['color']);
    }

    public function test_call_alert_preset(): void
    {
        $call = CallAlertPreset::create()
            ->caller('David Beckham', 'https://example.com/david.jpg', 'Video')
            ->callSession('session_abc', 'token_xyz');

        $fcm = $call->toFcmPayload('token-call');
        $this->assertEquals('Incoming Video Call', $fcm['message']['notification']['title']);
        $this->assertEquals('session_abc', $fcm['message']['data']['session_id']);
        $this->assertEquals('incoming_calls', $fcm['message']['android']['notification']['channel_id']);

        $apns = $call->toApnsPayload();
        $this->assertEquals('INCOMING_CALL', $apns['aps']['category']);
        $this->assertEquals('ringtone.caf', $apns['aps']['sound']);
    }
}

