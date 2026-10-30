<?php

namespace Creatix\AppNotificationManager\Commands;

use Creatix\AppNotificationManager\Facades\AppNotification;
use Creatix\AppNotificationManager\Messages\Presets\ChatMessagePreset;
use Illuminate\Console\Command;

class SendTestNotificationCommand extends Command
{
    protected $signature = 'notification:test
                            {token : Target device token}
                            {--platform= : Target platform: ios, apns, android, fcm}
                            {--title=Test Notification : Notification title}
                            {--body=Hello from Laravel AppNotificationManager! : Notification body}
                            {--image= : Optional rich image URL}';

    protected $description = 'Send a test push notification to a device token to verify FCM or APNs credentials';

    public function handle(): int
    {
        $token = $this->argument('token');
        $platform = $this->option('platform');
        $title = $this->option('title');
        $body = $this->option('body');
        $image = $this->option('image');

        $this->info("Sending test notification to {$token}...");

        $message = ChatMessagePreset::create()
            ->sender($title)
            ->message($body)
            ->badge(1);

        if ($image) {
            $message->image($image);
        }

        try {
            $response = AppNotification::to($token, $platform)->send($message);

            if ($response->isSuccessful()) {
                $this->info("✓ Notification sent successfully!");
                $this->line("Message ID: " . ($response->getMessageId() ?? 'N/A'));
                return self::SUCCESS;
            }

            $this->error("✗ Delivery failed: " . $response->getError());
            if ($response->isInvalidToken()) {
                $this->warn("Token reported as invalid or unregistered by provider.");
            }
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}

