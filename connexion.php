<?php
// connexion.php

function getConnexion() {
    // Valeurs par défaut locales ; surchargeables par variables d'environnement.
    $host   = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'formatsatbd';
    $user   = getenv('DB_USER') ?: 'projet';
    $pass   = getenv('DB_PASS') ?: '';
    $dsn    = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die('Erreur connexion : ' . $e->getMessage());
    }
}
