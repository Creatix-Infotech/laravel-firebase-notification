<?php

namespace Creatix\AppNotificationManager\Messages;

class ApnsConfig
{
    protected ?string $sound = 'default';
    protected ?int $badge = null;
    protected ?string $category = null;
    protected ?string $threadId = null;
    protected bool $mutableContent = true;
    protected bool $contentAvailable = false;
    protected ?string $targetContentId = null;
    protected ?string $collapseId = null;
    protected int $priority = 10; // 10 = immediate, 5 = battery efficient
    protected string $pushType = 'alert'; // 'alert', 'background', 'voip'
    protected ?int $expiration = null; // unix timestamp

    public static function create(): static
    {
        return new static();
    }

    public function sound(?string $sound): static
    {
        $this->sound = $sound;
        return $this;
    }

    public function badge(?int $badge): static
    {
        $this->badge = $badge;
        return $this;
    }

    public function category(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function threadId(?string $threadId): static
    {
        $this->threadId = $threadId;
        return $this;
    }

    public function mutableContent(bool $enable = true): static
    {
        $this->mutableContent = $enable;
        return $this;
    }

    public function contentAvailable(bool $enable = true): static
    {
        $this->contentAvailable = $enable;
        if ($enable) {
            $this->pushType = 'background';
            $this->priority = 5;
        }
        return $this;
    }

    public function collapseId(?string $collapseId): static
    {
        $this->collapseId = $collapseId;
        return $this;
    }

    public function priority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function pushType(string $pushType): static
    {
        $this->pushType = $pushType;
        return $this;
    }

    public function expiration(?int $timestamp): static
    {
        $this->expiration = $timestamp;
        return $this;
    }

    public function getCollapseId(): ?string
    {
        return $this->collapseId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getPushType(): string
    {
        return $this->pushType;
    }

    public function getExpiration(): ?int
    {
        return $this->expiration;
    }

    public function toApsPayload(): array
    {
        $aps = [];

        if (! empty($this->sound)) {
            $aps['sound'] = $this->sound;
        }

        if ($this->badge !== null) {
            $aps['badge'] = $this->badge;
        }

        if (! empty($this->category)) {
            $aps['category'] = $this->category;
        }

        if (! empty($this->threadId)) {
            $aps['thread-id'] = $this->threadId;
        }

        if ($this->mutableContent) {
            $aps['mutable-content'] = 1;
        }

        if ($this->contentAvailable) {
            $aps['content-available'] = 1;
        }

        if (! empty($this->targetContentId)) {
            $aps['target-content-id'] = $this->targetContentId;
        }

        return $aps;
    }
}

