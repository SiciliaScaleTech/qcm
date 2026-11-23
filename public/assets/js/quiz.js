(function () {
    const form = document.getElementById('quiz');
    if (!form) {
        return;
    }

    const questions = Array.from(form.querySelectorAll('[data-question]'));
    const liens = Array.from(form.querySelectorAll('[data-nav]'));
    const compteur = document.getElementById('nb-repondues');
    const barre = document.getElementById('barre');
    const boutonSansReponse = document.getElementById('sans-reponse');
    const dialog = document.getElementById('quiz-dialog');
    const texteDialog = document.getElementById('quiz-dialog-text');
    const cle = form.dataset.storage;

    function lire() {
        try {
            return JSON.parse(sessionStorage.getItem(cle)) || {};
        } catch (e) {
            return {};
        }
    }

    function sauver() {
        const reponses = {};
        form.querySelectorAll('input[type="radio"]:checked').forEach((radio) => {
            reponses[radio.name] = radio.value;
        });
        try {
            sessionStorage.setItem(cle, JSON.stringify(reponses));
        } catch (e) {
        }
    }

    function sansReponse() {
        return questions.filter((q) => !q.querySelector('input[type="radio"]:checked'));
    }

    function actualiser() {
        const manquantes = sansReponse();
        const repondues = questions.length - manquantes.length;
        compteur.textContent = repondues;
        barre.style.width = (100 * repondues / questions.length) + '%';
        questions.forEach((question, i) => {
            const ok = !manquantes.includes(question);
            question.classList.toggle('repondue', ok);
            liens[i].classList.toggle('ok', ok);
            liens[i].setAttribute('aria-label', 'Question ' + (i + 1) + (ok ? ', répondue' : ', sans réponse'));
        });
        boutonSansReponse.hidden = manquantes.length === 0;
    }

    function allerA(question) {
        question.scrollIntoView({ block: 'start' });
        const radio = question.querySelector('input[type="radio"]');
        if (radio) {
            radio.focus({ preventScroll: true });
        }
    }

    const enregistrees = lire();
    Object.keys(enregistrees).forEach((nom) => {
        const radio = Array.from(form.querySelectorAll('input[type="radio"]'))
            .find((r) => r.name === nom && r.value === enregistrees[nom]);
        if (radio) {
            radio.checked = true;
        }
    });

    form.addEventListener('change', () => {
        sauver();
        actualiser();
    });

    liens.forEach((lien, i) => lien.addEventListener('click', (event) => {
        event.preventDefault();
        allerA(questions[i]);
        history.replaceState(null, '', '#q-' + (i + 1));
    }));

    boutonSansReponse.addEventListener('click', () => {
        const manquantes = sansReponse();
        if (!manquantes.length) {
            return;
        }
        const ecran = window.scrollY + 120;
        const suivante = manquantes.find((q) => q.offsetTop > ecran) || manquantes[0];
        allerA(suivante);
    });

    function envoyer() {
        try {
            sessionStorage.removeItem(cle);
        } catch (e) {
        }
        form.submit();
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const manquantes = sansReponse();
        let message = 'Vous ne pourrez plus modifier vos réponses.';
        if (manquantes.length) {
            const numeros = manquantes.map((q) => q.dataset.numero).join(', ');
            message = (manquantes.length === 1
                ? 'La question ' + numeros + ' est sans réponse.'
                : manquantes.length + ' questions sont sans réponse : ' + numeros + '.')
                + ' Une question sans réponse compte comme fausse. ' + message;
        }

        if (!dialog || typeof dialog.showModal !== 'function') {
            if (window.confirm(message)) {
                envoyer();
            }
            return;
        }

        texteDialog.textContent = message;
        dialog.returnValue = '';
        dialog.addEventListener('close', function onClose() {
            dialog.removeEventListener('close', onClose);
            if (dialog.returnValue === 'ok') {
                envoyer();
            } else if (manquantes.length) {
                allerA(manquantes[0]);
            }
        });
        dialog.showModal();
    });

    const nav = form.querySelector('.quiz-nav');
    if (nav && window.matchMedia('(max-width: 56rem)').matches) {
        nav.open = false;
    }

    actualiser();
})();
