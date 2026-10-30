<?php

namespace Creatix\AppNotificationManager\Messages;

use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;

class AppNotificationMessage implements NotificationMessageInterface
{
    protected ?string $title = null;
    protected ?string $body = null;
    protected ?string $subtitle = null;
    protected ?string $imageUrl = null;
    protected ?string $avatarUrl = null;
    protected ?string $deepLink = null;
    protected ?string $sound = 'default';
    protected ?int $badge = null;
    protected ?string $threadId = null;
    protected ?string $collapseKey = null;
    protected ?string $category = null;
    protected bool $isSilent = false;
    protected array $data = [];

    protected ?AndroidConfig $androidConfig = null;
    protected ?ApnsConfig $apnsConfig = null;

    public function __construct()
    {
        $this->androidConfig = new AndroidConfig();
        $this->apnsConfig = new ApnsConfig();
    }

    public static function create(): static
    {
        return new static();
    }

    public function title(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function body(?string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function subtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function image(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function avatar(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function deepLink(?string $deepLink): static
    {
        $this->deepLink = $deepLink;
        return $this;
    }

    public function sound(?string $sound): static
    {
        $this->sound = $sound;
        $this->androidConfig->sound($sound);
        $this->apnsConfig->sound($sound);
        return $this;
    }

    public function badge(?int $badge): static
    {
        $this->badge = $badge;
        $this->androidConfig->notificationCount($badge);
        $this->apnsConfig->badge($badge);
        return $this;
    }

    public function threadId(?string $threadId): static
    {
        $this->threadId = $threadId;
        $this->androidConfig->tag($threadId);
        $this->apnsConfig->threadId($threadId);
        return $this;
    }

    public function collapseKey(?string $collapseKey): static
    {
        $this->collapseKey = $collapseKey;
        $this->androidConfig->tag($collapseKey);
        $this->apnsConfig->collapseId($collapseKey);
        return $this;
    }

    public function category(?string $category): static
    {
        $this->category = $category;
        $this->apnsConfig->category($category);
        return $this;
    }

    public function silent(bool $isSilent = true): static
    {
        $this->isSilent = $isSilent;
        if ($isSilent) {
            $this->apnsConfig->contentAvailable(true);
        }
        return $this;
    }

    public function data(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function withData(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function android(callable|AndroidConfig $config): static
    {
        if (is_callable($config)) {
            $config($this->androidConfig);
        } else {
            $this->androidConfig = $config;
        }
        return $this;
    }

    public function apns(callable|ApnsConfig $config): static
    {
        if (is_callable($config)) {
            $config($this->apnsConfig);
        } else {
            $this->apnsConfig = $config;
        }
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getDeepLink(): ?string
    {
        return $this->deepLink;
    }

    public function getSound(): ?string
    {
        return $this->sound;
    }

    public function getBadge(): ?int
    {
        return $this->badge;
    }

    public function getThreadId(): ?string
    {
        return $this->threadId;
    }

    public function getCollapseKey(): ?string
    {
        return $this->collapseKey;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function isSilent(): bool
    {
        return $this->isSilent;
    }

    public function getAndroidConfig(): AndroidConfig
    {
        return $this->androidConfig;
    }

    public function getApnsConfig(): ApnsConfig
    {
        return $this->apnsConfig;
    }

    /**
     * Convert message into provider-specific payload for FCM HTTP v1.
     */
    public function toFcmPayload(string $deviceToken): array
    {
        $fcmMessage = [
            'token' => $deviceToken,
        ];

        // Format data payload (FCM HTTP v1 requires all values in data to be strings)
        $dataPayload = [];
        foreach ($this->data as $key => $val) {
            $dataPayload[(string) $key] = is_string($val) ? $val : json_encode($val);
        }

        if ($this->avatarUrl) {
            $dataPayload['avatar_url'] = $this->avatarUrl;
        }

        if ($this->deepLink) {
            $dataPayload['deep_link'] = $this->deepLink;
        }

        if ($this->threadId) {
            $dataPayload['thread_id'] = $this->threadId;
        }

        if ($this->category) {
            $dataPayload['category'] = $this->category;
        }

        if (! empty($dataPayload)) {
            $fcmMessage['data'] = $dataPayload;
        }

        // If not silent, include visual notification block
        if (! $this->isSilent) {
            $notification = array_filter([
                'title' => $this->title,
                'body' => $this->body,
                'image' => $this->imageUrl,
            ], fn ($val) => $val !== null);

            if (! empty($notification)) {
                $fcmMessage['notification'] = $notification;
            }

            $fcmMessage['android'] = $this->androidConfig->toArray();
        } else {
            // For data-only background messages, set high priority in android block
            $fcmMessage['android'] = [
                'priority' => 'HIGH',
            ];
        }

        return ['message' => $fcmMessage];
    }

    /**
     * Convert message into provider-specific payload for APNs HTTP/2.
     */
    public function toApnsPayload(): array
    {
        $aps = $this->apnsConfig->toApsPayload();

        if (! $this->isSilent) {
            $alert = array_filter([
                'title' => $this->title,
                'body' => $this->body,
                'subtitle' => $this->subtitle,
            ], fn ($val) => $val !== null);

            if (! empty($alert)) {
                $aps['alert'] = $alert;
            }
        }

        $payload = ['aps' => $aps];

        // Rich media & custom metadata
        if ($this->imageUrl) {
            $payload['image_url'] = $this->imageUrl;
            $payload['media_url'] = $this->imageUrl;
        }

        if ($this->avatarUrl) {
            $payload['avatar_url'] = $this->avatarUrl;
        }

        if ($this->deepLink) {
            $payload['deep_link'] = $this->deepLink;
        }

        if ($this->threadId) {
            $payload['thread_id'] = $this->threadId;
        }

        // Merge custom data
        foreach ($this->data as $key => $val) {
            $payload[$key] = $val;
        }

        return $payload;
    }

    /**
     * Convert message into APNs HTTP/2 request headers.
     */
    public function toApnsHeaders(string $bundleId): array
    {
        $headers = [
            'apns-topic' => $bundleId,
            'apns-push-type' => $this->apnsConfig->getPushType(),
            'apns-priority' => (string) $this->apnsConfig->getPriority(),
        ];

        $collapseId = $this->collapseKey ?? $this->apnsConfig->getCollapseId();
        if ($collapseId) {
            $headers['apns-collapse-id'] = substr($collapseId, 0, 64);
        }

        if ($this->apnsConfig->getExpiration() !== null) {
            $headers['apns-expiration'] = (string) $this->apnsConfig->getExpiration();
        }

        return $headers;
    }
}

