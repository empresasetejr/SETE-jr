<?php

require_once __DIR__ . '/bootstrap.php';

use Models\ChatConversation;
use Models\ChatMessage;
use Models\ChatbotOption;

function createConversation(?string $name, ?string $phone, ?string $email, string $chatbotType): int
{
    return (new ChatConversation())->create($name, $phone, $email, $chatbotType);
}

function getOpenConversation(int $conversationId): ?array
{
    return (new ChatConversation())->findOpen($conversationId);
}

function saveMessage(int $conversationId, string $sender, string $message): void
{
    (new ChatMessage())->create($conversationId, $sender, $message);
    (new ChatConversation())->updateActivity($conversationId);
}

function closeConversation(int $conversationId): void
{
    (new ChatConversation())->close($conversationId);
}

function getManualOptionResponse(string $option): ?string
{
    return (new ChatbotOption())->findActiveResponse($option);
}

function listConversationMessages(int $conversationId): array
{
    return (new ChatMessage())->listByConversation($conversationId);
}

function getConversationHistoryForAi(int $conversationId, int $limit = 12): array
{
    $rows = (new ChatMessage())->historyForAi($conversationId, $limit);
    $rows = array_reverse($rows);
    $messages = [];

    foreach ($rows as $row) {
        $messages[] = [
            'role' => ($row['sender'] ?? 'user') === 'user' ? 'user' : 'assistant',
            'content' => $row['message'] ?? '',
        ];
    }

    return $messages;
}

