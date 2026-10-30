<?php

namespace Creatix\AppNotificationManager\Contracts;

interface NotificationMessageInterface
{
    /**
     * Get the notification title.
     */
    public function getTitle(): ?string;

    /**
     * Get the notification body / message content.
     */
    public function getBody(): ?string;

    /**
     * Get the subtitle (primarily used in iOS).
     */
    public function getSubtitle(): ?string;

    /**
     * Get the rich media image URL (attachment).
     */
    public function getImageUrl(): ?string;

    /**
     * Get the application icon or sender avatar URL.
     */
    public function getAvatarUrl(): ?string;

    /**
     * Get custom data payload key-value pairs.
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Get deep-link / navigation URL or route.
     */
    public function getDeepLink(): ?string;

    /**
     * Get sound file name or default.
     */
    public function getSound(): ?string;

    /**
     * Get badge count.
     */
    public function getBadge(): ?int;

    /**
     * Get thread / group / conversation ID.
     */
    public function getThreadId(): ?string;

    /**
     * Get collapse key / notification grouping tag.
     */
    public function getCollapseKey(): ?string;

    /**
     * Get notification category / action identifier.
     */
    public function getCategory(): ?string;

    /**
     * Check if notification is a background silent sync notification.
     */
    public function isSilent(): bool;

    /**
     * Convert message into provider-specific payload for FCM HTTP v1.
     *
     * @param string $deviceToken
     * @return array<string, mixed>
     */
    public function toFcmPayload(string $deviceToken): array;

    /**
     * Convert message into provider-specific payload for APNs HTTP/2.
     *
     * @return array<string, mixed>
     */
    public function toApnsPayload(): array;

    /**
     * Convert message into APNs HTTP/2 headers.
     *
     * @param string $bundleId
     * @return array<string, string>
     */
    public function toApnsHeaders(string $bundleId): array;
}

