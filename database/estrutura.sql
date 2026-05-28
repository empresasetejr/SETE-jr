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

DELETE FROM chatbot_options;

INSERT INTO chatbot_options (option_number, title, response, active) VALUES
('1', 'Desenvolvimento de sites e tecnologia', 'A Sete Jr pode ajudar com sites institucionais, landing pages, sistemas simples, automação de processos e organização digital para pequenos e grandes negócios. Conte rapidamente o que você precisa para avaliarmos o melhor caminho.', 1),
('2', 'Precificação e finanças', 'Podemos apoiar sua precificação analisando custos fixos, custos variáveis, margem desejada e posicionamento de mercado. Também podemos montar uma planilha simples para ajudar na tomada de decisão.', 1),
('3', 'Redes sociais e presença digital', 'A Sete Jr pode orientar sua presença digital com diagnóstico de perfil, organização da bio, destaques, ideias de conteúdo, calendário de postagens e chamadas para contato.', 1),
('4', 'Falar com a equipe', 'Certo! Informe seu nome, telefone e uma breve descrição do seu negócio ou projeto para que a equipe da Sete Jr possa retornar com mais contexto.', 1),
('5', 'Menu principal', '1 - Desenvolvimento de sites e tecnologia\n2 - Precificação e finanças\n3 - Redes sociais e presença digital\n4 - Falar com a equipe\n5 - Ver este menu novamente', 1);
