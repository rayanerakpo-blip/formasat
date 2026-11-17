<?php
// admin_login.php
session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$erreur = '';
$succes = $_GET['succes'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'connexion.php';
    $pdo   = getConnexion();
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM admin WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($mdp, $admin['mot_de_passe'])) {
            $erreur = 'Identifiants administrateur incorrects.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id']  = $admin['id_admin'];
            $_SESSION['admin_nom'] = $admin['nom'];
            header('Location: admin_dashboard.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FormaSat - Administration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container-sm">
    <div class="auth-header">
        <a href="index.php" class="logo">Forma<span>Sat</span></a>
        <p>Espace administrateur</p>
    </div>

    <div class="card">
        <div class="card-title">Connexion Admin</div>
        <div class="card-subtitle">Accès réservé au personnel pédagogique</div>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
            <div class="alert alert-success"><?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email administrateur</label>
                <input type="email" id="email" name="email" required placeholder="admin@formasat.fr">
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required placeholder="********">
            </div>
            <button type="submit" class="btn btn-primary">Accéder au tableau de bord</button>
        </form>
    </div>

    <div class="auth-footer auth-links">
        <a href="login.html" class="link">Connexion stagiaire</a>
        <span aria-hidden="true">-</span>
        <a href="inscription.html" class="link">Inscription</a>
    </div>
</div>
</body>
</html>
