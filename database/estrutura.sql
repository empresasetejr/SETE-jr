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
('1', 'Tecnologia', 'Na área de tecnologia, fazemos sites profissionais, sistemas simples e automação de processos. Conte qual solução digital você precisa.', 1),
('2', 'Finanças', 'A SETE Jr ajuda com planejamento financeiro, controle de gastos e consultoria para MEI. Conte qual é a principal dúvida financeira do seu negócio.', 1),
('3', 'Empreendedorismo', 'Apoiamos empreendedorismo com abertura de empresas, modelagem de negócio e estratégia inicial. Conte em que fase está sua ideia ou empresa.', 1),
('4', 'Falar com a equipe', 'Certo! Envie seu nome, telefone e uma breve descrição do projeto.', 1),
('5', 'Menu principal', '1 - Tecnologia\n2 - Finanças\n3 - Empreendedorismo\n4 - Falar com a equipe', 1);
