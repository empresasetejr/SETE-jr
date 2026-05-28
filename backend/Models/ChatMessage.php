<?php

namespace Models;

use Core\Model;

class ChatMessage extends Model
{
    protected string $table = 'chat_messages';

    public function create(int $conversationId, string $sender, string $message): void
    {
        $this->insert([
            'conversation_id' => $conversationId,
            'sender' => $sender,
            'message' => $message,
        ]);
    }

    public function listByConversation(int $conversationId): array
    {
        return $this->findAll(
            ['conversation_id' => $conversationId],
            ['sender', 'message', 'created_at'],
            'id ASC'
        );
    }

    public function historyForAi(int $conversationId, int $limit = 12): array
    {
        return $this->findAll(
            ['conversation_id' => $conversationId],
            ['sender', 'message'],
            'id DESC',
            $limit
        );
    }
}

