<?php

namespace Creatix\AppNotificationManager\Messages\Presets;

use Creatix\AppNotificationManager\Messages\AppNotificationMessage;

class SocialPostPreset extends AppNotificationMessage
{
    public static function create(): static
    {
        $preset = new static();
        $preset->category('SOCIAL_INTERACTION')
            ->android(function ($android) {
                $android->channelId('social_activity')
                    ->priority('HIGH')
                    ->color('#E1306C'); // Instagram Pink / Gradient Brand
            })
            ->apns(function ($apns) {
                $apns->priority(10)
                    ->mutableContent(true)
                    ->category('SOCIAL_INTERACTION');
            });

        return $preset;
    }

    /**
     * Set who performed the action (e.g. "alex_ross liked your post").
     */
    public function actor(string $username, ?string $avatarUrl = null): static
    {
        $this->withData('actor_username', $username);
        if ($avatarUrl) {
            $this->avatar($avatarUrl);
        }
        return $this;
    }

    /**
     * Set the activity action type and text.
     */
    public function activity(string $title, string $message): static
    {
        $this->title($title);
        $this->body($message);
        return $this;
    }

    /**
     * Set media thumbnail (photo, reel preview).
     */
    public function mediaThumbnail(string $thumbnailUrl): static
    {
        $this->image($thumbnailUrl);
        return $this;
    }

    /**
     * Set deep link to the post or profile.
     */
    public function targetPost(string $postId, ?string $deepLink = null): static
    {
        $this->withData('post_id', $postId);
        $this->deepLink($deepLink ?? "app://posts/{$postId}");
        return $this;
    }
}

