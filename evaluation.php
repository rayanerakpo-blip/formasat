<?php
// evaluation.php - Page protégée, réservée aux stagiaires connectés
session_start();

if (!isset($_SESSION['etudiant_id'])) {
    header('Location: login.html?erreur=' . urlencode('Vous devez être connecté pour accéder à cette page.'));
    exit();
}

require_once 'connexion.php';
$pdo = getConnexion();

$stmt = $pdo->prepare(
    'SELECT m.id_module, m.nom, m.description
     FROM module m
     INNER JOIN etudiant e ON e.id_promotion = m.id_promotion
     WHERE e.id_etudiant = ?
     ORDER BY m.nom ASC'
);
$stmt->execute([$_SESSION['etudiant_id']]);
$modules = $stmt->fetchAll();

$stmt2 = $pdo->prepare('SELECT id_module FROM evaluation WHERE id_etudiant = ?');
$stmt2->execute([$_SESSION['etudiant_id']]);
$deja_evalues = $stmt2->fetchAll(PDO::FETCH_COLUMN);

$criteres = [
    'contenu'       => ['label' => 'Contenu',       'desc' => 'Pertinence et richesse des sujets traités'],
    'pedagogie'     => ['label' => 'Pédagogie',     'desc' => 'Clarté des explications, méthode du formateur'],
    'support'       => ['label' => 'Support',       'desc' => 'Qualité des documents, slides et exercices'],
    'rythme'        => ['label' => 'Rythme',        'desc' => 'Adéquation du tempo avec le niveau du groupe'],
    'applicabilite' => ['label' => 'Applicabilité', 'desc' => 'Utilité concrète en situation professionnelle'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FormaSat - Évaluation</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">Forma<span>Sat</span></a>
    <div class="nav-actions">
        <span class="nav-user">Bonjour, <strong><?= htmlspecialchars($_SESSION['etudiant_nom']) ?></strong></span>
        <a href="evaluation.php">Évaluation</a>
        <a href="logout.php" class="btn-nav">Déconnexion</a>
    </div>
</nav>

<div class="container">

    <?php if (isset($_GET['erreur'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['erreur']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['succes'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['succes']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Évaluer un module</div>
        <div class="card-subtitle">Notez chaque critère de 1 (insuffisant) à 5 (excellent)</div>

        <?php if (empty($modules)): ?>
            <div class="alert alert-info">Aucun module disponible pour votre promotion pour le moment.</div>
        <?php else: ?>

        <form action="evaluation_traitement.php" method="POST">
            <div class="form-group">
                <label for="module_id">Module à évaluer</label>
                <select name="module_id" id="module_id" required>
                    <option value="">Choisissez un module</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= $m['id_module'] ?>"
                            <?= in_array($m['id_module'], $deja_evalues) ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($m['nom']) ?>
                            <?= in_array($m['id_module'], $deja_evalues) ? ' (déjà évalué)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($criteres as $key => $info): ?>
            <div class="critere-row">
                <div>
                    <div class="critere-label"><?= $info['label'] ?></div>
                    <div class="critere-desc"><?= $info['desc'] ?></div>
                </div>
                <div class="rating-group">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="note_<?= $key ?>" id="<?= $key ?>_<?= $i ?>" value="<?= $i ?>" required>
                        <label for="<?= $key ?>_<?= $i ?>"><?= $i ?></label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="form-group" style="margin-top:1.5rem;">
                <label for="commentaire">Commentaire libre (facultatif)</label>
                <textarea name="commentaire" id="commentaire" placeholder="Vos remarques, suggestions..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Envoyer mon évaluation</button>
        </form>

        <?php endif; ?>
    </div>

    <?php if (!empty($deja_evalues)): ?>
    <div class="card">
        <div class="card-title" style="font-size:1.1rem;">Modules déjà évalués</div>
        <div style="margin-top:1rem;">
            <?php foreach ($modules as $m): ?>
                <?php if (in_array($m['id_module'], $deja_evalues)): ?>
                    <span class="badge badge-green" style="margin:.25rem;"><?= htmlspecialchars($m['nom']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
