# Le Tiki Bar — Projet WordPress

Site WordPress pour un bar clandestin de jardin, développé dans le cadre d'un projet d'études (thème + plugins personnalisés, sans base existante).

## Structure du dépôt

```
tiki-bar/
├── wp-content/
│   ├── themes/
│   │   └── tiki-bar/                  # Thème WordPress personnalisé (affichage)
│   └── plugins/
│       ├── tiki-bar-activites/        # CPT "soiree", taxonomies, rôles, tableau de bord, recherche
│       └── tiki-bar-reservations/     # Formulaire de réservation + gestion admin
├── documentation/                     # Documentation technique du projet
├── .htaccess
└── .gitignore
```

Seuls le thème, les plugins personnalisés et la documentation sont versionnés — le cœur de WordPress (`wp-admin`, `wp-includes`...), les identifiants de connexion (`wp-config.php`) et les plugins tiers ne le sont pas (voir `.gitignore`).

## Stack technique

- WordPress (CMS)
- PHP (thème + plugins, développés manuellement, aucun page builder)
- HTML / CSS / JavaScript vanilla (pas de framework front)
- WampServer (environnement de développement local)

## Fonctionnalités principales

### Thème `tiki-bar`

Thème personnalisé développé sans base existante, respectant le Template Hierarchy WordPress (header, footer, front-page, templates génériques et templates dédiés au CPT `soiree`). Identité visuelle "lagon tropical", accessible (contraste vérifié, liens avec noms accessibles, skip-link) et optimisé (chargement non-bloquant des polices).

### Plugin `tiki-bar-activites`

- Custom Post Type `soiree` (titre, description, image, date, heure, durée, lieu, tarif, participants, statut) et deux taxonomies (`type_soiree`, `niveau_ambiance`).
- Rôle personnalisé **Gestionnaire** avec des capacités limitées au contenu métier (soirées + réservations), sans accès aux réglages généraux du site.
- **Tableau de bord admin** : nombre total d'activités, activités à venir, réservations en attente, réservations acceptées.
- **Recherche et filtrage dynamique** (catégorie, ambiance, période, lieu) via AJAX, sans rechargement de page.
- Indépendant du thème.

### Plugin `tiki-bar-reservations`

Formulaire public de demande de réservation (sécurisé par nonce, sanitization et honeypot anti-spam) + interface d'administration pour consulter et changer le statut des demandes (En attente / Acceptée / Refusée).

## Sécurité

- Nonces, `sanitize_*`, `esc_*`, capacités WordPress utilisés systématiquement (voir `documentation/`).
- Rôles et capacités personnalisés, least-privilege pour le rôle Gestionnaire.
- Pare-feu applicatif : Wordfence.
- Sauvegardes automatiques : UpdraftPlus.
- Historique Git nettoyé de tout identifiant sensible.

## Installation locale

1. Installer WordPress (ex. via WampServer) et créer une base de données.
2. Copier `wp-content/themes/tiki-bar/` de ce dépôt vers `wp-content/themes/tiki-bar/` de l'installation WordPress.
3. Copier `wp-content/plugins/tiki-bar-activites/` et `wp-content/plugins/tiki-bar-reservations/` vers `wp-content/plugins/` de l'installation WordPress.
4. Dans l'admin WordPress : activer le thème "Le Tiki Bar", puis activer les deux plugins (dans n'importe quel ordre).
5. Aller dans Réglages > Permaliens et cliquer sur "Enregistrer" pour régénérer les URLs.
6. Créer au moins une catégorie (Soirées > Types de soirée) et une ambiance (Soirées > Niveaux d'ambiance) pour que les filtres de recherche affichent des options.

## Documentation

Voir le dossier `documentation/` :

- `theme-tiki-bar.md` — explication détaillée du thème
- `plugin-tiki-bar-activites.md` — explication détaillée (CPT, rôles, tableau de bord, recherche)
- `plugin-tiki-bar-reservations.md` — explication détaillée du plugin de réservations
- `problemes-et-resolutions.md` — problèmes rencontrés pendant le développement et leur résolution

## Auteur

Projet réalisé dans le cadre d'un cursus WordPress.
