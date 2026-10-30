<?php

namespace Creatix\AppNotificationManager\Commands;

use Creatix\AppNotificationManager\Facades\AppNotification;
use Creatix\AppNotificationManager\Messages\Presets\ChatMessagePreset;
use Illuminate\Console\Command;

class SendTestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test
                            {token : Target device token}
                            {--platform= : Target platform: ios, apns, android, fcm}
                            {--title=Test Notification : Notification title}
                            {--body=Hello from Laravel AppNotificationManager! : Notification body}
                            {--image= : Optional rich image URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test push notification to a device token to verify FCM or APNs credentials';

    public function handle(): int
    {
        $token = (string) $this->argument('token');
        $platform = is_string($this->option('platform')) ? $this->option('platform') : null;
        $title = (string) ($this->option('title') ?: 'Test Notification');
        $body = (string) ($this->option('body') ?: 'Hello from Laravel AppNotificationManager!');
        $image = is_string($this->option('image')) ? $this->option('image') : null;

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

            $this->error("✗ Delivery failed: " . ($response->getError() ?? 'Unknown error'));
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
