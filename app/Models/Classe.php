<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Classe
{
    public static function forIntervenant(int $intervenantId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id_classe, c.nom,
                    (SELECT COUNT(*) FROM concerner co WHERE co.id_classe = c.id_classe) AS nb_qcm
             FROM classe c
             WHERE c.id_intervenant = :intervenant
             ORDER BY c.nom'
        );
        $stmt->execute(['intervenant' => $intervenantId]);
        return $stmt->fetchAll();
    }

    public static function forQcm(int $qcmId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id_classe, c.nom, co.slug,
                    (SELECT COUNT(*) FROM tentative t WHERE t.id_qcm = co.id_qcm AND t.id_classe = c.id_classe) AS nb_tentatives
             FROM classe c
             JOIN concerner co ON co.id_classe = c.id_classe
             WHERE co.id_qcm = :qcm
             ORDER BY c.nom'
        );
        $stmt->execute(['qcm' => $qcmId]);
        return $stmt->fetchAll();
    }

    public static function liensParQcm(array $qcmIds): array
    {
        if (!$qcmIds) {
            return [];
        }
        $marques = implode(',', array_fill(0, count($qcmIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT co.id_qcm, c.nom, co.slug
             FROM concerner co
             JOIN classe c ON c.id_classe = co.id_classe
             WHERE co.id_qcm IN ($marques)
             ORDER BY c.nom"
        );
        $stmt->execute(array_map('intval', array_values($qcmIds)));
        $liens = [];
        foreach ($stmt->fetchAll() as $ligne) {
            $liens[(int) $ligne['id_qcm']][] = $ligne;
        }
        return $liens;
    }

    public static function findOrCreate(int $intervenantId, string $nom): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id_classe FROM classe WHERE id_intervenant = :intervenant AND nom = :nom');
        $stmt->execute(['intervenant' => $intervenantId, 'nom' => $nom]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $pdo->prepare('INSERT INTO classe (nom, id_intervenant) VALUES (:nom, :intervenant)');
        $stmt->execute(['nom' => $nom, 'intervenant' => $intervenantId]);
        return (int) $pdo->lastInsertId();
    }

    public static function syncForQcm(int $intervenantId, int $qcmId, array $ids, array $nouvelles, string $titre, string $date): void
    {
        $pdo = Database::pdo();

        $siennes = array_map('intval', array_column(self::forIntervenant($intervenantId), 'id_classe'));
        $ids = array_values(array_intersect($ids, $siennes));

        foreach ($nouvelles as $nom) {
            $ids[] = self::findOrCreate($intervenantId, $nom);
        }

        $stmt = $pdo->prepare('DELETE FROM concerner WHERE id_qcm = :qcm');
        $stmt->execute(['qcm' => $qcmId]);

        $noms = $pdo->prepare('SELECT nom FROM classe WHERE id_classe = :id');
        $insert = $pdo->prepare('INSERT INTO concerner (id_qcm, id_classe, slug) VALUES (:qcm, :classe, :slug)');
        foreach (array_unique($ids) as $id) {
            $noms->execute(['id' => $id]);
            $slug = self::slugLibre(slug_lien($titre, (string) $noms->fetchColumn(), $date));
            $insert->execute(['qcm' => $qcmId, 'classe' => $id, 'slug' => $slug]);
        }

        self::deleteUnused($intervenantId);
    }

    private static function slugLibre(string $base): string
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM concerner WHERE slug = :slug');
        $slug = $base;
        for ($numero = 2; ; $numero++) {
            $stmt->execute(['slug' => $slug]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $numero;
        }
    }

    public static function deleteUnused(int $intervenantId): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM classe
             WHERE id_intervenant = :intervenant
               AND id_classe NOT IN (SELECT id_classe FROM concerner)
               AND id_classe NOT IN (SELECT id_classe FROM tentative WHERE id_classe IS NOT NULL)'
        );
        $stmt->execute(['intervenant' => $intervenantId]);
    }
}
