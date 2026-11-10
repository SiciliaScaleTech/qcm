<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

// Catégories des QCM (Cybersécurité, Développement), communes à tous les formateurs
class Categorie
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT id_categorie, nom FROM categorie ORDER BY nom')->fetchAll();
    }

    // Catégories avec le nombre de QCM de ce formateur dans chacune (onglets du tableau de bord)
    public static function forFormateur(int $formateurId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id_categorie, c.nom,
                    (SELECT COUNT(*) FROM qcm q
                      WHERE q.id_categorie = c.id_categorie AND q.id_formateur = :formateur) AS nb_qcm
             FROM categorie c
             ORDER BY c.nom'
        );
        $stmt->execute(['formateur' => $formateurId]);
        return $stmt->fetchAll();
    }
}
