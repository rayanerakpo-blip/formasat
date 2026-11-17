-- Trigger 3 : bloque les dates incohérentes sur les modules.
-- La règle métier est simple : si les deux dates sont renseignées,
-- date_fin doit être après date_debut.
-- SIGNAL SQLSTATE '45000' permet de provoquer une erreur MySQL personnalisée
-- et donc d'annuler l'INSERT ou l'UPDATE.

DROP TRIGGER IF EXISTS trg_check_dates_module_insert;
DROP TRIGGER IF EXISTS trg_check_dates_module_update;

DELIMITER //

CREATE TRIGGER trg_check_dates_module_insert
BEFORE INSERT ON module
FOR EACH ROW
BEGIN
    IF NEW.date_debut IS NOT NULL
       AND NEW.date_fin IS NOT NULL
       AND NEW.date_fin <= NEW.date_debut THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Erreur : date_fin doit être postérieure à date_debut';
    END IF;
END //

CREATE TRIGGER trg_check_dates_module_update
BEFORE UPDATE ON module
FOR EACH ROW
BEGIN
    IF NEW.date_debut IS NOT NULL
       AND NEW.date_fin IS NOT NULL
       AND NEW.date_fin <= NEW.date_debut THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Erreur : date_fin doit être postérieure à date_debut';
    END IF;
END //

DELIMITER ;
