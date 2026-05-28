<?php

/**
 * Configuracao global do projeto.
 *
 * Antes da apresentacao ou deploy:
 * 1. Importe database/estrutura.sql no MySQL.
 * 2. Configure as variaveis no arquivo .env.
 * 3. Se for usar IA, preencha OPENROUTER_API_KEY no .env.
 * 4. Se instalar dependencias, rode composer install na raiz do projeto.
 */

function loadProjectEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

function envValue(string $key, string $default = ''): string
{
    $value = getenv($key);

    if ($value === false) {
        return $_ENV[$key] ?? $default;
    }

    return $value;
}

loadProjectEnv(__DIR__ . '/../../.env');

define('APP_NAME', envValue('APP_NAME', 'Sete Jr'));

define('DB_CONFIG', [
    'host' => envValue('DB_HOST', '127.0.0.1'),
    'port' => envValue('DB_PORT', '3306'),
    'name' => envValue('DB_NAME', 'devweb_chatbot'),
    'user' => envValue('DB_USER', 'root'),
    'pass' => envValue('DB_PASS', ''),
]);

define('OPENROUTER_CONFIG', [
    'api_key' => envValue('OPENROUTER_API_KEY'),
    'model' => envValue('OPENROUTER_MODEL', 'openai/gpt-oss-120b:free'),
]);

define('CONFIG', [
    'app_name' => APP_NAME,
    'db' => DB_CONFIG,
    'openrouter' => OPENROUTER_CONFIG,
]);
