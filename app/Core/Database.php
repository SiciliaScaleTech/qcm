<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

// Connexion unique à MySQL avec PDO
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
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $config['db_host'],
                $config['db_name']
            );
            self::$pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // vraies requêtes préparées
            ]);
        }
        return self::$pdo;
    }
}
