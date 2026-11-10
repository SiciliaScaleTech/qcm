// Demande une confirmation avant d'envoyer un formulaire marqué data-confirm
document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Sélectionne tout le lien de partage au clic pour le copier facilement
document.querySelectorAll('[data-select]').forEach((champ) => {
    champ.addEventListener('focus', () => champ.select());
});
