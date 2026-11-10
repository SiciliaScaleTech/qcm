# Plateforme de QCM en ligne

Application web pour créer des QCM, les partager par lien et consulter les résultats.
Projet du titre RNCP 6 Développeur Web et Web Mobile.

## Technologies

PHP (architecture MVC), MySQL (PDO), HTML, CSS, JavaScript.

## Installation en local (XAMPP)

1. Placer le projet dans `htdocs/qcm`.
2. Démarrer Apache et MySQL.
3. Importer `database/qcm.sql` dans phpMyAdmin.
4. Copier `config/config.example.php` vers `config/config.php` et renseigner les identifiants.
5. Ouvrir `http://localhost/qcm/public/`.

## Structure

- `public/` : point d'entrée et fichiers publics (css, js)
- `app/Controllers`, `app/Models`, `app/Views` : le MVC
- `app/Core` : routeur, connexion à la base, contrôleur de base
- `config/` : routes et configuration
- `database/` : script de création de la base
