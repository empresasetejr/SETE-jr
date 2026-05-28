<?php
require_once __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new RuntimeException('Dados inválidos.');
    }

    $message = trim($input['message'] ?? '');
    $mode = ($input['mode'] ?? 'manual') === 'ai' ? 'ai' : 'manual';

    if ($message === '') {
        throw new RuntimeException('Mensagem vazia.');
    }

    $pdo = getConnection();
    $conversationId = getOrCreateConversation($pdo, $input, $mode);

    saveMessage($pdo, $conversationId, 'user', $message);

    if ($mode === 'ai') {
        $reply = getAiResponse($message);
        $sender = 'ai';
    } else {
        $reply = getManualResponse($pdo, $message);
        $sender = 'bot';
    }

    saveMessage($pdo, $conversationId, $sender, $reply);
    touchConversation($pdo, $conversationId);

    echo json_encode([
        'conversation_id' => $conversationId,
        'reply' => $reply,
        'sender' => $sender,
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'reply' => 'Erro no chatbot: ' . $error->getMessage(),
        'sender' => 'bot',
    ]);
}

function getOrCreateConversation(PDO $pdo, array $input, string $mode): int
{
    $conversationId = (int)($input['conversation_id'] ?? 0);

    if ($conversationId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM chat_conversations WHERE id = ? AND status = "open"');
        $stmt->execute([$conversationId]);

        if ($stmt->fetch()) {
            return $conversationId;
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO chat_conversations (visitor_name, visitor_phone, visitor_email, chatbot_type)
         VALUES (:visitor_name, :visitor_phone, :visitor_email, :chatbot_type)'
    );

    $stmt->execute([
        ':visitor_name' => empty($input['visitor_name']) ? null : trim($input['visitor_name']),
        ':visitor_phone' => empty($input['visitor_phone']) ? null : trim($input['visitor_phone']),
        ':visitor_email' => empty($input['visitor_email']) ? null : trim($input['visitor_email']),
        ':chatbot_type' => $mode,
    ]);

    return (int)$pdo->lastInsertId();
}

function saveMessage(PDO $pdo, int $conversationId, string $sender, string $message): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO chat_messages (conversation_id, sender, message)
         VALUES (:conversation_id, :sender, :message)'
    );

    $stmt->execute([
        ':conversation_id' => $conversationId,
        ':sender' => $sender,
        ':message' => $message,
    ]);
}

function touchConversation(PDO $pdo, int $conversationId): void
{
    $stmt = $pdo->prepare('UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?');
    $stmt->execute([$conversationId]);
}

function getManualResponse(PDO $pdo, string $message): string
{
    $normalized = strtolower(trim($message));

    $stmt = $pdo->prepare(
        'SELECT response FROM chatbot_options
         WHERE option_number = :option_number AND active = 1
         LIMIT 1'
    );
    $stmt->execute([':option_number' => $normalized]);
    $option = $stmt->fetch();

    if ($option) {
        return $option['response'];
    }

    if (str_contains($normalized, 'serviço') || str_contains($normalized, 'servico')) {
        return 'A Sete Jr atua com tecnologia, finanças e empreendedorismo. Digite 1 para ver os serviços principais.';
    }

    if (str_contains($normalized, 'horário') || str_contains($normalized, 'horario')) {
        return 'Nosso atendimento inicial acontece em horário comercial. Digite 2 para ver mais detalhes.';
    }

    if (str_contains($normalized, 'contato') || str_contains($normalized, 'atendente')) {
        return 'Claro! Envie seu nome, telefone e uma breve descrição do projeto para a equipe retornar.';
    }

    return 'Não entendi totalmente. Digite 1 para serviços, 2 para horário, 3 para localização ou 4 para falar com um atendente.';
}

function getAiResponse(string $message): string
{
    // Para funcionar, troque OPENROUTER_API_KEY em backend/config.php pela chave real.
    // Sem chave configurada, o modo IA cai em uma resposta explicativa para não quebrar a apresentação.
    if (OPENROUTER_API_KEY === 'COLE_SUA_CHAVE_OPENROUTER_AQUI' || OPENROUTER_API_KEY === '') {
        return 'Modo IA ainda precisa da chave OpenRouter em backend/config.php. Enquanto isso, posso atender pelo fluxo manual.';
    }

    $payload = [
        'model' => OPENROUTER_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Você é o atendente virtual da Sete Jr, uma empresa júnior que oferece soluções em tecnologia, finanças e empreendedorismo. Responda de forma breve, educada e orientada a contato comercial.',
            ],
            [
                'role' => 'user',
                'content' => $message,
            ],
        ],
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENROUTER_API_KEY,
            'HTTP-Referer: http://localhost/7sitejr',
            'X-Title: Sete Jr Chatbot',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return 'Não consegui consultar a IA agora. Erro: ' . $error;
    }

    curl_close($ch);
    $data = json_decode($response, true);

    return $data['choices'][0]['message']['content'] ?? 'A IA não retornou uma resposta válida agora.';
}
