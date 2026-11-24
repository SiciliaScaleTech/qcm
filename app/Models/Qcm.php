<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Qcm
{
    public static function forIntervenant(int $intervenantId, ?int $categorieId = null, ?string $annee = null, ?int $classeId = null): array
    {
        $sql = 'SELECT q.*, cat.nom AS categorie,
                    (SELECT COUNT(*) FROM question qu WHERE qu.id_qcm = q.id_qcm) AS nb_questions,
                    (SELECT COUNT(*) FROM tentative t WHERE t.id_qcm = q.id_qcm) AS nb_tentatives
                FROM qcm q
                JOIN categorie cat ON cat.id_categorie = q.id_categorie
                WHERE q.id_intervenant = :intervenant';
        $params = ['intervenant' => $intervenantId];

        if ($categorieId !== null) {
            $sql .= ' AND q.id_categorie = :categorie';
            $params['categorie'] = $categorieId;
        }
        if ($annee !== null) {
            $sql .= ' AND q.annee_scolaire = :annee';
            $params['annee'] = $annee;
        }
        if ($classeId !== null) {
            $sql .= ' AND q.id_qcm IN (SELECT id_qcm FROM concerner WHERE id_classe = :classe)';
            $params['classe'] = $classeId;
        }
        $sql .= ' ORDER BY q.date_evaluation DESC, q.id_qcm DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function anneesForIntervenant(int $intervenantId, ?int $categorieId = null): array
    {
        $sql = 'SELECT DISTINCT annee_scolaire FROM qcm WHERE id_intervenant = :intervenant';
        $params = ['intervenant' => $intervenantId];
        if ($categorieId !== null) {
            $sql .= ' AND id_categorie = :categorie';
            $params['categorie'] = $categorieId;
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY annee_scolaire DESC');
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function findOwned(int $id, int $intervenantId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT q.*, cat.nom AS categorie
             FROM qcm q
             JOIN categorie cat ON cat.id_categorie = q.id_categorie
             WHERE q.id_qcm = :id AND q.id_intervenant = :intervenant'
        );
        $stmt->execute(['id' => $id, 'intervenant' => $intervenantId]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT q.*, co.slug, c.id_classe, c.nom AS classe
             FROM concerner co
             JOIN qcm q ON q.id_qcm = co.id_qcm
             JOIN classe c ON c.id_classe = co.id_classe
             WHERE co.slug = :slug'
        );
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $intervenantId, string $titre, ?string $description, string $date, string $annee, int $categorieId): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO qcm (titre, description, date_evaluation, annee_scolaire, id_intervenant, id_categorie)
             VALUES (:titre, :description, :date, :annee, :intervenant, :categorie)'
        );
        $stmt->execute([
            'titre' => $titre,
            'description' => $description,
            'date' => $date,
            'annee' => $annee,
            'intervenant' => $intervenantId,
            'categorie' => $categorieId,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $titre, ?string $description, string $date, string $annee, int $categorieId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE qcm SET titre = :titre, description = :description, date_evaluation = :date,
                    annee_scolaire = :annee, id_categorie = :categorie
             WHERE id_qcm = :id'
        );
        $stmt->execute([
            'titre' => $titre,
            'description' => $description,
            'date' => $date,
            'annee' => $annee,
            'categorie' => $categorieId,
            'id' => $id,
        ]);
    }

    public static function setActif(int $id, bool $actif): void
    {
        $stmt = Database::pdo()->prepare('UPDATE qcm SET actif = :actif WHERE id_qcm = :id');
        $stmt->execute(['actif' => $actif ? 1 : 0, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM qcm WHERE id_qcm = :id');
        $stmt->execute(['id' => $id]);
    }
}
