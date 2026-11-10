<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function check(): void
    {
        $expected = $_SESSION['csrf'] ?? '';
        $sent = $_POST['csrf'] ?? '';
        if ($expected === '' || !is_string($sent) || !hash_equals($expected, $sent)) {
            http_response_code(403);
            exit('Requête invalide.');
        }
    }
}
