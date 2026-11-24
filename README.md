# Z&L Nexus Academy

Application web pour créer des QCM, les partager par lien et consulter les résultats.
Projet du titre RNCP 6 Développeur Web et Web Mobile.

## Technologies

PHP (architecture MVC), MySQL (PDO), MongoDB (statistiques et journal), HTML, CSS, JavaScript.

- **MySQL** contient les données principales : intervenants, QCM, questions, réponses, résultats.
- **MongoDB** contient des données dérivées : statistiques par question et journal d'événements.
  Il est facultatif : sans lui, le site fonctionne et la page Statistiques l'indique.

## Installation en local (XAMPP)

1. Placer le projet dans `htdocs/qcm`.
2. Démarrer Apache et MySQL.
3. Importer `database/qcm.sql` dans phpMyAdmin.
4. Copier `config/config.example.php` vers `config/config.php` et renseigner les identifiants (dont `db_port` si MySQL n'écoute pas sur 3306)
   et, pour l'email « mot de passe oublié », le bloc `mail` (serveur SMTP). Sans SMTP et avec `debug` à `true`,
   le lien de réinitialisation est écrit dans le journal d'erreurs PHP.
5. Renseigner les informations de l'entreprise dans `config/legal.php` (mentions légales et confidentialité).
6. Installer les bibliothèques : `composer install`.
7. Ouvrir `http://localhost/qcm/public/`.

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

Créer les collections, leurs règles de validation et leurs index une seule fois (le script lit `mongo_uri` dans `config/config.php`, local ou Atlas) :

```
php database/mongo-init.php
```

Le script peut être relancé sans risque. Il met en place :

- un schéma de validation (`$jsonSchema`) sur `stats_questions` et `journal` : MongoDB refuse un document mal formé, un type d'événement inconnu ou un nombre de bonnes réponses supérieur au total ;
- un index unique `id_qcm` + `id_question` : un seul document de statistiques par question, même si deux passages se terminent au même moment ;
- un index TTL sur `journal.date` : MongoDB efface lui-même les événements au bout d'un an (durée de conservation RGPD).

Pour désactiver MongoDB, laisser `mongo_uri` vide.

## Déploiement sur Railway

Le `Dockerfile` construit une image PHP 8.2 + Apache avec les extensions `pdo_mysql` et `mongodb`.
En ligne, il n'y a pas de `config/config.php` : la configuration est lue dans les variables d'environnement.

1. Créer un projet Railway depuis le dépôt GitHub, dans une région européenne.
2. Ajouter un service **MySQL** au projet, puis importer `database/qcm.sql` (onglet Data, ou `mysql` avec l'adresse publique fournie par Railway).
3. Dans le service de l'application, onglet Variables :

| Variable | Valeur |
|---|---|
| `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD` | références vers le service MySQL (`${{MySQL.MYSQLHOST}}`, etc.) |
| `MYSQLDATABASE` | `qcm` (la base créée par `qcm.sql`) |
| `MONGO_URI`, `MONGO_DB` | adresse MongoDB Atlas et nom de la base (`qcm`) |
| `APP_URL` | adresse publique du site, par exemple `https://qcm.exemple.fr` |
| `HTTPS_PROXY_HEADER` | `1` (Railway termine le HTTPS devant l'application) |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_SECURE` | serveur d'envoi des emails (`tls` sur le port 587, `ssl` sur le port 465) |
| `MAIL_FROM`, `MAIL_FROM_NAME` | adresse et nom d'expéditeur |

4. Dans MongoDB Atlas, menu Network Access, autoriser l'accès depuis Railway.
5. Lancer `php database/mongo-init.php` une fois, en local avec l'adresse Atlas dans `config/config.php`.

## Structure

- `public/` : point d'entrée et fichiers publics (css, js)
- `app/Controllers`, `app/Models`, `app/Views` : le MVC
- `app/Core` : routeur, connexions aux bases (`Database` pour MySQL, `Mongo` pour MongoDB), contrôleur de base
- `app/Repositories` : accès aux données MongoDB (`StatsRepository` : statistiques par question et journal)
- `app/Services` : logique métier indépendante de la base (`Scoring` : correction et notes sur 20, `Validation` : règles des formulaires)
- `tests/Unit` : tests unitaires PHPUnit de la logique métier
- `config/` : routes et configuration
- `database/` : création de la base MySQL (`qcm.sql`) et initialisation de MongoDB (`mongo-init.php`)

## Tests

Les tests unitaires utilisent PHPUnit, installé avec Composer (dépendance de développement uniquement).

```
composer install
vendor/bin/phpunit
```
