<?php

namespace Creatix\AppNotificationManager\Contracts;

interface HasDeviceTokens
{
    /**
     * Get the device tokens for the notifiable entity.
     *
     * Should return either:
     * - An array of token strings: ['token1', 'token2']
     * - An array of structured items: [['token' => '...', 'platform' => 'ios'|'android']]
     * - A single token string
     *
     * @return array|string
     */
    public function routeNotificationForSocialPush(): array|string;
}

