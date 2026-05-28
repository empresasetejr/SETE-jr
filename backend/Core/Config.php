<?php
/**
 * Configuração global do projeto.
 *
 * Antes da apresentação:
 * 1. Importe database/estrutura.sql no MySQL.
 * 2. Confira usuário e senha do banco abaixo.
 * 3. Se for usar IA, cole sua chave da OpenRouter em OPENROUTER_CONFIG['api_key'].
 * 4. Se instalar dependências, rode composer install na raiz do projeto.
 */

const APP_NAME = 'Sete Jr';

const DB_CONFIG = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'devweb_chatbot',
    'user' => 'root',
    'pass' => '',
];

const OPENROUTER_CONFIG = [
    'api_key' => 'COLE_SUA_CHAVE_OPENROUTER_AQUI',
    'model' => 'openai/gpt-oss-120b:free',
];

const CONFIG = [
    'app_name' => APP_NAME,
    'db' => DB_CONFIG,
    'openrouter' => OPENROUTER_CONFIG,
];

