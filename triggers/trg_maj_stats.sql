-- Trigger 1 : met à jour automatiquement les statistiques d'un module.
-- Il se déclenche après l'ajout d'une évaluation, quand les notes existent déjà
-- dans la table evaluation. On peut donc recalculer les moyennes du module.

DROP TRIGGER IF EXISTS trg_maj_stats;

DELIMITER //

CREATE TRIGGER trg_maj_stats
AFTER INSERT ON evaluation
FOR EACH ROW
BEGIN
    -- NEW.id_module représente le module qui vient d'être évalué.
    -- Si la ligne existe déjà dans stats_module, ON DUPLICATE KEY UPDATE
    -- remplace les anciennes valeurs par les nouvelles moyennes.
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
END //

DELIMITER ;
