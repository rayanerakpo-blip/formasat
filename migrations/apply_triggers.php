<?php
// Script d'installation des triggers FormaSat v2.
// Tu peux le relancer sans casser la base : les tables sont créées si besoin
// et les triggers sont supprimés puis recréés proprement.

require_once __DIR__ . '/../connexion.php';

$pdo = getConnexion();

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

// Les colonnes date_debut et date_fin servent au trigger de validation des dates.
if (!columnExists($pdo, 'module', 'date_debut')) {
    $pdo->exec('ALTER TABLE module ADD COLUMN date_debut DATE NULL');
}

if (!columnExists($pdo, 'module', 'date_fin')) {
    $pdo->exec('ALTER TABLE module ADD COLUMN date_fin DATE NULL');
}

// stats_module stocke les moyennes déjà calculées pour chaque module.
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS stats_module (
        id_module INT NOT NULL PRIMARY KEY,
        nb_evaluations INT NOT NULL DEFAULT 0,
        moy_contenu DECIMAL(3,2) NOT NULL DEFAULT 0,
        moy_pedagogie DECIMAL(3,2) NOT NULL DEFAULT 0,
        moy_support DECIMAL(3,2) NOT NULL DEFAULT 0,
        moy_rythme DECIMAL(3,2) NOT NULL DEFAULT 0,
        moy_applicabilite DECIMAL(3,2) NOT NULL DEFAULT 0,
        moy_globale DECIMAL(3,2) NOT NULL DEFAULT 0,
        mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )'
);

// log_suppression_etudiant conserve une trace quand un élève est supprimé.
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS log_suppression_etudiant (
        id_log INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_etudiant INT NULL,
        prenom_etudiant VARCHAR(100) NULL,
        nom_etudiant VARCHAR(100) NULL,
        email_etudiant VARCHAR(150) NULL,
        id_promotion INT NULL,
        id_admin INT NULL,
        nb_evaluations INT NOT NULL DEFAULT 0,
        supprime_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

// On initialise les statistiques des modules déjà présents avant l'installation.
$pdo->exec(
    'INSERT INTO stats_module (
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
        mise_a_jour = NOW()'
);

// On recrée les triggers pour être sûr que leur code correspond aux fichiers SQL.
$triggers = [
    'trg_maj_stats',
    'trg_log_suppr_etudiant',
    'trg_check_dates_module_insert',
    'trg_check_dates_module_update',
    'trg_init_stats_module',
];

foreach ($triggers as $trigger) {
    $pdo->exec("DROP TRIGGER IF EXISTS $trigger");
}

$pdo->exec(
    'CREATE TRIGGER trg_maj_stats
     AFTER INSERT ON evaluation
     FOR EACH ROW
     BEGIN
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
            NEW.id_module,
            COUNT(*),
            ROUND(AVG(note_contenu), 2),
            ROUND(AVG(note_pedagogie), 2),
            ROUND(AVG(note_support), 2),
            ROUND(AVG(note_rythme), 2),
            ROUND(AVG(note_applicabilite), 2),
            ROUND(AVG((note_contenu + note_pedagogie + note_support + note_rythme + note_applicabilite) / 5), 2),
            NOW()
        FROM evaluation
        WHERE id_module = NEW.id_module
        ON DUPLICATE KEY UPDATE
            nb_evaluations = VALUES(nb_evaluations),
            moy_contenu = VALUES(moy_contenu),
            moy_pedagogie = VALUES(moy_pedagogie),
            moy_support = VALUES(moy_support),
            moy_rythme = VALUES(moy_rythme),
            moy_applicabilite = VALUES(moy_applicabilite),
            moy_globale = VALUES(moy_globale),
            mise_a_jour = NOW();
     END'
);

$pdo->exec(
    'CREATE TRIGGER trg_log_suppr_etudiant
     BEFORE DELETE ON etudiant
     FOR EACH ROW
     BEGIN
        INSERT INTO log_suppression_etudiant (
            id_etudiant,
            prenom_etudiant,
            nom_etudiant,
            email_etudiant,
            id_promotion,
            id_admin,
            nb_evaluations
        )
        VALUES (
            OLD.id_etudiant,
            OLD.prenom,
            OLD.nom,
            OLD.email,
            OLD.id_promotion,
            OLD.id_admin,
            (SELECT COUNT(*) FROM evaluation WHERE id_etudiant = OLD.id_etudiant)
        );
     END'
);

$pdo->exec(
    'CREATE TRIGGER trg_check_dates_module_insert
     BEFORE INSERT ON module
     FOR EACH ROW
     BEGIN
        IF NEW.date_debut IS NOT NULL
           AND NEW.date_fin IS NOT NULL
           AND NEW.date_fin <= NEW.date_debut THEN
            SIGNAL SQLSTATE \'45000\'
                SET MESSAGE_TEXT = \'Erreur : date_fin doit être postérieure à date_debut\';
        END IF;
     END'
);

$pdo->exec(
    'CREATE TRIGGER trg_check_dates_module_update
     BEFORE UPDATE ON module
     FOR EACH ROW
     BEGIN
        IF NEW.date_debut IS NOT NULL
           AND NEW.date_fin IS NOT NULL
           AND NEW.date_fin <= NEW.date_debut THEN
            SIGNAL SQLSTATE \'45000\'
                SET MESSAGE_TEXT = \'Erreur : date_fin doit être postérieure à date_debut\';
        END IF;
     END'
);

$pdo->exec(
    'CREATE TRIGGER trg_init_stats_module
     AFTER INSERT ON module
     FOR EACH ROW
     BEGIN
        INSERT IGNORE INTO stats_module (
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
        VALUES (
            NEW.id_module,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            NOW()
        );
     END'
);

echo "Installation des triggers terminee.\n";
