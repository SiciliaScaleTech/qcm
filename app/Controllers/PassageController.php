<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Qcm;
use App\Models\Question;
use App\Models\Tentative;
use App\Repositories\StatsRepository;
use App\Services\Scoring;
use App\Services\Validation;

class PassageController extends Controller
{
    public function start(string $slug): void
    {
        [$qcm, $questions] = $this->availableQcm($slug);
        $this->render('passage/accueil', [
            'qcm' => $qcm,
            'nbQuestions' => count($questions),
            'errors' => [],
            'old' => [],
            'hideNav' => true,
        ], $qcm['titre']);
    }

    public function begin(string $slug): void
    {
        [$qcm, $questions] = $this->availableQcm($slug);
        Csrf::check();

        // Règles de saisie : composant métier Validation
        [['nom' => $nom, 'prenom' => $prenom], $errors] = Validation::etudiant($_POST);

        // Un étudiant ne peut passer le QCM qu'une seule fois, même depuis un autre navigateur
        if (!$errors && Tentative::trouver((int) $qcm['id_qcm'], $nom, $prenom) !== null) {
            $errors[] = "Un passage est déjà enregistré à ce nom pour ce QCM. Contactez votre formateur en cas d'erreur.";
        }

        if ($errors) {
            http_response_code(422);
            $this->render('passage/accueil', [
                'qcm' => $qcm,
                'nbQuestions' => count($questions),
                'errors' => $errors,
                'old' => ['nom' => $nom, 'prenom' => $prenom],
                'hideNav' => true,
            ], $qcm['titre']);
            return;
        }

        $_SESSION['passage'][$slug] = ['nom' => $nom, 'prenom' => $prenom];
        $this->redirect('/q/' . $slug . '/passage');
    }

    public function passage(string $slug): void
    {
        [$qcm, $questions] = $this->availableQcm($slug);
        if (!isset($_SESSION['passage'][$slug])) {
            $this->redirect('/q/' . $slug);
        }

        // Les bonnes réponses ne sont pas envoyées au navigateur : on ne garde que id et texte
        $questions = array_map(function (array $question): array {
            $question['reponses'] = array_map(
                fn(array $r): array => ['id_reponse' => $r['id_reponse'], 'texte' => $r['texte']],
                $question['reponses']
            );
            return $question;
        }, $questions);

        $this->render('passage/question', [
            'qcm' => $qcm,
            'questions' => $questions,
            'hideNav' => true,
            'scripts' => ['assets/js/quiz.js'],
        ], $qcm['titre']);
    }

    public function finish(string $slug): void
    {
        [$qcm, $questions] = $this->availableQcm($slug);
        $identite = $_SESSION['passage'][$slug] ?? null;
        if ($identite === null) {
            $this->redirect('/q/' . $slug);
        }
        Csrf::check();

        // La correction est faite par le composant métier Scoring
        $envoyees = is_array($_POST['reponse'] ?? null) ? $_POST['reponse'] : [];
        ['score' => $score, 'choix' => $choix] = Scoring::corriger($questions, $envoyees);

        unset($_SESSION['passage'][$slug]);

        // Cas de deux onglets ouverts en même temps : seul le premier envoi est enregistré
        $dejaEnregistre = Tentative::trouver((int) $qcm['id_qcm'], $identite['nom'], $identite['prenom']);
        if ($dejaEnregistre !== null) {
            $this->marquerTermine($qcm, $dejaEnregistre);
            $this->redirect('/q/' . $slug);
        }

        // Le nombre de questions est enregistré avec le score pour calculer la note sur 20
        $id = Tentative::create((int) $qcm['id_qcm'], $identite['nom'], $identite['prenom'], $score, count($questions), $choix);

        StatsRepository::enregistrerPassage((int) $qcm['id_qcm'], $questions, $choix);
        StatsRepository::journaliser('passage_termine', (int) $qcm['id_formateur'], [
            'id_qcm' => (int) $qcm['id_qcm'],
            'id_tentative' => $id,
            'score' => $score,
            'nb_questions' => count($questions),
        ]);

        $_SESSION['resultat'][$slug] = $id;
        $this->marquerTermine($qcm, $id);
        $this->redirect('/q/' . $slug . '/resultat');
    }

