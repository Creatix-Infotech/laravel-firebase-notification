<?php

namespace Creatix\AppNotificationManager\Messages\Presets;

use Creatix\AppNotificationManager\Messages\AppNotificationMessage;

class CallAlertPreset extends AppNotificationMessage
{
    public static function create(): static
    {
        $preset = new static();
        $preset->category('INCOMING_CALL')
            ->sound('ringtone.caf')
            ->android(function ($android) {
                $android->channelId('incoming_calls')
                    ->priority('HIGH')
                    ->sound('ringtone')
                    ->visibility('PUBLIC')
                    ->ttl(60); // Call expires in 60s
            })
            ->apns(function ($apns) {
                $apns->priority(10)
                    ->sound('ringtone.caf')
                    ->category('INCOMING_CALL')
                    ->expiration(time() + 60);
            });

        return $preset;
    }

    public function caller(string $name, ?string $avatarUrl = null, string $type = 'Audio'): static
    {
        $this->title("Incoming {$type} Call");
        $this->body("{$name} is calling you...");
        if ($avatarUrl) {
            $this->avatar($avatarUrl);
        }
        $this->withData('caller_name', $name);
        $this->withData('call_type', strtolower($type));
        return $this;
    }

    public function callSession(string $sessionId, ?string $channelToken = null): static
    {
        $this->withData('session_id', $sessionId);
        if ($channelToken) {
            $this->withData('channel_token', $channelToken);
        }
        return $this;
    }
}

