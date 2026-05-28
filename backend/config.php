<?php
// Configure estes dados de acordo com o MySQL da sua máquina antes de apresentar.
// 1. Importe o arquivo database/estrutura.sql no phpMyAdmin ou MySQL Workbench.
// 2. Ajuste DB_USER e DB_PASS se o seu MySQL usar outro usuário/senha.
// 3. Para testar o modo IA, crie uma chave no OpenRouter e cole em OPENROUTER_API_KEY.

define('DB_HOST', 'localhost');
define('DB_NAME', 'devweb_chatbot');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('OPENROUTER_API_KEY', 'COLE_SUA_CHAVE_OPENROUTER_AQUI');
define('OPENROUTER_MODEL', 'deepseek/deepseek-chat-v3-0324:free');

