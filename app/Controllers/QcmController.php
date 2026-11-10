<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Qcm;
use App\Models\Question;

class QcmController extends Controller
{
    public function create(): void
    {
        Auth::require();
        $this->render('qcm/form', ['qcm' => null, 'errors' => [], 'old' => []], 'Nouveau QCM');
    }

    public function store(): void
    {
        Auth::require();
        Csrf::check();

        [$titre, $description, $errors] = $this->readForm();
        if ($errors) {
            http_response_code(422);
            $this->render('qcm/form', [
                'qcm' => null,
                'errors' => $errors,
                'old' => ['titre' => $titre, 'description' => $description],
            ], 'Nouveau QCM');
            return;
        }

        $jeton = bin2hex(random_bytes(16));
        $id = Qcm::create(Auth::id(), $titre, $description ?: null, $jeton);
        $this->redirect('/qcm/' . $id);
    }

    public function show(int $id): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($id);
        $this->render('qcm/show', [
            'qcm' => $qcm,
            'questions' => Question::forQcm($id),
            'lien' => absolute_url('/q/' . $qcm['jeton_partage']),
        ], $qcm['titre']);
    }

    public function edit(int $id): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($id);
        $this->render('qcm/form', ['qcm' => $qcm, 'errors' => [], 'old' => []], 'Modifier le QCM');
    }

    public function update(int $id): void
    {
        Auth::require();
        $qcm = $this->ownedQcm($id);
        Csrf::check();

        [$titre, $description, $errors] = $this->readForm();
        $actif = isset($_POST['actif']);
        if ($errors) {
            http_response_code(422);
            $this->render('qcm/form', [
                'qcm' => $qcm,
                'errors' => $errors,
                'old' => ['titre' => $titre, 'description' => $description, 'actif' => $actif],
            ], 'Modifier le QCM');
            return;
        }

        Qcm::update($id, $titre, $description ?: null, $actif);
        $this->redirect('/qcm/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::require();
        $this->ownedQcm($id);
        Csrf::check();

        Qcm::delete($id);
        $this->redirect('/tableau-de-bord');
    }

    private function ownedQcm(int $id): array
    {
        $qcm = Qcm::findOwned($id, Auth::id());
        if (!$qcm) {
            $this->abortNotFound();
        }
        return $qcm;
    }

    private function readForm(): array
    {
        $titre = trim((string) ($_POST['titre'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        $errors = [];
        if ($titre === '' || mb_strlen($titre) > 150) {
            $errors[] = 'Le titre est obligatoire (150 caractères maximum).';
        }
        if (mb_strlen($description) > 1000) {
            $errors[] = 'La description ne doit pas dépasser 1000 caractères.';
        }
        return [$titre, $description, $errors];
    }
}
