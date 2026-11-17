-- Migration FormaSat v2 : tables techniques + triggers MySQL.
-- Ce fichier regroupe tout ce qu'il faut exécuter en base pour installer
-- les automatisations demandées dans le document de TP.

-- Dates de module :
-- elles servent au trigger qui refuse une date de fin avant la date de début.
ALTER TABLE module
    ADD COLUMN IF NOT EXISTS date_debut DATE NULL,
    ADD COLUMN IF NOT EXISTS date_fin DATE NULL;

-- Cache de statistiques par module.
-- Cette table évite de recalculer toutes les moyennes dans le PHP à chaque affichage.
CREATE TABLE IF NOT EXISTS stats_module (
    id_module INT NOT NULL PRIMARY KEY,
    nb_evaluations INT NOT NULL DEFAULT 0,
    moy_contenu DECIMAL(3,2) NOT NULL DEFAULT 0,
    moy_pedagogie DECIMAL(3,2) NOT NULL DEFAULT 0,
    moy_support DECIMAL(3,2) NOT NULL DEFAULT 0,
    moy_rythme DECIMAL(3,2) NOT NULL DEFAULT 0,
    moy_applicabilite DECIMAL(3,2) NOT NULL DEFAULT 0,
    moy_globale DECIMAL(3,2) NOT NULL DEFAULT 0,
    mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Journal des suppressions d'élèves.
-- On garde les informations utiles même après suppression de la ligne etudiant.
CREATE TABLE IF NOT EXISTS log_suppression_etudiant (
    id_log INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NULL,
    prenom_etudiant VARCHAR(100) NULL,
    nom_etudiant VARCHAR(100) NULL,
    email_etudiant VARCHAR(150) NULL,
    id_promotion INT NULL,
    id_admin INT NULL,
    nb_evaluations INT NOT NULL DEFAULT 0,
    supprime_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Initialisation des stats pour les modules déjà présents avant la migration.
INSERT INTO stats_module (
    id_module,
    nb_evaluations,
    moy_contenu,
    moy_pedagogie,
    moy_support,
    moy_rythme,
    moy_applicabilite,
    moy_globale,
    mise_a_jour
)
SELECT
    m.id_module,
    COUNT(e.id_evaluation),
    COALESCE(ROUND(AVG(e.note_contenu), 2), 0),
    COALESCE(ROUND(AVG(e.note_pedagogie), 2), 0),
    COALESCE(ROUND(AVG(e.note_support), 2), 0),
    COALESCE(ROUND(AVG(e.note_rythme), 2), 0),
    COALESCE(ROUND(AVG(e.note_applicabilite), 2), 0),
    COALESCE(ROUND(AVG((e.note_contenu + e.note_pedagogie + e.note_support + e.note_rythme + e.note_applicabilite) / 5), 2), 0),
    NOW()
FROM module m
LEFT JOIN evaluation e ON e.id_module = m.id_module
GROUP BY m.id_module
ON DUPLICATE KEY UPDATE
    nb_evaluations = VALUES(nb_evaluations),
    moy_contenu = VALUES(moy_contenu),
    moy_pedagogie = VALUES(moy_pedagogie),
    moy_support = VALUES(moy_support),
    moy_rythme = VALUES(moy_rythme),
    moy_applicabilite = VALUES(moy_applicabilite),
    moy_globale = VALUES(moy_globale),
    mise_a_jour = NOW();

-- Les triggers sont dans des fichiers séparés :
-- triggers/trg_maj_stats.sql
-- triggers/trg_log_suppr_etudiant.sql
-- triggers/trg_check_dates_module.sql
-- triggers/trg_init_stats_module.sql
