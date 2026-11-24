<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit;
}

define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/vendor/autoload.php';
require ROOT_PATH . '/app/Core/helpers.php';

$client = new MongoDB\Client((string) config('mongo_uri'), ['serverSelectionTimeoutMS' => 10000]);
$db = $client->selectDatabase((string) config('mongo_db', 'qcm'));

$entier = ['bsonType' => ['int', 'long'], 'minimum' => 0];

$schemas = [
    'stats_questions' => [
        '$and' => [
            [
                '$jsonSchema' => [
                    'bsonType' => 'object',
                    'required' => ['id_qcm', 'id_question', 'total', 'justes'],
                    'properties' => [
                        'id_qcm' => ['bsonType' => ['int', 'long'], 'minimum' => 1],
                        'id_question' => ['bsonType' => ['int', 'long'], 'minimum' => 1],
                        'total' => $entier,
                        'justes' => $entier,
                        'sans_reponse' => $entier,
                        'options' => ['bsonType' => 'object', 'additionalProperties' => $entier],
                        'taux_reussite' => ['bsonType' => ['double', 'int', 'long', 'decimal'], 'minimum' => 0, 'maximum' => 100],
                        'mis_a_jour' => ['bsonType' => 'date'],
                    ],
                ],
            ],
            ['$expr' => ['$lte' => ['$justes', '$total']]],
        ],
    ],
    'journal' => [
        '$jsonSchema' => [
            'bsonType' => 'object',
            'required' => ['type', 'date'],
            'properties' => [
                'type' => ['enum' => ['connexion', 'qcm_cree', 'passage_termine', 'mot_de_passe_reinitialise']],
                'date' => ['bsonType' => 'date'],
                'id_intervenant' => ['bsonType' => ['int', 'long', 'null']],
                'id_qcm' => ['bsonType' => ['int', 'long']],
                'id_classe' => ['bsonType' => ['int', 'long']],
                'id_tentative' => ['bsonType' => ['int', 'long']],
                'score' => $entier,
                'nb_questions' => ['bsonType' => ['int', 'long'], 'minimum' => 1],
                'titre' => ['bsonType' => 'string', 'maxLength' => 150],
                'annee_scolaire' => ['bsonType' => 'string', 'pattern' => '^[0-9]{4}-[0-9]{4}$'],
            ],
        ],
    ],
];

$existantes = iterator_to_array($db->listCollectionNames());
foreach ($schemas as $nom => $validator) {
    $options = ['validator' => $validator, 'validationLevel' => 'strict', 'validationAction' => 'error'];
    if (in_array($nom, $existantes, true)) {
        $db->command(['collMod' => $nom] + $options);
    } else {
        $db->createCollection($nom, $options);
    }
}

$db->selectCollection('stats_questions')->createIndex(['id_qcm' => 1, 'id_question' => 1], ['unique' => true, 'name' => 'uq_qcm_question']);
$db->selectCollection('stats_questions')->createIndex(['id_question' => 1], ['name' => 'idx_question']);
$db->selectCollection('journal')->createIndex(['id_intervenant' => 1, 'date' => -1], ['name' => 'idx_intervenant_date']);
$db->selectCollection('journal')->createIndex(['date' => 1], ['expireAfterSeconds' => 31536000, 'name' => 'ttl_un_an']);

echo 'Base ' . $db->getDatabaseName() . ' prête : ' . implode(', ', iterator_to_array($db->listCollectionNames())) . PHP_EOL;
