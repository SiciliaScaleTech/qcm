<?php
declare(strict_types=1);

namespace App\Core;

use MongoDB\Client;
use MongoDB\Database as MongoDatabase;
use Throwable;

// Connexion unique à MongoDB. MongoDB est facultatif : si l'extension manque,
// si l'adresse est vide ou si le serveur ne répond pas, db() renvoie null
// et l'application continue sans statistiques.
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
                // Délai court : une page ne doit pas attendre si MongoDB est arrêté
                ['serverSelectionTimeoutMS' => 800, 'connectTimeoutMS' => 800],
                // Documents renvoyés sous forme de tableaux PHP
                ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
            );
            self::$db = $client->selectDatabase((string) config('mongo_db', 'qcm'));
        } catch (Throwable $e) {
            self::echec($e);
        }
        return self::$db;
    }

    // Après une erreur, MongoDB n'est plus sollicité jusqu'à la fin de la requête
    public static function echec(Throwable $e): void
    {
        self::$db = null;
        self::$indisponible = true;
        error_log('MongoDB indisponible : ' . $e->getMessage());
    }
}
