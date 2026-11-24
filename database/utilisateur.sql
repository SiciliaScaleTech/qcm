-- Compte utilisé par l'application : uniquement la lecture et l'écriture des données de la base qcm.
-- Remplacer le mot de passe avant d'exécuter, puis le reporter dans config/config.php (db_user, db_pass).

CREATE USER IF NOT EXISTS 'qcm_app'@'localhost' IDENTIFIED BY 'CHANGER_CE_MOT_DE_PASSE';
CREATE USER IF NOT EXISTS 'qcm_app'@'127.0.0.1' IDENTIFIED BY 'CHANGER_CE_MOT_DE_PASSE';

GRANT SELECT, INSERT, UPDATE, DELETE ON qcm.* TO 'qcm_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON qcm.* TO 'qcm_app'@'127.0.0.1';

FLUSH PRIVILEGES;
