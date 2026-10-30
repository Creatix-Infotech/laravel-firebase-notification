<?php

namespace Creatix\AppNotificationManager\Messages;

class AndroidConfig
{
    protected ?string $channelId = null;
    protected string $priority = 'HIGH';
    protected ?string $sound = 'default';
    protected ?string $tag = null;
    protected ?string $color = null;
    protected ?string $icon = null;
    protected ?string $clickAction = null;
    protected ?int $notificationCount = null;
    protected string $visibility = 'PUBLIC';
    protected ?int $ttlSeconds = null;

    public static function create(): static
    {
        return new static();
    }

    public function channelId(?string $channelId): static
    {
        $this->channelId = $channelId;
        return $this;
    }

    public function priority(string $priority): static
    {
        $this->priority = strtoupper($priority);
        return $this;
    }

    public function sound(?string $sound): static
    {
        $this->sound = $sound;
        return $this;
    }

    public function tag(?string $tag): static
    {
        $this->tag = $tag;
        return $this;
    }

    public function color(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function clickAction(?string $clickAction): static
    {
        $this->clickAction = $clickAction;
        return $this;
    }

    public function notificationCount(?int $count): static
    {
        $this->notificationCount = $count;
        return $this;
    }

    public function visibility(string $visibility): static
    {
        $this->visibility = strtoupper($visibility);
        return $this;
    }

    public function ttl(int $seconds): static
    {
        $this->ttlSeconds = $seconds;
        return $this;
    }

    public function toArray(): array
    {
        $config = [
            'priority' => $this->priority,
            'notification' => array_filter([
                'channel_id' => $this->channelId,
                'sound' => $this->sound,
                'tag' => $this->tag,
                'color' => $this->color,
                'icon' => $this->icon,
                'click_action' => $this->clickAction,
                'notification_count' => $this->notificationCount,
                'visibility' => $this->visibility,
            ], fn ($val) => $val !== null),
        ];

        if ($this->ttlSeconds !== null) {
            $config['ttl'] = "{$this->ttlSeconds}s";
        }

        return $config;
    }
}

