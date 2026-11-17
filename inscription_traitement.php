<?php
// inscription_traitement.php
require_once 'connexion.php';

$prenom  = trim($_POST['prenom'] ?? '');
$nom     = trim($_POST['nom'] ?? '');
$email   = trim($_POST['email'] ?? '');
$mdp     = $_POST['mot_de_passe'] ?? '';
$confirm = $_POST['confirmation'] ?? '';

if (empty($prenom) || empty($nom) || empty($email) || empty($mdp) || empty($confirm)) {
    header('Location: inscription.html?erreur=' . urlencode('Tous les champs sont obligatoires.'));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: inscription.html?erreur=' . urlencode('Adresse email invalide.'));
    exit();
}

if (strlen($mdp) < 8) {
    header('Location: inscription.html?erreur=' . urlencode('Le mot de passe doit contenir au moins 8 caractères.'));
    exit();
}

if ($mdp !== $confirm) {
    header('Location: inscription.html?erreur=' . urlencode('Les mots de passe ne correspondent pas.'));
    exit();
}

$pdo = getConnexion();
$stmt = $pdo->prepare('SELECT id_etudiant FROM etudiant WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header('Location: inscription.html?erreur=' . urlencode('Cette adresse email est déjà utilisée.'));
    exit();
}

$hash = password_hash($mdp, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO etudiant (prenom, nom, email, mot_de_passe) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$prenom, $nom, $email, $hash]);

header('Location: login.html?succes=' . urlencode('Compte créé avec succès ! Vous pouvez vous connecter.'));
exit();
