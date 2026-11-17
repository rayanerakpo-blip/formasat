<?php
// login_traitement.php
session_start();
require_once 'connexion.php';

$email = trim($_POST['email'] ?? '');
$mdp   = $_POST['mot_de_passe'] ?? '';

if (empty($email) || empty($mdp)) {
    header('Location: login.html?erreur=' . urlencode('Veuillez remplir tous les champs.'));
    exit();
}

$pdo = getConnexion();
$stmt = $pdo->prepare('SELECT * FROM etudiant WHERE email = ?');
$stmt->execute([$email]);
$etudiant = $stmt->fetch();

if (!$etudiant || !password_verify($mdp, $etudiant['mot_de_passe'])) {
    header('Location: login.html?erreur=' . urlencode('Email ou mot de passe incorrect.'));
    exit();
}

session_regenerate_id(true);
$_SESSION['etudiant_id']  = $etudiant['id_etudiant'];
$_SESSION['etudiant_nom'] = $etudiant['prenom'] . ' ' . $etudiant['nom'];

header('Location: evaluation.php');
exit();
