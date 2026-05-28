<?php

namespace Core;

use PDO;
use PDOException;

class Connect
{
    private const OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
    ];

    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance) {
            return self::$instance;
        }

        try {
            $db = (object) DB_CONFIG;
            $dsn = "mysql:host={$db->host};port={$db->port};dbname={$db->name};charset=utf8mb4";
            self::$instance = new PDO($dsn, $db->user, $db->pass, self::OPTIONS);
        } catch (PDOException $exception) {
            http_response_code(500);
            exit('Erro ao conectar com o banco de dados. Confira backend/Core/Config.php.');
        }

        return self::$instance;
    }
}

