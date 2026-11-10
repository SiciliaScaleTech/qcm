-- Plateforme de QCM en ligne : création de la base de données
-- Import : phpMyAdmin > onglet Importer > choisir ce fichier

CREATE DATABASE IF NOT EXISTS qcm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE qcm;

-- Formateur : seul utilisateur avec un compte
CREATE TABLE formateur (
    id_formateur INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL, -- hash généré par password_hash
    echecs_connexion TINYINT UNSIGNED NOT NULL DEFAULT 0, -- mots de passe faux à la suite
    bloque_jusqu_a DATETIME NULL, -- compte bloqué jusqu'à cette date après trop d'échecs
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_formateur),
    UNIQUE KEY uq_formateur_email (email)
) ENGINE=InnoDB;

-- Catégorie d'un QCM (liste fixe, commune à tous les formateurs)
CREATE TABLE categorie (
    id_categorie INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_categorie),
    UNIQUE KEY uq_categorie_nom (nom)
) ENGINE=InnoDB;

INSERT INTO categorie (id_categorie, nom) VALUES
    (1, 'Cybersécurité'),
    (2, 'Développement');

-- QCM créé par un formateur, partagé par un lien construit à partir du titre
CREATE TABLE qcm (
    id_qcm INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre VARCHAR(150) NOT NULL,
    description TEXT NULL,
    slug VARCHAR(160) NOT NULL, -- titre simplifié pour l'URL, ex. "les-bases-du-html"
    annee_scolaire CHAR(9) NOT NULL, -- ex. "2026-2027"
    actif TINYINT(1) NOT NULL DEFAULT 1,
    id_formateur INT UNSIGNED NOT NULL,
    id_categorie INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_qcm),
    UNIQUE KEY uq_qcm_slug (slug),
    KEY idx_qcm_categorie (id_categorie),
    KEY idx_qcm_formateur (id_formateur),
    CONSTRAINT fk_qcm_formateur
        FOREIGN KEY (id_formateur) REFERENCES formateur (id_formateur)
        ON DELETE CASCADE,
    -- Une catégorie utilisée par des QCM ne peut pas être supprimée
    CONSTRAINT fk_qcm_categorie
        FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)
) ENGINE=InnoDB;

-- Classe d'étudiants, propre à chaque formateur (ex. "BTS SIO 1")
CREATE TABLE classe (
    id_classe INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    id_formateur INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_classe),
    UNIQUE KEY uq_classe_nom (id_formateur, nom),
    CONSTRAINT fk_classe_formateur
        FOREIGN KEY (id_formateur) REFERENCES formateur (id_formateur)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Classes concernées par un QCM (un QCM peut concerner plusieurs classes)
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

-- Question d'un QCM
CREATE TABLE question (
    id_question INT UNSIGNED NOT NULL AUTO_INCREMENT,
    enonce TEXT NOT NULL,
    ordre INT UNSIGNED NOT NULL DEFAULT 1,
    id_qcm INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_question),
    KEY idx_question_qcm (id_qcm),
    CONSTRAINT fk_question_qcm
        FOREIGN KEY (id_qcm) REFERENCES qcm (id_qcm)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Réponse proposée pour une question (4 par question, une seule correcte)
CREATE TABLE reponse (
    id_reponse INT UNSIGNED NOT NULL AUTO_INCREMENT,
    texte VARCHAR(255) NOT NULL,
    est_correct TINYINT(1) NOT NULL DEFAULT 0,
    id_question INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_reponse),
    KEY idx_reponse_question (id_question),
    CONSTRAINT fk_reponse_question
        FOREIGN KEY (id_question) REFERENCES question (id_question)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Passage d'un QCM par un étudiant (sans compte)
CREATE TABLE tentative (
    id_tentative INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_etudiant VARCHAR(100) NOT NULL,
    prenom_etudiant VARCHAR(100) NOT NULL,
    score INT UNSIGNED NOT NULL DEFAULT 0,
    nb_questions INT UNSIGNED NOT NULL, -- nombre de questions au moment du passage (pour la note sur 20)
    date_passage DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_qcm INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_tentative),
    KEY idx_tentative_qcm (id_qcm),
    CONSTRAINT fk_tentative_qcm
        FOREIGN KEY (id_qcm) REFERENCES qcm (id_qcm)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Réponses choisies par l'étudiant pendant une tentative
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
