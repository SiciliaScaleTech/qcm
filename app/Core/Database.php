<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $file = ROOT_PATH . '/config/config.php';
            if (!is_file($file)) {
                http_response_code(500);
                exit('Configuration manquante : copier config/config.example.php vers config/config.php.');
            }
            $config = require $file;

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['db_host'],
                (int) ($config['db_port'] ?? 3306),
                $config['db_name']
            );
            self::$pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }
}
