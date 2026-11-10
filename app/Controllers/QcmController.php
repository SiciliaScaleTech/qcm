<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Models\Categorie;
use App\Models\Classe;
use App\Models\Qcm;
use App\Models\Question;
use App\Models\Tentative;
use App\Repositories\StatsRepository;
use App\Services\Scoring;
use App\Services\Validation;

class QcmController extends Controller
{
    public function create(): void
    {
        Auth::require();
        // Depuis l'onglet d'une catégorie, celle-ci est déjà cochée
        $this->renderForm(null, [], ['categorie' => (int) ($_GET['categorie'] ?? 0)]);
    }

    public function store(): void
    {
        Auth::require();
        Csrf::check();

        [$data, $errors] = $this->readForm();
        if ($errors) {
            http_response_code(422);
            $this->renderForm(null, $errors, $data);
            return;
        }

        // Le QCM et ses classes sont enregistrés ensemble, ou pas du tout
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $id = Qcm::create(Auth::id(), $data['titre'], $data['description'] ?: null, $data['annee'], $data['categorie']);
        Classe::syncForQcm(Auth::id(), $id, $data['classes'], $data['nouvelles_classes']);
        $pdo->commit();

        StatsRepository::journaliser('qcm_cree', Auth::id(), [
            'id_qcm' => $id,
            'titre' => $data['titre'],
            'annee_scolaire' => $data['annee'],
        ]);

        flash('QCM créé. Ajoutez maintenant vos questions.');
        $this->redirect('/qcm/' . $id);
    }

    public function show(int $id): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($id);

        // Note sur 20 de chaque passage, calculée par le composant métier
        $tentatives = Tentative::forQcm($id);
        foreach ($tentatives as &$tentative) {
            $tentative['note'] = Scoring::noteSur20((int) $tentative['score'], (int) $tentative['nb_questions']);
        }
        unset($tentative);

        $this->render('qcm/show', [
            'qcm' => $qcm,
            'classes' => Classe::forQcm($id),
            'questions' => Question::forQcm($id),
            'tentatives' => $tentatives,
            'moyenne' => Scoring::moyenne($tentatives),
            'lien' => absolute_url('/q/' . $qcm['slug']),
        ], $qcm['titre']);
    }

    // Statistiques par question : textes lus dans MySQL, compteurs lus dans MongoDB
    public function statistiques(int $id): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($id);
        $stats = StatsRepository::pourQcm($id);

        $lignes = [];
        foreach (Question::forQcm($id) as $question) {
            $document = $stats[(int) $question['id_question']] ?? [];
            $options = $document['options'] ?? [];

            $plusChoisie = null;
            $nbPlusChoisie = 0;
            foreach ($question['reponses'] as $reponse) {
                $nb = (int) ($options[(int) $reponse['id_reponse']] ?? 0);
                if ($nb > $nbPlusChoisie) {
                    $plusChoisie = $reponse;
                    $nbPlusChoisie = $nb;
                }
            }

            $lignes[] = [
                'enonce' => $question['enonce'],
                'total' => (int) ($document['total'] ?? 0),
                'taux' => isset($document['taux_reussite']) ? (float) $document['taux_reussite'] : null,
                'plus_choisie' => $plusChoisie,
                'nb_plus_choisie' => $nbPlusChoisie,
            ];
        }

        $this->render('qcm/statistiques', [
            'qcm' => $qcm,
            'disponible' => $stats !== null,
            'lignes' => $lignes,
        ], 'Statistiques - ' . $qcm['titre']);
    }

    public function edit(int $id): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($id);
        $this->renderForm($qcm, [], []);
    }

    public function update(int $id): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($id);
        Csrf::check();

        [$data, $errors] = $this->readForm();
        if ($errors) {
            http_response_code(422);
            $this->renderForm($qcm, $errors, $data);
            return;
        }

        $titreChange = $data['titre'] !== $qcm['titre'];

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        Qcm::update($id, $data['titre'], $data['description'] ?: null, $data['annee'], $data['categorie'], $titreChange);
        Classe::syncForQcm(Auth::id(), $id, $data['classes'], $data['nouvelles_classes']);
        $pdo->commit();

        flash($titreChange ? 'QCM modifié. Le lien de partage a changé avec le titre.' : 'QCM modifié.');
        $this->redirect('/qcm/' . $id);
    }

    // Active ou désactive le lien de partage. Répond en JSON si la requête vient de fetch.
    public function toggle(int $id): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($id);
        Csrf::check();

        $actif = !$qcm['actif'];
        Qcm::setActif($id, $actif);

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
            header('Content-Type: application/json');
            echo json_encode(['actif' => $actif]);
            return;
        }

        flash($actif ? 'Le lien de partage est actif.' : 'Le lien de partage est désactivé.');
        $this->redirect('/qcm/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::require();
        $this->ownedQcm($id);
        Csrf::check();

        Qcm::delete($id);
        StatsRepository::supprimerQcm($id);
        Classe::deleteUnused(Auth::id());
        flash('QCM supprimé.');
        $this->redirect('/');
    }

    private function ownedQcm(int $id): array
    {
        $qcm = Qcm::findOwned($id, Auth::id());
        if (!$qcm) {
            $this->abortNotFound();
        }
        return $qcm;
    }

    // Formulaire de création ($qcm = null) ou de modification
    private function renderForm(?array $qcm, array $errors, array $old): void
    {
        $classesCochees = $qcm ? array_column(Classe::forQcm((int) $qcm['id_qcm']), 'id_classe') : [];

        $this->render('qcm/form', [
            'qcm' => $qcm,
            'errors' => $errors,
            'old' => $old,
            'classes' => Classe::forFormateur(Auth::id()),
            'classesCochees' => array_map('intval', $old['classes'] ?? $classesCochees),
            'annees' => annees_scolaires_proposees(),
            'categories' => Categorie::all(),
        ], $qcm ? 'Modifier le QCM' : 'Nouveau QCM');
    }

    // Règles de saisie : composant métier Validation.
    // Le contrôleur lui donne la liste des catégories, lue en base.
    private function readForm(): array
    {
        return Validation::qcm($_POST, array_column(Categorie::all(), 'id_categorie'));
    }
}
