(function () {
    const menuBouton = document.querySelector('[data-menu]');
    if (menuBouton) {
        const menu = document.getElementById(menuBouton.getAttribute('aria-controls'));
        const basculer = (ouvert) => {
            menuBouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
            menu.classList.toggle('ouvert', ouvert);
        };
        menuBouton.addEventListener('click', () => basculer(menuBouton.getAttribute('aria-expanded') !== 'true'));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && menu.classList.contains('ouvert')) {
                basculer(false);
                menuBouton.focus();
            }
        });
        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target) && !menuBouton.contains(event.target)) {
                basculer(false);
            }
        });
    }

    document.querySelectorAll('[data-select]').forEach((input) => {
        input.addEventListener('focus', () => input.select());
    });

    const toasts = document.getElementById('toasts');

    function toast(message, type) {
        const p = document.createElement('p');
        p.className = 'toast' + (type === 'error' ? ' toast-error' : '');
        p.textContent = message;
        toasts.appendChild(p);
        setTimeout(() => p.remove(), 5200);
    }

    toasts.querySelectorAll('.toast').forEach((p) => setTimeout(() => p.remove(), 5200));

    const dialog = document.getElementById('confirm-dialog');
    const canDialog = dialog && typeof dialog.showModal === 'function';

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed) {
                return;
            }
            event.preventDefault();

            if (!canDialog) {
                if (window.confirm(form.dataset.confirm)) {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
                return;
            }

            document.getElementById('confirm-title').textContent = form.dataset.confirmTitle || 'Confirmer';
            document.getElementById('confirm-text').textContent = form.dataset.confirm;
            document.getElementById('confirm-ok').textContent = form.dataset.confirmLabel || 'Confirmer';
            dialog.returnValue = '';
            dialog.addEventListener('close', function onClose() {
                dialog.removeEventListener('close', onClose);
                if (dialog.returnValue === 'ok') {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
            });
            dialog.showModal();
        });
    });

    async function copyText(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (e) {
            const tmp = document.createElement('textarea');
            tmp.value = text;
            document.body.appendChild(tmp);
            tmp.select();
            const ok = document.execCommand('copy');
            tmp.remove();
            return ok;
        }
    }

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const source = button.dataset.copy;
            const target = source.startsWith('#') ? document.querySelector(source) : null;
            const text = target ? target.value : source;
            const ok = await copyText(text);
            toast(ok ? 'Lien copié dans le presse-papiers.' : 'Copie impossible, sélectionnez le lien à la main.', ok ? 'success' : 'error');
        });
    });

    document.querySelectorAll('form[data-toggle]').forEach((form) => {
        const button = form.querySelector('button[role="switch"]');
        const label = document.getElementById(button.getAttribute('aria-labelledby'));

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'fetch' },
                });
                if (!response.ok) {
                    throw new Error('http ' + response.status);
                }
                const data = await response.json();
                button.setAttribute('aria-checked', data.actif ? 'true' : 'false');
                label.textContent = data.actif ? button.dataset.textOn : button.dataset.textOff;
                toast(data.actif ? 'Le lien de partage est actif.' : 'Le lien de partage est désactivé.');
            } catch (e) {
                toast('Le changement a échoué, réessayez.', 'error');
            } finally {
                button.disabled = false;
            }
        });
    });

    const cards = document.querySelector('[data-cards]');
    if (cards) {
        const search = document.querySelector('[data-search]');
        const chips = document.querySelectorAll('[data-filter]');
        const empty = document.querySelector('[data-empty]');
        const count = document.querySelector('[data-count]');
        let statut = 'tous';

        function apply() {
            const q = search.value.trim().toLowerCase();
            let visibles = 0;
            cards.querySelectorAll('.qcm-card').forEach((card) => {
                const ok = card.dataset.titre.includes(q) && (statut === 'tous' || card.dataset.statut === statut);
                card.hidden = !ok;
                if (ok) {
                    visibles++;
                }
            });
            empty.hidden = visibles > 0;
            count.textContent = visibles + (visibles > 1 ? ' QCM affichés' : ' QCM affiché');
        }

        search.addEventListener('input', apply);
        chips.forEach((chip) => chip.addEventListener('click', () => {
            statut = chip.dataset.filter;
            chips.forEach((c) => c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'));
            apply();
        }));
    }

    const filtres = document.querySelector('[data-filtres]');
    if (filtres) {
        filtres.querySelectorAll('select').forEach((liste) => {
            liste.addEventListener('change', () => {
                [...filtres.querySelectorAll('select')].filter((l) => l.value === '').forEach((l) => l.removeAttribute('name'));
                filtres.submit();
            });
        });
        filtres.addEventListener('submit', (event) => event.preventDefault());
    }

    document.querySelectorAll('[data-no-copy]').forEach((zone) => {
        ['copy', 'cut', 'contextmenu', 'dragstart', 'selectstart'].forEach((type) => {
            zone.addEventListener(type, (event) => event.preventDefault());
        });
    });

    document.querySelectorAll('[data-donut]').forEach((donut) => {
        const value = donut.querySelector('.value');
        const pct = Number(donut.dataset.donut);
        value.setAttribute('stroke-dasharray', '0 100');
        requestAnimationFrame(() => requestAnimationFrame(() => value.setAttribute('stroke-dasharray', pct + ' 100')));
    });
})();
