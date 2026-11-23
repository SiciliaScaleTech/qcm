CREATE DATABASE IF NOT EXISTS qcm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE qcm;

CREATE TABLE formateur (
    id_formateur INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    echecs_connexion TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloque_jusqu_a DATETIME NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_formateur),
    UNIQUE KEY uq_formateur_email (email),
    CONSTRAINT ck_formateur_nom CHECK (CHAR_LENGTH(TRIM(nom)) > 0),
    CONSTRAINT ck_formateur_email CHECK (email LIKE '_%@_%._%')
) ENGINE=InnoDB;

CREATE TABLE categorie (
    id_categorie INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_categorie),
    UNIQUE KEY uq_categorie_nom (nom)
) ENGINE=InnoDB;

INSERT INTO categorie (id_categorie, nom) VALUES
    (1, 'Cybersécurité'),
    (2, 'Développement');

CREATE TABLE qcm (
    id_qcm INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre VARCHAR(150) NOT NULL,
    description TEXT NULL,
    slug VARCHAR(160) NOT NULL,
    annee_scolaire CHAR(9) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    id_formateur INT UNSIGNED NOT NULL,
    id_categorie INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_qcm),
    UNIQUE KEY uq_qcm_slug (slug),
    KEY idx_qcm_categorie (id_categorie),
    KEY idx_qcm_formateur (id_formateur, annee_scolaire),
    CONSTRAINT ck_qcm_titre CHECK (CHAR_LENGTH(TRIM(titre)) > 0),
    CONSTRAINT ck_qcm_slug CHECK (slug REGEXP '^[a-z0-9-]+$'),
    CONSTRAINT ck_qcm_annee CHECK (
        annee_scolaire REGEXP '^[0-9]{4}-[0-9]{4}$'
        AND CAST(SUBSTRING(annee_scolaire, 6, 4) AS UNSIGNED) = CAST(SUBSTRING(annee_scolaire, 1, 4) AS UNSIGNED) + 1
    ),
    CONSTRAINT ck_qcm_actif CHECK (actif IN (0, 1)),
    CONSTRAINT fk_qcm_formateur
        FOREIGN KEY (id_formateur) REFERENCES formateur (id_formateur)
        ON DELETE CASCADE,
    CONSTRAINT fk_qcm_categorie
        FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)
) ENGINE=InnoDB;

CREATE TABLE classe (
    id_classe INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    id_formateur INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_classe),
    UNIQUE KEY uq_classe_nom (id_formateur, nom),
    CONSTRAINT ck_classe_nom CHECK (CHAR_LENGTH(TRIM(nom)) > 0),
    CONSTRAINT fk_classe_formateur
        FOREIGN KEY (id_formateur) REFERENCES formateur (id_formateur)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE concerner (
    id_qcm INT UNSIGNED NOT NULL,
    id_classe INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_qcm, id_classe),
    KEY idx_concerner_classe (id_classe),
    CONSTRAINT fk_concerner_qcm
        FOREIGN KEY (id_qcm) REFERENCES qcm (id_qcm)
        ON DELETE CASCADE,
    CONSTRAINT fk_concerner_classe
        FOREIGN KEY (id_classe) REFERENCES classe (id_classe)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE question (
    id_question INT UNSIGNED NOT NULL AUTO_INCREMENT,
    enonce TEXT NOT NULL,
    ordre INT UNSIGNED NOT NULL DEFAULT 1,
    id_qcm INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_question),
    UNIQUE KEY uq_question_ordre (id_qcm, ordre),
    CONSTRAINT ck_question_enonce CHECK (CHAR_LENGTH(TRIM(enonce)) > 0),
    CONSTRAINT ck_question_ordre CHECK (ordre > 0),
    CONSTRAINT fk_question_qcm
        FOREIGN KEY (id_qcm) REFERENCES qcm (id_qcm)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reponse (
    id_reponse INT UNSIGNED NOT NULL AUTO_INCREMENT,
    texte VARCHAR(255) NOT NULL,
    est_correct TINYINT(1) NOT NULL DEFAULT 0,
    id_question INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_reponse),
    KEY idx_reponse_question (id_question),
    CONSTRAINT ck_reponse_texte CHECK (CHAR_LENGTH(TRIM(texte)) > 0),
    CONSTRAINT ck_reponse_correct CHECK (est_correct IN (0, 1)),
    CONSTRAINT fk_reponse_question
        FOREIGN KEY (id_question) REFERENCES question (id_question)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Un étudiant ne passe un QCM qu'une fois : l'interclassement _ci rend
-- « Dupont Léa » et « DUPONT lea » identiques pour la clé unique.
CREATE TABLE tentative (
    id_tentative INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_etudiant VARCHAR(100) NOT NULL,
    prenom_etudiant VARCHAR(100) NOT NULL,
    score INT UNSIGNED NOT NULL DEFAULT 0,
    nb_questions INT UNSIGNED NOT NULL,
    date_passage DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_qcm INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_tentative),
    UNIQUE KEY uq_tentative_etudiant (id_qcm, nom_etudiant, prenom_etudiant),
    KEY idx_tentative_date (date_passage),
    CONSTRAINT ck_tentative_score CHECK (score <= nb_questions),
    CONSTRAINT ck_tentative_nb CHECK (nb_questions > 0),
    CONSTRAINT fk_tentative_qcm
        FOREIGN KEY (id_qcm) REFERENCES qcm (id_qcm)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE choisir (
    id_tentative INT UNSIGNED NOT NULL,
    id_reponse INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_tentative, id_reponse),
    KEY idx_choisir_reponse (id_reponse),
    CONSTRAINT fk_choisir_tentative
        FOREIGN KEY (id_tentative) REFERENCES tentative (id_tentative)
        ON DELETE CASCADE,
    CONSTRAINT fk_choisir_reponse
        FOREIGN KEY (id_reponse) REFERENCES reponse (id_reponse)
        ON DELETE CASCADE
) ENGINE=InnoDB;

DELIMITER //

CREATE TRIGGER trg_reponse_unique_correcte_insert
BEFORE INSERT ON reponse
FOR EACH ROW
BEGIN
    IF NEW.est_correct = 1 AND EXISTS (
        SELECT 1 FROM reponse WHERE id_question = NEW.id_question AND est_correct = 1
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Une question ne peut avoir qu''une seule bonne réponse.';
    END IF;
END//

CREATE TRIGGER trg_reponse_unique_correcte_update
BEFORE UPDATE ON reponse
FOR EACH ROW
BEGIN
    IF NEW.est_correct = 1 AND EXISTS (
        SELECT 1 FROM reponse
        WHERE id_question = NEW.id_question AND est_correct = 1 AND id_reponse <> NEW.id_reponse
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Une question ne peut avoir qu''une seule bonne réponse.';
    END IF;
END//

DELIMITER ;
