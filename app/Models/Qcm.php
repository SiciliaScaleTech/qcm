<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Qcm
{
    // QCM du formateur, filtrés si besoin par catégorie, année scolaire et classe (onglets du tableau de bord)
    public static function forFormateur(int $formateurId, ?int $categorieId = null, ?string $annee = null, ?int $classeId = null): array
    {
        $sql = 'SELECT q.*, cat.nom AS categorie,
                    (SELECT COUNT(*) FROM question qu WHERE qu.id_qcm = q.id_qcm) AS nb_questions,
                    (SELECT COUNT(*) FROM tentative t WHERE t.id_qcm = q.id_qcm) AS nb_tentatives,
                    (SELECT GROUP_CONCAT(c.nom ORDER BY c.nom SEPARATOR \', \')
                       FROM classe c JOIN concerner co ON co.id_classe = c.id_classe
                      WHERE co.id_qcm = q.id_qcm) AS classes
                FROM qcm q
                JOIN categorie cat ON cat.id_categorie = q.id_categorie
                WHERE q.id_formateur = :formateur';
        $params = ['formateur' => $formateurId];

        // Les filtres sont ajoutés seulement s'ils sont demandés
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
        // Rangés par année scolaire (la plus récente d'abord), puis du plus récent au plus ancien
        $sql .= ' ORDER BY q.annee_scolaire DESC, q.id_qcm DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Années scolaires où le formateur a au moins un QCM (dans la catégorie choisie),
    // de la plus récente à la plus ancienne
    public static function anneesForFormateur(int $formateurId, ?int $categorieId = null): array
    {
        $sql = 'SELECT DISTINCT annee_scolaire FROM qcm WHERE id_formateur = :formateur';
        $params = ['formateur' => $formateurId];
        if ($categorieId !== null) {
            $sql .= ' AND id_categorie = :categorie';
            $params['categorie'] = $categorieId;
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY annee_scolaire DESC');
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    // Renvoie le QCM seulement s'il appartient à ce formateur
    public static function findOwned(int $id, int $formateurId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT q.*, cat.nom AS categorie
             FROM qcm q
             JOIN categorie cat ON cat.id_categorie = q.id_categorie
             WHERE q.id_qcm = :id AND q.id_formateur = :formateur'
        );
        $stmt->execute(['id' => $id, 'formateur' => $formateurId]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM qcm WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $formateurId, string $titre, ?string $description, string $annee, int $categorieId): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO qcm (titre, description, slug, annee_scolaire, id_formateur, id_categorie)
             VALUES (:titre, :description, :slug, :annee, :formateur, :categorie)'
        );
        $stmt->execute([
            'titre' => $titre,
            'description' => $description,
            'slug' => self::uniqueSlug($titre),
            'annee' => $annee,
            'formateur' => $formateurId,
            'categorie' => $categorieId,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    // Si le titre change, le lien de partage suit le nouveau titre
    public static function update(int $id, string $titre, ?string $description, string $annee, int $categorieId, bool $changerSlug): void
    {
        $sql = 'UPDATE qcm SET titre = :titre, description = :description, annee_scolaire = :annee, id_categorie = :categorie';
        $params = [
            'titre' => $titre,
            'description' => $description,
            'annee' => $annee,
            'categorie' => $categorieId,
            'id' => $id,
        ];
        if ($changerSlug) {
            $sql .= ', slug = :slug';
            $params['slug'] = self::uniqueSlug($titre, $id);
        }
        $stmt = Database::pdo()->prepare($sql . ' WHERE id_qcm = :id');
        $stmt->execute($params);
    }

    public static function setActif(int $id, bool $actif): void
    {
        $stmt = Database::pdo()->prepare('UPDATE qcm SET actif = :actif WHERE id_qcm = :id');
        $stmt->execute(['actif' => $actif ? 1 : 0, 'id' => $id]);
    }

    // Slug libre construit à partir du titre : "html", puis "html-2", "html-3"...
    // $saufId : le QCM en cours de modification peut garder son propre slug
    public static function uniqueSlug(string $titre, int $saufId = 0): string
    {
        $base = slugify($titre);
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM qcm WHERE slug = :slug AND id_qcm <> :id');

        $slug = $base;
        $numero = 2;
        while (true) {
            $stmt->execute(['slug' => $slug, 'id' => $saufId]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $numero;
            $numero++;
        }
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM qcm WHERE id_qcm = :id');
        $stmt->execute(['id' => $id]);
    }
}
