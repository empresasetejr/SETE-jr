<?php
require_once __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $conversationId = (int)($input['conversation_id'] ?? 0);

    if ($conversationId <= 0) {
        throw new RuntimeException('Conversa inválida.');
    }

    $pdo = getConnection();
    $stmt = $pdo->prepare('UPDATE chat_conversations SET status = "closed", updated_at = NOW() WHERE id = ?');
    $stmt->execute([$conversationId]);

    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $error->getMessage(),
    ]);
}

