-- Trigger 4 : initialise une ligne de statistiques quand un module est créé.
-- Même si le module n'a encore aucune évaluation, le dashboard peut afficher
-- nb_evaluations = 0 et des moyennes à 0.

DROP TRIGGER IF EXISTS trg_init_stats_module;

DELIMITER //

CREATE TRIGGER trg_init_stats_module
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
END //

DELIMITER ;
