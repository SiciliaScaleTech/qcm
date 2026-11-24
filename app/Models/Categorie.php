<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Categorie
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT id_categorie, nom FROM categorie ORDER BY nom')->fetchAll();
    }

    public static function forIntervenant(int $intervenantId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id_categorie, c.nom,
                    (SELECT COUNT(*) FROM qcm q
                      WHERE q.id_categorie = c.id_categorie AND q.id_intervenant = :intervenant) AS nb_qcm
             FROM categorie c
             ORDER BY c.nom'
        );
        $stmt->execute(['intervenant' => $intervenantId]);
        return $stmt->fetchAll();
    }
}
