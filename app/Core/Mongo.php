<?php
declare(strict_types=1);

namespace App\Core;

use MongoDB\Client;
use MongoDB\Database as MongoDatabase;
use Throwable;

final class Mongo
{
    private static ?MongoDatabase $db = null;
    private static bool $indisponible = false;

    public static function db(): ?MongoDatabase
    {
        if (self::$db !== null || self::$indisponible) {
            return self::$db;
        }

        $uri = trim((string) config('mongo_uri', ''));
        if ($uri === '' || !extension_loaded('mongodb')) {
            self::$indisponible = true;
            return null;
        }

        try {
            $client = new Client(
                $uri,

                ['serverSelectionTimeoutMS' => 2000, 'connectTimeoutMS' => 2000],

                ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
            );
            self::$db = $client->selectDatabase((string) config('mongo_db', 'qcm'));
        } catch (Throwable $e) {
            self::echec($e);
        }
        return self::$db;
    }

    public static function echec(Throwable $e): void
    {
        self::$db = null;
        self::$indisponible = true;
        error_log('MongoDB indisponible : ' . $e->getMessage());
    }
}