    public function result(string $slug): void
    {
        $qcm = $this->findQcm($slug);
        if (!$qcm) {
            $this->unavailable('Ce lien n\'est pas valide.');
        }

        $tentativeId = $_SESSION['resultat'][$slug] ?? null;
        $tentative = $tentativeId ? Tentative::findForQcm((int) $tentativeId, (int) $qcm['id_qcm']) : null;
        if (!$tentative) {
            $this->redirect('/q/' . $slug);
        }

        $score = (int) $tentative['score'];
        $nb = (int) $tentative['nb_questions'];

        $this->render('passage/resultat', [
            'qcm' => $qcm,
            'tentative' => $tentative,
            'note' => Scoring::noteSur20($score, $nb),
            'pourcentage' => Scoring::pourcentage($score, $nb),
            'questions' => Question::forQcm((int) $qcm['id_qcm']),
            'choix' => Tentative::choixIds((int) $tentative['id_tentative']),
            'hideNav' => true,
        ], 'Résultat');
    }

    private function findQcm(string $slug): ?array
    {
        // Un slug ne contient que des minuscules, des chiffres et des tirets
        return preg_match('/^[a-z0-9-]{1,160}$/', $slug) ? Qcm::findBySlug($slug) : null;
    }

    // QCM accessible : le lien existe, il est actif, l'étudiant ne l'a pas déjà terminé
    // et le QCM contient des questions
    private function availableQcm(string $slug): array
    {
        $qcm = $this->findQcm($slug);
        if (!$qcm || !$qcm['actif']) {
            $this->unavailable('Ce lien n\'est pas valide ou le QCM n\'est plus accessible.');
        }

        if ($this->dejaTermine($qcm)) {
            http_response_code(403);
            $this->render('passage/termine', [
                'qcm' => $qcm,
                'aUnResultat' => isset($_SESSION['resultat'][$slug]),
                'hideNav' => true,
            ], 'QCM déjà terminé');
            exit;
        }

        $questions = Question::forQcm((int) $qcm['id_qcm']);
        if (!$questions) {
            $this->unavailable('Ce QCM ne contient pas encore de questions.');
        }
        return [$qcm, $questions];
    }

    // Nom du cookie qui retient qu'un QCM a été terminé dans ce navigateur
    // (on utilise l'id, qui ne change pas, contrairement au slug qui suit le titre)
    private function nomCookie(array $qcm): string
    {
        return 'qcm_termine_' . (int) $qcm['id_qcm'];
    }

    // Le cookie (ou la session) contient l'id du passage de l'étudiant.
    // Si le formateur a supprimé ce passage, l'étudiant peut recommencer.
    private function dejaTermine(array $qcm): bool
    {
        $tentativeId = (int) ($_COOKIE[$this->nomCookie($qcm)] ?? $_SESSION['termine'][(int) $qcm['id_qcm']] ?? 0);
        if ($tentativeId === 0) {
            return false;
        }
        return Tentative::findForQcm($tentativeId, (int) $qcm['id_qcm']) !== null;
    }

    // Retient la fin du QCM en session et dans un cookie valable un an
    private function marquerTermine(array $qcm, int $tentativeId): void
    {
        $_SESSION['termine'][(int) $qcm['id_qcm']] = $tentativeId;
        setcookie($this->nomCookie($qcm), (string) $tentativeId, [
            'expires'  => time() + 365 * 24 * 3600,
            'path'     => url('/'),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
    }

    private function unavailable(string $message): void
    {
        http_response_code(404);
        $this->render('passage/indisponible', ['message' => $message, 'hideNav' => true], 'QCM indisponible');
        exit;
    }
}
