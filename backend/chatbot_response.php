<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/chat_helpers.php';

$companyProfile = require __DIR__ . '/company_profile.php';
$payload = json_decode(file_get_contents('php://input'), true) ?: [];

$mode = ($payload['mode'] ?? 'manual') === 'ai' ? 'ai' : 'manual';
$message = trim($payload['message'] ?? '');
$conversationId = isset($payload['conversation_id']) ? (int) $payload['conversation_id'] : 0;
$visitorName = trim($payload['visitor_name'] ?? '') ?: null;
$visitorPhone = trim($payload['visitor_phone'] ?? '') ?: null;
$visitorEmail = trim($payload['visitor_email'] ?? '') ?: null;

if ($message === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Mensagem vazia.']);
    exit;
}

try {
    if ($conversationId <= 0 || !getOpenConversation($conversationId)) {
        $conversationId = createConversation($visitorName, $visitorPhone, $visitorEmail, $mode);
    }

    saveMessage($conversationId, 'user', $message);

    if ($mode === 'ai') {
        $history = getConversationHistoryForAi($conversationId, 12);
        $reply = getAiResponse($message, OPENROUTER_CONFIG, $companyProfile, $history);
        $sender = 'ai';
    } else {
        $reply = getManualResponse($message, $companyProfile);
        $sender = 'bot';
    }

    saveMessage($conversationId, $sender, $reply);

    echo json_encode([
        'conversation_id' => $conversationId,
        'mode' => $mode,
        'reply_sender' => $sender,
        'reply' => $reply,
        'sender' => $sender,
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no chatbot. Confira banco de dados e configurações.',
        'detail' => $error->getMessage(),
    ]);
}

function getManualResponse(string $message, array $companyProfile): string
{
    $normalized = strtolower(trim($message));
    $optionResponse = getManualOptionResponse($normalized);

    if ($optionResponse !== null) {
        return $optionResponse;
    }

    if (in_array($normalized, ['5', 'menu', 'opcoes', 'opções'], true)) {
        return buildManualMenu();
    }

    if (containsAny($normalized, ['preco', 'preço', 'precificacao', 'precificação', 'valor', 'margem'])) {
        $p = $companyProfile['precificacao'] ?? [];
        return "Podemos ajudar com precificação analisando custos, margem e posicionamento.\n"
            . "Como funciona: " . ($p['analise_custos'] ?? 'levantamos os principais custos do negócio') . "\n"
            . "Entrega possível: " . ($p['entregavel'] ?? 'orientação e planilha simples') . "\n"
            . "Digite 4 para falar com a equipe.";
    }

    if (containsAny($normalized, ['site', 'landing page', 'pagina', 'página', 'sistema', 'tecnologia', 'automacao', 'automação'])) {
        $t = $companyProfile['tecnologia'] ?? [];
        return "Na área de tecnologia, a Sete Jr pode apoiar com:\n"
            . "- " . ($t['sites'] ?? 'sites institucionais e landing pages') . "\n"
            . "- " . ($t['sistemas'] ?? 'sistemas simples') . "\n"
            . "- " . ($t['automacao'] ?? 'automação de processos') . "\n"
            . "Digite 4 para iniciar um atendimento.";
    }

    if (containsAny($normalized, ['rede social', 'redes sociais', 'instagram', 'marketing', 'conteudo', 'conteúdo'])) {
        $r = $companyProfile['redes_sociais'] ?? [];
        return "Podemos ajudar seu negócio nas redes sociais com diagnóstico, organização de perfil e ideias de conteúdo.\n"
            . ($r['apoio'] ?? 'Apoio em calendário de conteúdo, bio, destaques e chamadas para contato.');
    }

    if (containsAny($normalized, ['servico', 'serviço', 'servicos', 'serviços', 'fazem', 'oferecem'])) {
        return "A Sete Jr oferece:\n- " . implode("\n- ", $companyProfile['servicos'] ?? []);
    }

    if (containsAny($normalized, ['horario', 'horário', 'funcionamento'])) {
        $h = $companyProfile['horario'] ?? [];
        return "Horário de atendimento:\n"
            . "- Segunda a sexta: " . ($h['segunda_a_sexta'] ?? '-') . "\n"
            . "- Sábado: " . ($h['sabado'] ?? '-') . "\n"
            . "- Domingo: " . ($h['domingo'] ?? '-');
    }

    if (containsAny($normalized, ['contato', 'whatsapp', 'telefone', 'email', 'e-mail', 'atendente'])) {
        return "Contatos da Sete Jr:\n"
            . "- WhatsApp: " . ($companyProfile['whatsapp'] ?? '-') . "\n"
            . "- E-mail: " . ($companyProfile['email'] ?? '-') . "\n"
            . "- Instagram: " . ($companyProfile['instagram'] ?? '-');
    }

    if (containsAny($normalized, ['localizacao', 'localização', 'endereco', 'endereço', 'onde'])) {
        return "Endereço/base de atendimento: " . ($companyProfile['endereco'] ?? 'UNIAENE') . ".";
    }

    return "Posso te ajudar com:\n" . buildManualMenu();
}

