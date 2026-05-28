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

$notUnderstood = false;

try {
    $openConversation = $conversationId > 0 ? getOpenConversation($conversationId) : null;

    if (!$openConversation || ($openConversation['chatbot_type'] ?? '') !== $mode) {
        if (!$visitorName || !$visitorPhone || !$visitorEmail) {
            http_response_code(422);
            echo json_encode(['error' => 'Preencha nome, telefone e e-mail antes de iniciar o atendimento.']);
            exit;
        }

        $conversationId = createConversation($visitorName, $visitorPhone, $visitorEmail, $mode);
    }

    saveMessage($conversationId, 'user', $message);

    if ($mode === 'ai') {
        $reply = getAiResponse($message, OPENROUTER_CONFIG, $companyProfile);
        $sender = 'ai';
    } else {
        $reply = getManualResponse($message, $companyProfile, $notUnderstood);
        $sender = 'bot';
    }

    saveMessage($conversationId, $sender, $reply);

    echo json_encode([
        'conversation_id' => $conversationId,
        'mode' => $mode,
        'reply_sender' => $sender,
        'reply' => $reply,
        'sender' => $sender,
        'not_understood' => $notUnderstood,
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no chatbot. Confira banco de dados e configurações.',
        'detail' => $error->getMessage(),
    ]);
}

function getManualResponse(string $message, array $companyProfile, bool &$notUnderstood = false): string
{
    $normalized = strtolower(trim($message));

    $menuOptionResponse = getSeteJrMenuOptionResponse($normalized);
    if ($menuOptionResponse !== null) {
        return $menuOptionResponse;
    }

    $optionResponse = getManualOptionResponse($normalized);

    if ($optionResponse !== null) {
        return $optionResponse;
    }

    if (in_array($normalized, ['menu', 'opcoes', 'opções', 'ajuda'], true)) {
        return buildManualMenu();
    }

    if (containsAny($normalized, ['financa', 'finança', 'financeiro', 'financeira', 'gasto', 'gastos', 'mei', 'preco', 'preço', 'valor', 'margem'])) {
        return "Finanças: ajudamos com planejamento, controle de gastos e orientação para MEI. Qual é sua principal dúvida?";
    }

    if (containsAny($normalized, ['site', 'landing page', 'pagina', 'página', 'sistema', 'tecnologia', 'automacao', 'automação'])) {
        return "Tecnologia: fazemos sites, sistemas simples e automações. Que solução você precisa?";
    }

    if (containsAny($normalized, ['empreendedorismo', 'empreender', 'empresa', 'abrir empresa', 'abertura', 'modelo de negocio', 'modelo de negócio', 'estrategia', 'estratégia', 'ideia'])) {
        return "Empreendedorismo: apoiamos abertura de empresas, modelagem de negócio e estratégia inicial. Em que fase está seu projeto?";
    }

    if (containsAny($normalized, ['servico', 'serviço', 'servicos', 'serviços', 'fazem', 'oferecem'])) {
        return buildManualMenu();
    }

    if (containsAny($normalized, ['horario', 'horário', 'funcionamento'])) {
        $h = $companyProfile['horario'] ?? [];
        return "Atendimento somente online: segunda a sexta, " . ($h['segunda_a_sexta'] ?? 'em horário comercial') . ". Sábado: " . ($h['sabado'] ?? 'sob agendamento') . ".";
    }

    if (containsAny($normalized, ['contato', 'whatsapp', 'telefone', 'email', 'e-mail', 'atendente'])) {
        return "Contato: WhatsApp " . ($companyProfile['whatsapp'] ?? '-') . " e Instagram " . ($companyProfile['instagram'] ?? '-') . ".";
    }

    if (containsAny($normalized, ['localizacao', 'localização', 'endereco', 'endereço', 'onde', 'presencial', 'online'])) {
        return "Nossa base fica no " . ($companyProfile['endereco'] ?? 'UNIAENE') . ", mas atualmente atendemos somente online.";
    }

    $notUnderstood = true;
    return "Não entendi. Escolha uma opção do menu ou escreva de outro jeito:\n" . buildManualMenu();
}

function buildManualMenu(): string
{
    return "1 - Tecnologia\n"
        . "2 - Finanças\n"
        . "3 - Empreendedorismo\n"
        . "4 - Falar com a equipe";
}

function getSeteJrMenuOptionResponse(string $option): ?string
{
    $responses = [
        '1' => "Tecnologia: sites, landing pages, sistemas simples e automações. Qual solução você precisa?",
        '2' => "Finanças: planejamento, controle de gastos e orientação para MEI. Qual é sua dúvida?",
        '3' => "Empreendedorismo: abertura de empresas, modelo de negócio e estratégia inicial. Em que fase está seu projeto?",
        '4' => "Certo. Descreva brevemente o que você precisa e a equipe da Sete Jr retorna pelo contato informado.",
    ];

    return $responses[$option] ?? null;
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

function getAiResponse(string $userMessage, array $openRouter, array $companyProfile): string
{
    if (empty($openRouter['api_key']) || $openRouter['api_key'] === 'COLE_SUA_CHAVE_OPENROUTER_AQUI') {
        return 'Modo IA ainda precisa da chave OpenRouter em backend/Core/Config.php. Enquanto isso, use o modo Manual para atendimento.';
    }

    $systemPrompt = "Você é atendente virtual da {$companyProfile['nome']}. "
        . "Responda em português do Brasil, com tom cordial, direto e comercial. "
        . "Use respostas curtas, com no máximo 3 frases. "
        . "Não repita telefone, WhatsApp, Instagram ou pedido de contato no fim de toda resposta. "
        . "Só peça contato se o visitante ainda não tiver informado ou se ele pedir atendimento humano. "
        . "Use apenas as informações oficiais abaixo sobre a empresa. "
        . "Ajude visitantes interessados em tecnologia, sites, sistemas simples, automação, finanças, consultoria para MEI, abertura de empresas, modelagem de negócio e estratégia inicial. "
        . "Quando faltar informação, faça uma única pergunta objetiva para entender melhor.\n\n"
        . buildCompanyContext($companyProfile);

    $messages = [['role' => 'system', 'content' => $systemPrompt]];
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
                'max_tokens' => 180,
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
        'max_tokens' => 180,
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
    $lines[] = 'Instagram: ' . ($profile['instagram'] ?? '');
    $lines[] = 'Endereço/base: ' . ($profile['endereco'] ?? '');
    $lines[] = 'Forma de atendimento: ' . ($profile['atendimento'] ?? '');
    $lines[] = 'Serviços: ' . implode(', ', $profile['servicos'] ?? []);

    foreach (['tecnologia', 'financas', 'empreendedorismo'] as $section) {
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
