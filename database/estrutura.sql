CREATE DATABASE IF NOT EXISTS devweb_chatbot
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE devweb_chatbot;

CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(100) NULL,
    visitor_phone VARCHAR(20) NULL,
    visitor_email VARCHAR(150) NULL,
    chatbot_type ENUM('manual', 'ai') NOT NULL DEFAULT 'manual',
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender ENUM('user', 'bot', 'ai') NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id)
);

CREATE TABLE IF NOT EXISTS chatbot_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_number VARCHAR(10) NOT NULL,
    title VARCHAR(100) NOT NULL,
    response TEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO chatbot_options (option_number, title, response, active) VALUES
('1', 'Conhecer serviços', 'Nossos principais serviços são tecnologia, finanças e empreendedorismo. Podemos ajudar com sites, sistemas simples, automação, planejamento financeiro e modelagem de negócio.', 1),
('2', 'Horário de atendimento', 'Nosso atendimento inicial acontece em horário comercial. Envie seus dados para combinarmos o melhor horário de retorno.', 1),
('3', 'Localização', 'A Sete Jr é vinculada ao UNIAENE. Entre em contato para receber o endereço e combinar uma conversa com a equipe.', 1),
('4', 'Falar com atendente', 'Certo! Informe seu nome, telefone e uma breve descrição do que você precisa para que um atendente possa retornar.', 1);

