<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/chat_helpers.php';

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$conversationId = isset($payload['conversation_id']) ? (int) $payload['conversation_id'] : 0;

if ($conversationId <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Conversa inválida.']);
    exit;
}

closeConversation($conversationId);

echo json_encode(['ok' => true]);

