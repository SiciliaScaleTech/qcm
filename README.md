# Plateforme de QCM en ligne

Application web pour créer des QCM, les partager par lien et consulter les résultats.
Projet du titre RNCP 6 Développeur Web et Web Mobile.

## Technologies

PHP (architecture MVC), MySQL (PDO), MongoDB (statistiques et journal), HTML, CSS, JavaScript.

- **MySQL** contient les données principales : formateurs, QCM, questions, réponses, résultats.
- **MongoDB** contient des données dérivées : statistiques par question et journal d'événements.
  Il est facultatif : sans lui, le site fonctionne et la page Statistiques l'indique.

## Installation en local (XAMPP)

1. Placer le projet dans `htdocs/qcm`.
2. Démarrer Apache et MySQL.
3. Importer `database/qcm.sql` dans phpMyAdmin.
4. Copier `config/config.example.php` vers `config/config.php` et renseigner les identifiants.
5. Installer les bibliothèques : `composer install`.
6. Ouvrir `http://localhost/qcm/public/`.

## Installation de MongoDB (Windows, XAMPP)

### 1. Le serveur MongoDB

1. Télécharger **MongoDB Community Server** (paquet `.msi`) sur [mongodb.com/try/download/community](https://www.mongodb.com/try/download/community).
2. Pendant l'installation, garder « Install MongoD as a Service » : MongoDB démarre avec Windows sur le port 27017.
3. Installer aussi **mongosh** (le terminal MongoDB), proposé sur la même page.

### 2. L'extension PHP `mongodb`

La bibliothèque `mongodb/mongodb` (installée par Composer) a besoin de l'extension PHP `mongodb`, version 2.5 ou plus.

1. Vérifier sa version de PHP : `C:\xampp\php\php.exe -i | findstr /C:"PHP Version" /C:"Thread Safety" /C:"Architecture"`.
   Avec XAMPP 8.2 : PHP 8.2, Thread Safety `enabled` (donc **ts**), architecture x64.
2. Sur [github.com/mongodb/mongo-php-driver/releases](https://github.com/mongodb/mongo-php-driver/releases), télécharger le fichier correspondant,
   par exemple `php_mongodb-2.5.7-8.2-ts-vs16-x86_64.zip` (8.2 = version de PHP, ts = thread safe, x86_64 = 64 bits).
3. Extraire la DLL, la renommer `php_mongodb.dll` et la copier dans `C:\xampp\php\ext\`.
4. Ouvrir `C:\xampp\php\php.ini` et ajouter, avec les autres lignes `extension=` : `extension=mongodb`.
5. Redémarrer Apache depuis le panneau XAMPP.
6. Vérifier : `C:\xampp\php\php.exe -m | findstr mongodb` doit afficher `mongodb`.

### 3. Configuration et index

Dans `config/config.php` :

```php
'mongo_uri' => 'mongodb://127.0.0.1:27017',
'mongo_db' => 'qcm',
```

Créer les index une seule fois avec `mongosh` :

```js
use qcm
db.stats_questions.createIndex({ id_qcm: 1, id_question: 1 }, { unique: true })
db.journal.createIndex({ id_formateur: 1, date: -1 })
```

Le premier index garantit un seul document par question, même si deux passages se terminent au même moment.

Pour désactiver MongoDB, laisser `mongo_uri` vide.

## Structure

- `public/` : point d'entrée et fichiers publics (css, js)
- `app/Controllers`, `app/Models`, `app/Views` : le MVC
- `app/Core` : routeur, connexions aux bases (`Database` pour MySQL, `Mongo` pour MongoDB), contrôleur de base
- `app/Repositories` : accès aux données MongoDB (`StatsRepository` : statistiques par question et journal)
- `app/Services` : logique métier indépendante de la base (`Scoring` : correction et notes sur 20, `Validation` : règles des formulaires)
- `tests/Unit` : tests unitaires PHPUnit de la logique métier
- `config/` : routes et configuration
- `database/` : script de création de la base

## Tests

Les tests unitaires utilisent PHPUnit, installé avec Composer (dépendance de développement uniquement).

```
composer install
vendor/bin/phpunit
```
