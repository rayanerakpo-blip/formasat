<?php
// admin_dashboard.php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

require_once 'connexion.php';
$pdo = getConnexion();

$stats = [
    'etudiants' => (int) $pdo->query('SELECT COUNT(*) FROM etudiant')->fetchColumn(),
    'professeurs' => (int) $pdo->query('SELECT COUNT(*) FROM professeur')->fetchColumn(),
    'promotions' => (int) $pdo->query('SELECT COUNT(*) FROM promotion')->fetchColumn(),
    'modules' => (int) $pdo->query('SELECT COUNT(*) FROM module')->fetchColumn(),
    'evaluations' => (int) $pdo->query('SELECT COUNT(*) FROM evaluation')->fetchColumn(),
];

$promotions = $pdo->query('SELECT id_promotion, nom, annee FROM promotion ORDER BY annee DESC, nom ASC')->fetchAll();

$professeurs = $pdo->query(
    'SELECT p.id_professeur, p.nom, p.email, m.nom AS module_nom
     FROM professeur p
     LEFT JOIN module m ON m.id_module = p.id_module
     ORDER BY p.nom ASC'
)->fetchAll();

$etudiants = $pdo->query(
    'SELECT e.prenom, e.nom, e.email, pr.nom AS promotion_nom, pr.annee
     FROM etudiant e
     LEFT JOIN promotion pr ON pr.id_promotion = e.id_promotion
     ORDER BY pr.annee DESC, pr.nom ASC, e.nom ASC'
)->fetchAll();

$modulesGestion = $pdo->query(
    'SELECT m.id_module, m.nom, m.description, m.date_debut, m.date_fin, pr.nom AS promotion_nom, pr.annee, p.nom AS professeur_nom
     FROM module m
     LEFT JOIN promotion pr ON pr.id_promotion = m.id_promotion
     LEFT JOIN professeur p ON p.id_professeur = m.id_professeur
     ORDER BY pr.annee DESC, pr.nom ASC, m.nom ASC'
)->fetchAll();

$modulesResultats = $pdo->query(
    'SELECT
        m.id_module,
        m.nom,
        COALESCE(s.nb_evaluations, 0) AS nb_evaluations,
        s.moy_globale AS moyenne
     FROM module m
     LEFT JOIN stats_module s ON s.id_module = m.id_module
     ORDER BY m.nom ASC'
)->fetchAll();

