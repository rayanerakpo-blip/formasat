-- Trigger 2 : garde une trace avant la suppression d'un élève.
-- Sur un DELETE, les valeurs de la ligne supprimée sont disponibles avec OLD.
-- Le trigger s'exécute BEFORE DELETE pour copier les informations avant leur perte.

DROP TRIGGER IF EXISTS trg_log_suppr_etudiant;

DELIMITER //

CREATE TRIGGER trg_log_suppr_etudiant
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
END //

DELIMITER ;
