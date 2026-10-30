<?php

namespace Creatix\AppNotificationManager\Messages\Presets;

use Creatix\AppNotificationManager\Messages\AppNotificationMessage;

class SnapStoryPreset extends AppNotificationMessage
{
    public static function create(): static
    {
        $preset = new static();
        $preset->category('SNAP_ALERT')
            ->android(function ($android) {
                $android->channelId('ephemeral_snaps')
                    ->priority('HIGH')
                    ->color('#FFFC00') // Snapchat Yellow
                    ->ttl(300); // 5 minutes TTL
            })
            ->apns(function ($apns) {
                $apns->priority(10)
                    ->mutableContent(true)
                    ->category('SNAP_ALERT')
                    ->expiration(time() + 300);
            });

        return $preset;
    }

    /**
     * Ephemeral snap notification from a friend.
     */
    public function snapFrom(string $friendName, ?string $avatarUrl = null): static
    {
        $this->title('New Snap');
        $this->body("New Snap from {$friendName}");
        if ($avatarUrl) {
            $this->avatar($avatarUrl);
        }
        $this->withData('snap_sender', $friendName);
        return $this;
    }

    /**
     * New story update alert.
     */
    public function storyUpdate(string $friendName, ?string $storyPreviewUrl = null): static
    {
        $this->title('Story Update');
        $this->body("{$friendName} added a new story");
        if ($storyPreviewUrl) {
            $this->image($storyPreviewUrl);
        }
        return $this;
    }

    /**
     * Configure expiration time in seconds (ephemeral notification).
     */
    public function ttl(int $seconds): static
    {
        $this->androidConfig->ttl($seconds);
        $this->apnsConfig->expiration(time() + $seconds);
        return $this;
    }
}

