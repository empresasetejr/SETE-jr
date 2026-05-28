<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/chat_helpers.php';

$conversationId = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;

if ($conversationId <= 0) {
    echo json_encode(['messages' => []]);
    exit;
}

echo json_encode([
    'messages' => listConversationMessages($conversationId),
]);

