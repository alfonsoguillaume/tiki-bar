# Le Tiki Bar — Projet WordPress

Site WordPress pour un bar clandestin de jardin, développé dans le cadre d'un projet d'études (thème + plugins personnalisés).

## Structure du dépôt

```
tiki-bar-projet/
├── theme/
│   └── tiki-bar/                  # Thème WordPress personnalisé (affichage)
├── plugin/
│   ├── tiki-bar-activites/        # CPT "soiree" + taxonomies (données métier)
│   └── tiki-bar-reservations/     # Formulaire de réservation + gestion admin
└── documentation/                 # Documentation technique du projet
```

## Stack technique

- WordPress (CMS)
- PHP (thème + plugins, développés manuellement)
- HTML / CSS / JavaScript vanilla
- WampServer (environnement de développement local)

## Fonctionnalités principales

- **Thème `tiki-bar`** : thème personnalisé développé sans base existante, respectant le Template Hierarchy WordPress (header, footer, front-page, single/archive génériques et dédiés au CPT).
- **Plugin `tiki-bar-activites`** : déclare le Custom Post Type `soiree` (titre, description, image, date, heure, durée, lieu, tarif, participants, statut) et deux taxonomies (`type_soiree`, `niveau_ambiance`). Indépendant du thème.
- **Plugin `tiki-bar-reservations`** : formulaire public de demande de réservation (sécurisé par nonce, sanitization et honeypot anti-spam) + interface d'administration pour consulter et changer le statut des demandes (En attente / Acceptée / Refusée).

## Installation locale

1. Installer WordPress (ex. via WampServer) et créer une base de données.
2. Copier le contenu de `theme/tiki-bar/` dans `wp-content/themes/tiki-bar/`.
3. Copier `plugin/tiki-bar-activites/` et `plugin/tiki-bar-reservations/` dans `wp-content/plugins/`.
4. Dans l'admin WordPress : activer le thème "Le Tiki Bar", puis activer les deux plugins.
5. Aller dans Réglages > Permaliens et cliquer sur "Enregistrer" pour régénérer les URLs.

## Documentation

Voir le dossier `documentation/` pour l'architecture détaillée, les choix techniques, la sécurité et les tests.

## Auteur

Projet réalisé dans le cadre d'un cursus WordPress.
