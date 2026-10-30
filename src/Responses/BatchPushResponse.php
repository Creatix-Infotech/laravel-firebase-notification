<?php

namespace Creatix\AppNotificationManager\Responses;

class BatchPushResponse
{
    /**
     * @param array<PushResponse> $responses
     */
    public function __construct(
        protected array $responses = []
    ) {}

    public function add(PushResponse $response): static
    {
        $this->responses[] = $response;
        return $this;
    }

    /**
     * @return array<PushResponse>
     */
    public function all(): array
    {
        return $this->responses;
    }

    public function count(): int
    {
        return count($this->responses);
    }

    public function successCount(): int
    {
        return count(array_filter($this->responses, fn (PushResponse $r) => $r->isSuccessful()));
    }

    public function failureCount(): int
    {
        return count(array_filter($this->responses, fn (PushResponse $r) => $r->isFailed()));
    }

    /**
     * Retrieve all tokens that were rejected as invalid or unregistered.
     *
     * @return array<string>
     */
    public function invalidTokens(): array
    {
        $tokens = [];
        foreach ($this->responses as $response) {
            if ($response->isInvalidToken()) {
                $tokens[] = $response->getDeviceToken();
            }
        }
        return array_unique($tokens);
    }

    /**
     * Retrieve failure error messages mapped by device token.
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        $errors = [];
        foreach ($this->responses as $response) {
            if ($response->isFailed()) {
                $errors[$response->getDeviceToken()] = $response->getError() ?? 'Unknown error';
            }
        }
        return $errors;
    }

    public function toArray(): array
    {
        return [
            'total' => $this->count(),
            'success_count' => $this->successCount(),
            'failure_count' => $this->failureCount(),
            'invalid_tokens' => $this->invalidTokens(),
            'responses' => array_map(fn (PushResponse $r) => $r->toArray(), $this->responses),
        ];
    }
}

