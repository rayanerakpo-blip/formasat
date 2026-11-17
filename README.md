# FormaSat

Plateforme d'évaluation des modules de formation par les étudiants : un étudiant note un
module sur cinq critères, l'administration suit les résultats par promotion et par module.

Projet réalisé en **PHP 8 + MySQL**, sans framework.

![Connexion](screenshots/formasat-login.jpg)

---

## Fonctionnalités

### Espace étudiant
- Connexion sécurisée.
- Évaluation d'un module sur **5 critères** : contenu, pédagogie, support, rythme,
  applicabilité.
- Une seule évaluation par étudiant et par module.

### Espace administrateur
- Tableau de bord avec statistiques agrégées.
- CRUD étudiants, professeurs, promotions et modules.
- Résultats détaillés par module.

## Point technique

Les agrégats ne sont pas recalculés à chaque affichage : des **triggers MySQL** maintiennent
la table `stats_module` et journalisent les suppressions.

| Trigger | Rôle |
|---|---|
| `trg_check_dates_module_insert` | Vérifie la cohérence des dates à la création |
| `trg_check_dates_module_update` | Vérifie la cohérence des dates à la modification |
| `trg_init_stats_module` | Initialise la ligne d'agrégats d'un nouveau module |
| `trg_log_suppr_etudiant` | Journalise la suppression d'un étudiant |
| `trg_maj_stats` | Met à jour les moyennes après chaque évaluation |

## Stack

| Élément | Choix |
|---|---|
| Langage | PHP 8 (PDO) |
| Base | MySQL — 6 tables, 5 triggers |
| Front | HTML5, CSS3 |
| Dépendances | aucune |

## Installation locale

1. Copier le dossier dans la racine web.
2. Créer la base `formatsatbd` et importer `formatsatbd.sql`.
3. Appliquer les triggers : `migrations/2026_05_26_triggers_formasat_v2.sql`
   (ou `php migrations/apply_triggers.php`).
4. Renseigner les identifiants dans `connexion.php`, ou définir `DB_HOST`, `DB_NAME`,
   `DB_USER`, `DB_PASS`.
5. Ouvrir `http://localhost/FormaSat/`.

### Compte administrateur de démonstration

| Identifiant | Mot de passe |
|---|---|
| `admin@formasat.fr` | `admin2026` |

Compte fictif, base locale uniquement.

## Structure

```
FormaSat/
├─ index.php                  # accueil
├─ login.html / connexion.php # authentification
├─ evaluation.php             # formulaire d'évaluation
├─ admin_dashboard.php        # tableau de bord
├─ admin_actions.php          # CRUD
├─ formatsatbd.sql            # schéma + données
├─ migrations/                # triggers versionnés
└─ triggers/                  # triggers unitaires
```

## Limites connues

- Interface administrateur encore sobre.
- Pas de tests automatisés.
- Les triggers doivent être appliqués manuellement après l'import.

## Auteur

**Rayane AKPO** — [github.com/rayanerakpo-blip](https://github.com/rayanerakpo-blip)
