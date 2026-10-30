<?php

namespace Creatix\AppNotificationManager\Messages\Presets;

use Creatix\AppNotificationManager\Messages\AppNotificationMessage;

class ChatMessagePreset extends AppNotificationMessage
{
    public static function create(): static
    {
        $preset = new static();
        $preset->category('CHAT_REPLY')
            ->android(function ($android) {
                $android->channelId('chat_messages')
                    ->priority('HIGH')
                    ->color('#25D366'); // WhatsApp Brand Green
            })
            ->apns(function ($apns) {
                $apns->priority(10)
                    ->mutableContent(true)
                    ->category('CHAT_REPLY');
            });

        return $preset;
    }

    /**
     * Set the sender information.
     */
    public function sender(string $name, ?string $avatarUrl = null): static
    {
        $this->title($name);
        if ($avatarUrl) {
            $this->avatar($avatarUrl);
        }
        return $this;
    }

    /**
     * Set the message content.
     */
    public function message(string $text): static
    {
        $this->body($text);
        return $this;
    }

    /**
     * Group notifications by conversation / room ID.
     */
    public function conversation(string $conversationId, ?string $groupName = null): static
    {
        $this->threadId("chat_{$conversationId}");
        $this->collapseKey("chat_{$conversationId}");
        if ($groupName) {
            $this->subtitle($groupName);
        }
        $this->withData('conversation_id', $conversationId);
        return $this;
    }

    /**
     * Add attachment preview (photo, document thumbnail, etc.).
     */
    public function attachment(?string $attachmentUrl): static
    {
        $this->image($attachmentUrl);
        return $this;
    }

    /**
     * Enable interactive quick-reply actions.
     */
    public function withQuickReply(bool $enabled = true): static
    {
        $this->category($enabled ? 'CHAT_REPLY' : null);
        return $this;
    }
}

