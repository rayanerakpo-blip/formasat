<?php
// evaluation_traitement.php
session_start();

if (!isset($_SESSION['etudiant_id'])) {
    header('Location: login.html?erreur=' . urlencode('Vous devez être connecté pour accéder à cette page.'));
    exit();
}

require_once 'connexion.php';
$pdo = getConnexion();

$etudiant_id = (int) $_SESSION['etudiant_id'];
$module_id   = (int) ($_POST['module_id'] ?? 0);

$notes = [];
$criteres = ['contenu', 'pedagogie', 'support', 'rythme', 'applicabilite'];

foreach ($criteres as $c) {
    $val = (int) ($_POST["note_$c"] ?? 0);
    if ($val < 1 || $val > 5) {
        header('Location: evaluation.php?erreur=' . urlencode('Veuillez noter tous les critères (1 à 5).'));
        exit();
    }
    $notes[$c] = $val;
}

if ($module_id <= 0) {
    header('Location: evaluation.php?erreur=' . urlencode('Veuillez sélectionner un module.'));
    exit();
}

$commentaire = trim($_POST['commentaire'] ?? '');

$stmt = $pdo->prepare('SELECT id_module FROM module WHERE id_module = ?');
$stmt->execute([$module_id]);
if (!$stmt->fetch()) {
    header('Location: evaluation.php?erreur=' . urlencode('Module introuvable.'));
    exit();
}

$stmt = $pdo->prepare('SELECT id_evaluation FROM evaluation WHERE id_etudiant = ? AND id_module = ?');
$stmt->execute([$etudiant_id, $module_id]);
if ($stmt->fetch()) {
    header('Location: evaluation.php?erreur=' . urlencode('Vous avez déjà évalué ce module.'));
    exit();
}

$stmt = $pdo->prepare(
    'INSERT INTO evaluation
        (id_etudiant, id_module, note_contenu, note_pedagogie, note_support, note_rythme, note_applicabilite, commentaire)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $etudiant_id,
    $module_id,
    $notes['contenu'],
    $notes['pedagogie'],
    $notes['support'],
    $notes['rythme'],
    $notes['applicabilite'],
    $commentaire ?: null,
]);

header('Location: evaluation.php?succes=' . urlencode('Votre évaluation a bien été enregistrée. Merci !'));
exit();
