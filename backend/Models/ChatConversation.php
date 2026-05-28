<?php

namespace Models;

use Core\Model;

class ChatConversation extends Model
{
    protected string $table = 'chat_conversations';

    public function create(?string $name, ?string $phone, ?string $email, string $chatbotType): int
    {
        return $this->insert([
            'visitor_name' => $name,
            'visitor_phone' => $phone,
            'visitor_email' => $email,
            'chatbot_type' => $chatbotType,
        ]);
    }

    public function findOpen(int $conversationId): ?array
    {
        return $this->findOne([
            'id' => $conversationId,
            'status' => 'open',
        ], ['id', 'chatbot_type']);
    }

    public function updateActivity(int $conversationId): void
    {
        $this->update([
            'updated_at' => date('Y-m-d H:i:s'),
        ], [
            'id' => $conversationId,
        ]);
    }

    public function close(int $conversationId): void
    {
        $this->update([
            'status' => 'closed',
            'updated_at' => date('Y-m-d H:i:s'),
        ], [
            'id' => $conversationId,
        ]);
    }
}
