<?php
// admin_actions.php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require_once 'connexion.php';
$pdo = getConnexion();
$adminId = (int) $_SESSION['admin_id'];
$action = $_POST['action'] ?? '';

function redirectAdmin(string $type, string $message): void
{
    header('Location: admin_dashboard.php?' . $type . '=' . urlencode($message) . '#gestion');
    exit();
}

try {
    if ($action === 'add_promotion') {
        $nom = trim($_POST['nom'] ?? '');
        $annee = (int) ($_POST['annee'] ?? 0);

        if ($nom === '' || $annee < 2000 || $annee > 2100) {
            redirectAdmin('erreur', 'Veuillez renseigner une promotion et une année valides.');
        }

        $stmt = $pdo->prepare('INSERT INTO promotion (nom, annee) VALUES (?, ?)');
        $stmt->execute([$nom, $annee]);
        redirectAdmin('succes', 'Promotion ajoutée avec succès.');
    }

    if ($action === 'add_etudiant') {
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mdp = $_POST['mot_de_passe'] ?? '';
        $promotionId = (int) ($_POST['id_promotion'] ?? 0);

        if ($prenom === '' || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($mdp) < 8 || $promotionId <= 0) {
            redirectAdmin('erreur', 'Veuillez renseigner un élève complet avec un mot de passe de 8 caractères minimum.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO etudiant (prenom, nom, email, mot_de_passe, id_promotion, id_admin)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$prenom, $nom, $email, password_hash($mdp, PASSWORD_BCRYPT), $promotionId, $adminId]);
        redirectAdmin('succes', 'Élève ajouté et affecté à sa promotion.');
    }

    if ($action === 'add_professeur') {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $moduleId = (int) ($_POST['id_module'] ?? 0);

        if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectAdmin('erreur', 'Veuillez renseigner un professeur avec un email valide.');
        }

        $stmt = $pdo->prepare('INSERT INTO professeur (nom, email, id_module, id_admin) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nom, $email, $moduleId ?: null, $adminId]);
        $professeurId = (int) $pdo->lastInsertId();

        if ($moduleId > 0) {
            $stmt = $pdo->prepare('UPDATE module SET id_professeur = ? WHERE id_module = ?');
            $stmt->execute([$professeurId, $moduleId]);
        }

        redirectAdmin('succes', 'Professeur ajouté avec succès.');
    }

    if ($action === 'add_module') {
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $promotionId = (int) ($_POST['id_promotion'] ?? 0);
        $professeurId = (int) ($_POST['id_professeur'] ?? 0);
        $dateDebut = trim($_POST['date_debut'] ?? '');
        $dateFin = trim($_POST['date_fin'] ?? '');

        if ($nom === '' || $promotionId <= 0) {
            redirectAdmin('erreur', 'Veuillez renseigner un module et une promotion.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO module (nom, description, id_promotion, id_professeur, id_admin, date_debut, date_fin)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $nom,
            $description ?: null,
            $promotionId,
            $professeurId ?: null,
            $adminId,
            $dateDebut ?: null,
            $dateFin ?: null,
        ]);
        $moduleId = (int) $pdo->lastInsertId();

        if ($professeurId > 0) {
            $stmt = $pdo->prepare('UPDATE professeur SET id_module = ? WHERE id_professeur = ?');
            $stmt->execute([$moduleId, $professeurId]);
        }

        redirectAdmin('succes', 'Module ajouté et affecté à sa promotion.');
    }

    redirectAdmin('erreur', 'Action inconnue.');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        redirectAdmin('erreur', 'Cette adresse email existe déjà ou une donnée liée est invalide.');
    }

    redirectAdmin('erreur', 'Erreur base de données : ' . $e->getMessage());
}
