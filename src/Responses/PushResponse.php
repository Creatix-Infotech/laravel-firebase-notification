<?php

namespace Creatix\AppNotificationManager\Responses;

class PushResponse
{
    public function __construct(
        protected bool $successful,
        protected string $deviceToken,
        protected ?string $messageId = null,
        protected ?string $error = null,
        protected ?int $statusCode = null,
        protected bool $isInvalidToken = false,
        protected array $raw = []
    ) {}

    public static function success(string $deviceToken, ?string $messageId = null, array $raw = []): static
    {
        return new static(
            successful: true,
            deviceToken: $deviceToken,
            messageId: $messageId,
            raw: $raw
        );
    }

    public static function failure(
        string $deviceToken,
        string $error,
        ?int $statusCode = null,
        bool $isInvalidToken = false,
        array $raw = []
    ): static {
        return new static(
            successful: false,
            deviceToken: $deviceToken,
            error: $error,
            statusCode: $statusCode,
            isInvalidToken: $isInvalidToken,
            raw: $raw
        );
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function isFailed(): bool
    {
        return ! $this->successful;
    }

    public function getDeviceToken(): string
    {
        return $this->deviceToken;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function isInvalidToken(): bool
    {
        return $this->isInvalidToken;
    }

    public function getRaw(): array
    {
        return $this->raw;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->successful,
            'device_token' => $this->deviceToken,
            'message_id' => $this->messageId,
            'error' => $this->error,
            'status_code' => $this->statusCode,
            'is_invalid_token' => $this->isInvalidToken,
            'raw' => $this->raw,
        ];
    }
}

