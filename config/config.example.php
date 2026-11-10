<?php
// Modèle de configuration, sans mot de passe.
// Copier ce fichier vers config/config.php puis renseigner les vraies valeurs.
// config/config.php n'est jamais envoyé sur GitHub (voir .gitignore).

return [
    'db_host' => 'localhost',
    'db_name' => 'qcm',
    'db_user' => 'root',
    'db_pass' => '',

    // Adresse publique du site, utilisée pour le lien donné aux étudiants.
    // Laisser vide en local. En ligne, par exemple : 'https://qcm.horizon-formation.fr'
    'app_url' => '',

    // true en local pour voir les erreurs PHP, toujours false en ligne
    'debug' => false,

    // MongoDB (statistiques et journal). Laisser mongo_uri vide pour désactiver.
    'mongo_uri' => 'mongodb://127.0.0.1:27017',
    'mongo_db' => 'qcm',
];
