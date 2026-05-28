<?php

$autoload = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoload)) {
    require_once $autoload;
} else {
    require_once __DIR__ . '/Core/Config.php';
    require_once __DIR__ . '/Core/Connect.php';
    require_once __DIR__ . '/Core/Model.php';
    require_once __DIR__ . '/Models/ChatConversation.php';
    require_once __DIR__ . '/Models/ChatMessage.php';
    require_once __DIR__ . '/Models/ChatbotOption.php';
}

