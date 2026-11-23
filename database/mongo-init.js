const nomBase = typeof base !== 'undefined' ? base : 'qcm';
const cible = db.getSiblingDB(nomBase);

const entier = { bsonType: ['int', 'long'], minimum: 0 };

const schemas = {
    stats_questions: {
        $and: [
            {
                $jsonSchema: {
                    bsonType: 'object',
                    required: ['id_qcm', 'id_question', 'total', 'justes'],
                    properties: {
                        id_qcm: { bsonType: ['int', 'long'], minimum: 1 },
                        id_question: { bsonType: ['int', 'long'], minimum: 1 },
                        total: entier,
                        justes: entier,
                        sans_reponse: entier,
                        options: {
                            bsonType: 'object',
                            additionalProperties: entier,
                        },
                        taux_reussite: { bsonType: ['double', 'int', 'long', 'decimal'], minimum: 0, maximum: 100 },
                        mis_a_jour: { bsonType: 'date' },
                    },
                },
            },
            { $expr: { $lte: ['$justes', '$total'] } },
        ],
    },
    journal: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['type', 'date'],
            properties: {
                type: { enum: ['connexion', 'qcm_cree', 'passage_termine'] },
                date: { bsonType: 'date' },
                id_formateur: { bsonType: ['int', 'long', 'null'] },
                id_qcm: { bsonType: ['int', 'long'] },
                id_tentative: { bsonType: ['int', 'long'] },
                score: entier,
                nb_questions: { bsonType: ['int', 'long'], minimum: 1 },
                titre: { bsonType: 'string', maxLength: 150 },
                annee_scolaire: { bsonType: 'string', pattern: '^[0-9]{4}-[0-9]{4}$' },
            },
        },
    },
};

const existantes = cible.getCollectionNames();
Object.entries(schemas).forEach(([nom, validator]) => {
    const options = { validator, validationLevel: 'strict', validationAction: 'error' };
    if (existantes.includes(nom)) {
        cible.runCommand({ collMod: nom, ...options });
    } else {
        cible.createCollection(nom, options);
    }
});

cible.stats_questions.createIndex({ id_qcm: 1, id_question: 1 }, { unique: true, name: 'uq_qcm_question' });
cible.stats_questions.createIndex({ id_question: 1 }, { name: 'idx_question' });
cible.journal.createIndex({ id_formateur: 1, date: -1 }, { name: 'idx_formateur_date' });
cible.journal.createIndex({ date: 1 }, { expireAfterSeconds: 31536000, name: 'ttl_un_an' });

print('Base ' + nomBase + ' prête : ' + cible.getCollectionNames().join(', '));