$dernieresEvaluations = $pdo->query(
    'SELECT
        e.date_evaluation,
        e.commentaire,
        et.prenom,
        et.nom AS etudiant_nom,
        m.nom AS module_nom,
        ROUND((e.note_contenu + e.note_pedagogie + e.note_support + e.note_rythme + e.note_applicabilite) / 5, 2) AS moyenne
     FROM evaluation e
     INNER JOIN etudiant et ON et.id_etudiant = e.id_etudiant
     INNER JOIN module m ON m.id_module = e.id_module
     ORDER BY e.date_evaluation DESC
     LIMIT 10'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FormaSat - Tableau de bord</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">Forma<span>Sat</span></a>
    <div class="nav-actions">
        <span class="nav-user">Admin : <strong><?= htmlspecialchars($_SESSION['admin_nom']) ?></strong></span>
        <a href="#gestion">Gestion</a>
        <a href="#resultats">Résultats</a>
        <a href="logout.php" class="btn-nav">Déconnexion</a>
    </div>
</nav>

<div class="container container-wide">
    <?php if (isset($_GET['erreur'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['erreur']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['succes'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['succes']) ?></div>
    <?php endif; ?>

    <div class="stats-grid stats-grid-wide">
        <div class="stat-card">
            <div class="stat-val"><?= $stats['etudiants'] ?></div>
            <div class="stat-lbl">Élèves</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $stats['professeurs'] ?></div>
            <div class="stat-lbl">Professeurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $stats['promotions'] ?></div>
            <div class="stat-lbl">Promotions</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $stats['modules'] ?></div>
            <div class="stat-lbl">Modules</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $stats['evaluations'] ?></div>
            <div class="stat-lbl">Évaluations</div>
        </div>
    </div>

    <section id="gestion" class="section-block">
        <div class="section-heading">
            <h1>Gestion pédagogique</h1>
            <p>Ajoutez les promotions, les élèves, les professeurs et les modules, puis affectez-les ensemble.</p>
        </div>

        <div class="admin-grid">
            <div class="card">
                <div class="card-title">Ajouter une promotion</div>
                <div class="card-subtitle">Exemple : BTS SIO SLAM - 2026</div>
                <form action="admin_actions.php" method="POST">
                    <input type="hidden" name="action" value="add_promotion">
                    <div class="form-group">
                        <label for="promotion_nom">Nom de la promotion</label>
                        <input type="text" id="promotion_nom" name="nom" required placeholder="BTS SIO SLAM">
                    </div>
                    <div class="form-group">
                        <label for="promotion_annee">Année</label>
                        <input type="number" id="promotion_annee" name="annee" required min="2000" max="2100" value="<?= date('Y') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter la promotion</button>
                </form>
            </div>

            <div class="card">
                <div class="card-title">Ajouter un élève</div>
                <div class="card-subtitle">L'élève pourra ensuite se connecter à son espace.</div>
                <form action="admin_actions.php" method="POST">
                    <input type="hidden" name="action" value="add_etudiant">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="etudiant_prenom">Prénom</label>
                            <input type="text" id="etudiant_prenom" name="prenom" required>
                        </div>
                        <div class="form-group">
                            <label for="etudiant_nom">Nom</label>
                            <input type="text" id="etudiant_nom" name="nom" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="etudiant_email">Email</label>
                        <input type="email" id="etudiant_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="etudiant_mdp">Mot de passe</label>
                        <input type="password" id="etudiant_mdp" name="mot_de_passe" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="etudiant_promotion">Promotion</label>
                        <select id="etudiant_promotion" name="id_promotion" required>
                            <option value="">Choisir une promotion</option>
                            <?php foreach ($promotions as $promotion): ?>
                                <option value="<?= $promotion['id_promotion'] ?>">
                                    <?= htmlspecialchars($promotion['nom'] . ' - ' . $promotion['annee']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter l'élève</button>
                </form>
            </div>

            <div class="card">
                <div class="card-title">Ajouter un professeur</div>
                <div class="card-subtitle">Le module peut être affecté maintenant ou plus tard.</div>
                <form action="admin_actions.php" method="POST">
                    <input type="hidden" name="action" value="add_professeur">
                    <div class="form-group">
                        <label for="professeur_nom">Nom complet</label>
                        <input type="text" id="professeur_nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="professeur_email">Email</label>
                        <input type="email" id="professeur_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="professeur_module">Module</label>
                        <select id="professeur_module" name="id_module">
                            <option value="">Aucun module pour le moment</option>
                            <?php foreach ($modulesGestion as $module): ?>
                                <option value="<?= $module['id_module'] ?>"><?= htmlspecialchars($module['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter le professeur</button>
                </form>
            </div>

            <div class="card">
                <div class="card-title">Ajouter un module</div>
                <div class="card-subtitle">Associez le module à une promotion et éventuellement à un professeur.</div>
                <form action="admin_actions.php" method="POST">
                    <input type="hidden" name="action" value="add_module">
                    <div class="form-group">
                        <label for="module_nom">Nom du module</label>
                        <input type="text" id="module_nom" name="nom" required placeholder="Développement Web">
                    </div>
                    <div class="form-group">
                        <label for="module_description">Description</label>
                        <textarea id="module_description" name="description" placeholder="HTML, CSS, PHP..."></textarea>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="module_promotion">Promotion</label>
                            <select id="module_promotion" name="id_promotion" required>
                                <option value="">Choisir une promotion</option>
                                <?php foreach ($promotions as $promotion): ?>
                                    <option value="<?= $promotion['id_promotion'] ?>">
                                        <?= htmlspecialchars($promotion['nom'] . ' - ' . $promotion['annee']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="module_professeur">Professeur</label>
                            <select id="module_professeur" name="id_professeur">
                                <option value="">Aucun professeur</option>
                                <?php foreach ($professeurs as $professeur): ?>
                                    <option value="<?= $professeur['id_professeur'] ?>"><?= htmlspecialchars($professeur['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="module_date_debut">Date de début</label>
                            <input type="date" id="module_date_debut" name="date_debut">
                        </div>
                        <div class="form-group">
                            <label for="module_date_fin">Date de fin</label>
                            <input type="date" id="module_date_fin" name="date_fin">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter le module</button>
                </form>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="data-grid">
            <div class="card">
                <div class="card-title">Promotions</div>
                <?php if (empty($promotions)): ?>
                    <div class="alert alert-info">Aucune promotion enregistrée.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Année</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($promotions as $promotion): ?>
                                <tr>
                                    <td><?= htmlspecialchars($promotion['nom']) ?></td>
                                    <td><?= htmlspecialchars($promotion['annee']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">Professeurs</div>
                <?php if (empty($professeurs)): ?>
                    <div class="alert alert-info">Aucun professeur enregistré.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Module</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($professeurs as $professeur): ?>
                                <tr>
                                    <td><?= htmlspecialchars($professeur['nom']) ?></td>
                                    <td><?= htmlspecialchars($professeur['email']) ?></td>
                                    <td><?= htmlspecialchars($professeur['module_nom'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Modules par promotion</div>
            <?php if (empty($modulesGestion)): ?>
                <div class="alert alert-info">Aucun module enregistré.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Promotion</th>
                            <th>Professeur</th>
                            <th>Dates</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulesGestion as $module): ?>
                            <tr>
                                <td><?= htmlspecialchars($module['nom']) ?></td>
                                <td><?= htmlspecialchars($module['promotion_nom'] ? $module['promotion_nom'] . ' - ' . $module['annee'] : '-') ?></td>
                                <td><?= htmlspecialchars($module['professeur_nom'] ?: '-') ?></td>
                                <td>
                                    <?= htmlspecialchars($module['date_debut'] ?: '-') ?>
                                    <?= $module['date_fin'] ? ' au ' . htmlspecialchars($module['date_fin']) : '' ?>
                                </td>
                                <td><?= htmlspecialchars($module['description'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-title">Élèves par promotion</div>
            <?php if (empty($etudiants)): ?>
                <div class="alert alert-info">Aucun élève enregistré.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Email</th>
                            <th>Promotion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <tr>
                                <td><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></td>
                                <td><?= htmlspecialchars($etudiant['email']) ?></td>
                                <td><?= htmlspecialchars($etudiant['promotion_nom'] ? $etudiant['promotion_nom'] . ' - ' . $etudiant['annee'] : '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <section id="resultats" class="section-block">
        <div class="section-heading">
            <h1>Résultats des évaluations</h1>
            <p>Suivez les moyennes par module et les derniers retours des élèves.</p>
        </div>

        <div class="card">
            <div class="card-title">Résultats par module</div>
            <div class="card-subtitle">Moyenne calculée sur les 5 critères d'évaluation</div>

            <?php if (empty($modulesResultats)): ?>
                <div class="alert alert-info">Aucun module disponible pour le moment.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Évaluations</th>
                            <th>Moyenne</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulesResultats as $module): ?>
                            <tr>
                                <td><?= htmlspecialchars($module['nom']) ?></td>
                                <td><?= (int) $module['nb_evaluations'] ?></td>
                                <td>
                                    <?php if ($module['moyenne'] === null): ?>
                                        <span class="badge badge-blue">Aucune note</span>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($module['moyenne']) ?>/5</strong>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-title">Dernières évaluations</div>
            <div class="card-subtitle">Les 10 retours les plus récents</div>

            <?php if (empty($dernieresEvaluations)): ?>
                <div class="alert alert-info">Aucune évaluation enregistrée pour le moment.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Élève</th>
                            <th>Module</th>
                            <th>Moyenne</th>
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieresEvaluations as $evaluation): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($evaluation['date_evaluation']))) ?></td>
                                <td><?= htmlspecialchars($evaluation['prenom'] . ' ' . $evaluation['etudiant_nom']) ?></td>
                                <td><?= htmlspecialchars($evaluation['module_nom']) ?></td>
                                <td><strong><?= htmlspecialchars($evaluation['moyenne']) ?>/5</strong></td>
                                <td><?= htmlspecialchars($evaluation['commentaire'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>
