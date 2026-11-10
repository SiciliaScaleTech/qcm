<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Qcm;
use App\Models\Question;
use App\Repositories\StatsRepository;
use App\Services\Validation;

class QuestionController extends Controller
{
    public function create(int $qcmId): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($qcmId);
        $this->render('question/form', [
            'qcm' => $qcm,
            'question' => null,
            'errors' => [],
            'old' => [],
            'numero' => count(Question::forQcm($qcmId)) + 1,
        ], 'Nouvelle question');
    }

    public function store(int $qcmId): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($qcmId);
        Csrf::check();

        [$enonce, $textes, $correcte, $errors] = $this->readForm();
        if ($errors) {
            http_response_code(422);
            $this->render('question/form', [
                'qcm' => $qcm,
                'question' => null,
                'errors' => $errors,
                'old' => ['enonce' => $enonce, 'reponses' => $textes, 'correct' => $correcte],
                'numero' => count(Question::forQcm($qcmId)) + 1,
            ], 'Nouvelle question');
            return;
        }

        Question::create($qcmId, $enonce, $textes, $correcte);

        // Bouton "Enregistrer et ajouter la suivante" : on revient sur un formulaire vide
        if (isset($_POST['suivante'])) {
            flash('Question ajoutée. Saisissez la suivante.');
            $this->redirect('/qcm/' . $qcmId . '/questions/nouvelle');
        }

        flash('Question ajoutée.');
        $this->redirect('/qcm/' . $qcmId);
    }

    public function edit(int $id): void
    {
        Auth::require();
        $question = $this->ownedQuestion($id);
        $qcm = Qcm::findOwned((int) $question['id_qcm'], Auth::id());
        $this->render('question/form', ['qcm' => $qcm, 'question' => $question, 'errors' => [], 'old' => []], 'Modifier la question');
    }

    public function update(int $id): void
    {
        Auth::require();
        $question = $this->ownedQuestion($id);
        Csrf::check();

        [$enonce, $textes, $correcte, $errors] = $this->readForm();
        if ($errors) {
            http_response_code(422);
            $this->render('question/form', [
                'qcm' => Qcm::findOwned((int) $question['id_qcm'], Auth::id()),
                'question' => $question,
                'errors' => $errors,
                'old' => ['enonce' => $enonce, 'reponses' => $textes, 'correct' => $correcte],
            ], 'Modifier la question');
            return;
        }

        Question::update($id, $enonce, $textes, $correcte);
        flash('Question modifiée.');
        $this->redirect('/qcm/' . $question['id_qcm']);
    }

    public function destroy(int $id): void
    {
        Auth::require();
        $question = $this->ownedQuestion($id);
        Csrf::check();

        Question::delete($id);
        StatsRepository::supprimerQuestion($id);
        flash('Question supprimée.');
        $this->redirect('/qcm/' . $question['id_qcm']);
    }

    private function ownedQcm(int $id): array
    {
        $qcm = Qcm::findOwned($id, Auth::id());
        if (!$qcm) {
            $this->abortNotFound();
        }
        return $qcm;
    }

    private function ownedQuestion(int $id): array
    {
        $question = Question::findOwned($id, Auth::id());
        if (!$question) {
            $this->abortNotFound();
        }
        return $question;
    }

    // Règles de saisie : composant métier Validation
    private function readForm(): array
    {
        [$donnees, $errors] = Validation::question($_POST);
        return [$donnees['enonce'], $donnees['reponses'], $donnees['correcte'], $errors];
    }
}