function buildManualMenu(): string
{
    return "1 - Desenvolvimento de sites e tecnologia\n"
        . "2 - Precificação e finanças\n"
        . "3 - Redes sociais e presença digital\n"
        . "4 - Falar com a equipe\n"
        . "5 - Ver este menu novamente";
}

function containsAny(string $haystack, array $keywords): bool
{
    foreach ($keywords as $word) {
        if (str_contains($haystack, $word)) {
            return true;
        }
    }

    return false;
}

function getAiResponse(string $userMessage, array $openRouter, array $companyProfile, array $history = []): string
{
    if (empty($openRouter['api_key']) || $openRouter['api_key'] === 'COLE_SUA_CHAVE_OPENROUTER_AQUI') {
        return 'Modo IA ainda precisa da chave OpenRouter em backend/Core/Config.php. Enquanto isso, use o modo Manual para atendimento.';
    }

    $systemPrompt = "Você é atendente virtual da {$companyProfile['nome']}. "
        . "Responda em português do Brasil, com tom cordial, direto e comercial. "
        . "Use apenas as informações oficiais abaixo sobre a empresa. "
        . "Ajude visitantes interessados em precificação, desenvolvimento de sites, redes sociais, tecnologia, sistemas simples e soluções para pequenos e grandes negócios. "
        . "Se faltarem informações, peça nome e telefone para a equipe retornar.\n\n"
        . buildCompanyContext($companyProfile);

    $messages = [['role' => 'system', 'content' => $systemPrompt]];
    $messages = array_merge($messages, $history);
    $messages[] = ['role' => 'user', 'content' => $userMessage];

    if (class_exists('\GuzzleHttp\Client')) {
        return getAiResponseWithGuzzle($openRouter, $messages);
    }

    return getAiResponseWithCurl($openRouter, $messages);
}

function getAiResponseWithGuzzle(array $openRouter, array $messages): string
{
    try {
        $client = new GuzzleHttp\Client([
            'base_uri' => 'https://openrouter.ai/api/v1/',
            'timeout' => 20,
        ]);

        $response = $client->post('chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $openRouter['api_key'],
                'Content-Type' => 'application/json',
                'HTTP-Referer' => 'http://localhost',
                'X-Title' => 'Sete Jr - Chatbot',
            ],
            'json' => [
                'model' => $openRouter['model'],
                'messages' => $messages,
                'temperature' => 0.6,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return sanitizeAiText($data['choices'][0]['message']['content'] ?? 'Não consegui gerar uma resposta agora.');
    } catch (Throwable $error) {
        return 'No momento estou com instabilidade para responder por IA. Tente o modo Manual ou fale pelo WhatsApp.';
    }
}

function getAiResponseWithCurl(array $openRouter, array $messages): string
{
    $payload = [
        'model' => $openRouter['model'],
        'messages' => $messages,
        'temperature' => 0.6,
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openRouter['api_key'],
            'HTTP-Referer: http://localhost',
            'X-Title: Sete Jr - Chatbot',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return 'Não consegui consultar a IA agora. Tente o modo Manual ou fale pelo WhatsApp.';
    }

    curl_close($ch);
    $data = json_decode($response, true);

    return sanitizeAiText($data['choices'][0]['message']['content'] ?? 'A IA não retornou uma resposta válida agora.');
}

function buildCompanyContext(array $profile): string
{
    $lines = [];
    $lines[] = 'Nome: ' . ($profile['nome'] ?? '');
    $lines[] = 'Descrição: ' . ($profile['descricao'] ?? '');
    $lines[] = 'WhatsApp: ' . ($profile['whatsapp'] ?? '');
    $lines[] = 'E-mail: ' . ($profile['email'] ?? '');
    $lines[] = 'Instagram: ' . ($profile['instagram'] ?? '');
    $lines[] = 'Endereço/base: ' . ($profile['endereco'] ?? '');
    $lines[] = 'Serviços: ' . implode(', ', $profile['servicos'] ?? []);

    foreach (['precificacao', 'tecnologia', 'redes_sociais'] as $section) {
        if (!empty($profile[$section]) && is_array($profile[$section])) {
            $lines[] = ucfirst(str_replace('_', ' ', $section)) . ': ' . implode(' ', $profile[$section]);
        }
    }

    return implode("\n", $lines);
}

function sanitizeAiText(string $text): string
{
    $text = preg_replace('/```[\s\S]*?```/u', '', $text) ?? $text;
    $text = str_replace(['**', '__', '`'], '', $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

