<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Qcm;
use App\Models\Question;

class QuestionController extends Controller
{
    public function create(int $qcmId): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($qcmId);
        $this->render('question/form', ['qcm' => $qcm, 'question' => null, 'errors' => [], 'old' => []], 'Nouvelle question');
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
            ], 'Nouvelle question');
            return;
        }

        Question::create($qcmId, $enonce, $textes, $correcte);
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
        $this->redirect('/qcm/' . $question['id_qcm']);
    }

    public function destroy(int $id): void
    {
        Auth::require();
        $question = $this->ownedQuestion($id);
        Csrf::check();

        Question::delete($id);
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

    private function readForm(): array
    {
        $enonce = trim((string) ($_POST['enonce'] ?? ''));

        $reponses = $_POST['reponses'] ?? [];
        $reponses = is_array($reponses) ? array_slice(array_values($reponses), 0, 4) : [];
        $textes = array_map(fn($r) => is_string($r) ? trim($r) : '', $reponses);
        $textes = array_pad($textes, 4, '');

        $correcte = filter_var($_POST['correct'] ?? '', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 3],
        ]);

        $errors = [];
        if ($enonce === '' || mb_strlen($enonce) > 1000) {
            $errors[] = 'La question est obligatoire (1000 caractères maximum).';
        }
        foreach ($textes as $texte) {
            if ($texte === '' || mb_strlen($texte) > 255) {
                $errors[] = 'Les 4 réponses sont obligatoires (255 caractères maximum chacune).';
                break;
            }
        }
        if ($correcte === false) {
            $errors[] = 'Choisissez la bonne réponse.';
        }

        return [$enonce, $textes, $correcte === false ? -1 : $correcte, $errors];
    }
}
