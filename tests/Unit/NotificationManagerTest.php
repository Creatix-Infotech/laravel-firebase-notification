<?php

namespace Creatix\AppNotificationManager\Tests\Unit;

use Creatix\AppNotificationManager\Facades\AppNotification;
use Creatix\AppNotificationManager\Responses\BatchPushResponse;
use Creatix\AppNotificationManager\Responses\PushResponse;
use Creatix\AppNotificationManager\Tests\TestCase;

class NotificationManagerTest extends TestCase
{
    public function test_detects_apns_64_char_hex_token(): void
    {
        // 64-character hexadecimal token typical of iOS APNs
        $apnsToken = '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
        $platform = AppNotification::detectPlatform($apnsToken);

        $this->assertEquals('apns', $platform);
    }

    public function test_detects_fcm_token(): void
    {
        // Standard long FCM token with colons and characters
        $fcmToken = 'bk3RNwTe3H0:CI2k_HHwgIpoDKCIZvvDMExUdFQ3P1..._aLongRandomBase64SequenceThatExceedsNormalLength';
        $platform = AppNotification::detectPlatform($fcmToken);

        $this->assertEquals('fcm', $platform);
    }

    public function test_batch_response_aggregates_and_extracts_invalid_tokens(): void
    {
        $batch = new BatchPushResponse();

        $batch->add(PushResponse::success('token-1', 'msg-1'));
        $batch->add(PushResponse::failure('token-2', 'Unregistered', 404, isInvalidToken: true));
        $batch->add(PushResponse::failure('token-3', 'Connection error', 500, isInvalidToken: false));
        $batch->add(PushResponse::failure('token-4', 'BadDeviceToken', 400, isInvalidToken: true));

        $this->assertEquals(4, $batch->count());
        $this->assertEquals(1, $batch->successCount());
        $this->assertEquals(3, $batch->failureCount());

        $invalidTokens = $batch->invalidTokens();
        $this->assertCount(2, $invalidTokens);
        $this->assertContains('token-2', $invalidTokens);
        $this->assertContains('token-4', $invalidTokens);
    }
}

