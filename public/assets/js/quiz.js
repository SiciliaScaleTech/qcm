// Affiche une question à la fois. Sans JavaScript, toutes les questions restent visibles.
(function () {
    const form = document.getElementById('quiz');
    if (!form) {
        return;
    }

    const questions = Array.from(form.querySelectorAll('[data-question]'));
    const progression = document.getElementById('progression');
    const barre = document.getElementById('barre');
    const precedent = document.getElementById('precedent');
    const suivant = document.getElementById('suivant');
    const terminer = document.getElementById('terminer');
    const erreur = document.getElementById('erreur-reponse');
    let courante = 0;

    form.classList.add('js-quiz');
    precedent.hidden = false;
    suivant.hidden = false;

    function repondue(question) {
        return question.querySelector('input[type="radio"]:checked') !== null;
    }

    function afficher(index) {
        courante = index;
        questions.forEach((question, i) => question.classList.toggle('active', i === index));
        progression.textContent = 'Question ' + (index + 1) + ' sur ' + questions.length;
        barre.style.width = (100 * (index + 1) / questions.length) + '%';
        precedent.disabled = index === 0;
        suivant.hidden = index === questions.length - 1;
        terminer.hidden = index !== questions.length - 1;
        erreur.hidden = true;
        questions[index].querySelector('legend').focus();
    }

    function avancer() {
        if (!repondue(questions[courante])) {
            erreur.hidden = false;
            return;
        }
        if (courante < questions.length - 1) {
            afficher(courante + 1);
        }
    }

    precedent.addEventListener('click', () => afficher(courante - 1));
    suivant.addEventListener('click', avancer);

    // Touches 1 à 4 pour répondre, Entrée pour continuer
    document.addEventListener('keydown', (event) => {
        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }
        if (event.key >= '1' && event.key <= '4') {
            const radios = questions[courante].querySelectorAll('input[type="radio"]');
            const radio = radios[Number(event.key) - 1];
            if (radio) {
                radio.checked = true;
                erreur.hidden = true;
            }
        } else if (event.key === 'Enter' && courante < questions.length - 1) {
            event.preventDefault();
            avancer();
        }
    });

    form.addEventListener('submit', (event) => {
        const manquante = questions.findIndex((question) => !repondue(question));
        if (manquante !== -1) {
            event.preventDefault();
            afficher(manquante);
            erreur.hidden = false;
        }
    });

    afficher(0);
})();
