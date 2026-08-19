# Le plugin `tiki-bar-activites` — explication complète

Ce plugin déclare le Custom Post Type "Soirée" et ses taxonomies. C'est la partie **données métier** du projet — tout ce qui concerne la structure des soirées, indépendamment de comment elles sont affichées.

---

## 1. Pourquoi un plugin séparé, et pas dans le thème ?

C'est LA question que ton jury va probablement poser. Réponse : une soirée est une **donnée**, pas de la **présentation**. Si demain le client change complètement de thème (nouveau design), ses soirées existantes doivent continuer d'exister, rester éditables dans l'admin, et ne pas disparaître. Si le Custom Post Type avait été déclaré dans `functions.php` du thème, désactiver le thème aurait fait disparaître le type de contenu lui-même — WordPress ne saurait même plus que "soiree" existe.

En le mettant dans un plugin, la donnée est **indépendante de l'apparence du site**. C'est un principe d'architecture WordPress à connaître par cœur.

---

## 2. `tiki-bar-activites.php` — le fichier principal

### Le commentaire d'en-tête

```php
/**
 * Plugin Name: Tiki Bar - Activités
 * ...
 */
```

Comme pour le thème, ce commentaire n'est pas juste de la documentation — WordPress le lit pour savoir que ce dossier est un plugin activable, avec son nom affiché dans la page "Extensions".

### `plugins_loaded`

```php
add_action('plugins_loaded', 'tikibar_activites_init');
```

`plugins_loaded` est le tout premier hook disponible une fois que **tous** les plugins actifs sont chargés en mémoire (mais avant que WordPress ne commence à traiter la page demandée). On l'utilise pour être sûr que tout est prêt avant de lancer notre propre code.

### `register_activation_hook` et `flush_rewrite_rules()`

```php
function tikibar_activites_activation() {
    $cpt = new TikiBar_CPT_Soiree();
    $cpt->register_post_type();
    $cpt->register_taxonomies();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'tikibar_activites_activation');
```

Quand on déclare un nouveau type de contenu, WordPress doit générer les règles qui transforment `/soiree/nom-de-la-soiree/` en une vraie page. Ces règles sont normalement recalculées automatiquement, mais **seulement au bon moment** — juste après l'activation d'un plugin, WordPress ne les a pas encore recalculées, donc sans `flush_rewrite_rules()`, la première fois qu'on visiterait une fiche de soirée on tomberait sur une erreur 404 jusqu'à ce qu'on aille (souvent par hasard) dans Réglages > Permaliens.

**Piège classique** : appeler `flush_rewrite_rules()` à chaque chargement de page (dans `init` par exemple) serait une grosse erreur de performance — cette opération est lourde, elle ne doit se déclencher qu'une seule fois, à l'activation.

---

## 3. `includes/class-cpt-soiree.php` — le cœur du plugin

### Pourquoi une classe, et pas juste des fonctions ?

Utiliser une classe (programmation orientée objet) permet de regrouper toutes les fonctions liées à "la déclaration du CPT et de ses taxonomies" dans un seul endroit cohérent, avec un nom clair. Ça évite aussi les conflits de noms de fonctions si un autre plugin utilisait par hasard un nom similaire à une fonction "flottante".

### `register_post_type('soiree', $args)`

C'est LA fonction native de WordPress pour créer un nouveau type de contenu (comme "Articles" ou "Pages", mais personnalisé). Quelques paramètres clés du tableau `$args` :

| Paramètre | Rôle |
|---|---|
| `public => true` | Le contenu est visible sur le site, pas seulement dans l'admin |
| `has_archive => true` | Active automatiquement la page liste `/soiree/` |
| `rewrite => array('slug' => 'soiree')` | Définit le préfixe d'URL |
| `show_in_rest => true` | Rend le contenu accessible via l'éditeur Gutenberg (et l'API REST) |
| `supports => array('title', 'editor', 'thumbnail', 'excerpt')` | Active les champs natifs qu'on veut : titre, description, image, extrait |

Ce sont ces paramètres, et eux seuls, qui font que WordPress te génère **gratuitement** tout un écran d'admin (liste, ajout, modification, suppression) sans qu'on ait eu à coder une seule ligne d'interface d'administration.

### `register_taxonomy()`

Une taxonomie, c'est un système de classification (comme "Catégories" pour les articles de blog, mais qu'on peut personnaliser et rattacher à n'importe quel CPT). On en a deux :

- `type_soiree` : Soirée à thème, Dégustation cocktails, Concert live, Brunch tropical.
- `niveau_ambiance` : Lounge, Festive, Déchaînée (notre adaptation du "niveau" demandé dans le sujet original).

```php
register_taxonomy('type_soiree', 'soiree', array(
    'hierarchical' => true,
    ...
));
```

`hierarchical => true` donne un comportement "catégories" (cases à cocher, possibilité de sous-catégories) plutôt qu'un comportement "mots-clés/tags" (champ de saisie libre). On l'a choisi ici parce qu'on veut une liste fermée de types définis à l'avance par l'administrateur, pas que chaque soirée invente son propre type.

---

## 4. `includes/class-meta-box.php` — les champs personnalisés

Une **meta box** est un cadre qu'on ajoute sur l'écran d'édition d'un contenu, pour y afficher des champs qui ne sont pas prévus nativement par WordPress (date, heure, lieu, tarif...).

### `add_meta_box()`

```php
add_meta_box(
    'tikibar_soiree_details',           // identifiant unique
    'Détails de la soirée',              // titre affiché
    array($this, 'render'),              // fonction qui affiche le HTML
    'soiree',                            // uniquement sur ce type de contenu
    'normal',                            // position sur l'écran
    'high'                               // priorité d'affichage
);
```

### Le stockage : post meta

Chaque champ personnalisé (date, heure, lieu...) est stocké dans la table `wp_postmeta` de la base de données, rattaché à l'ID du contenu, via `update_post_meta($post_id, '_tikibar_date', $valeur)`. C'est le mécanisme natif de WordPress pour attacher des données structurées à n'importe quel contenu, sans avoir à créer de nouvelles tables SQL.

### La sécurité de la sauvegarde — à connaître par cœur

```php
public function save($post_id) {
    // 1. Vérification du nonce
    if (!isset($_POST['tikibar_soiree_nonce']) || !wp_verify_nonce($_POST['tikibar_soiree_nonce'], 'tikibar_soiree_save')) {
        return;
    }
    // 2. On ignore les sauvegardes automatiques
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    // 3. Vérification des droits
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    // 4. Nettoyage de chaque champ avant stockage
    ...
}
```

Quatre vérifications, dans cet ordre précis, avant de toucher à la base de données :

1. **Le nonce** (`wp_verify_nonce`) : un jeton unique généré à l'affichage du formulaire, qui prouve que la requête vient bien de ce formulaire précis, à ce moment précis — ça bloque les attaques CSRF (quelqu'un qui forgerait une requête depuis un autre site pour modifier tes données à ton insu).
2. **L'autosave** : WordPress sauvegarde automatiquement un brouillon toutes les X secondes en arrière-plan. À ce moment-là, les champs de notre formulaire ne sont pas envoyés (seulement le titre/contenu), donc on doit ignorer cette sauvegarde silencieuse pour ne pas écraser nos données par erreur.
3. **Les capacités** (`current_user_can`) : même si quelqu'un contourne le nonce d'une façon ou d'une autre, on vérifie quand même que l'utilisateur a le droit de modifier CE contenu précis.
4. **Le nettoyage** (`sanitize_text_field`, cast en `(float)` ou `(int)`) : on ne fait jamais confiance à ce qui arrive dans `$_POST`, même après les 3 vérifications précédentes — chaque champ est nettoyé selon sa nature avant d'être stocké.

### La liste blanche pour le statut

```php
$statut_autorises = array('disponible', 'complet', 'annule');
$statut = sanitize_text_field($_POST['tikibar_statut']);
if (in_array($statut, $statut_autorises, true)) {
    update_post_meta($post_id, '_tikibar_statut', $statut);
}
```

Même après nettoyage, on vérifie que la valeur reçue correspond à l'une des 3 valeurs qu'on attend vraiment. Le `true` en 3ᵉ argument de `in_array()` force une comparaison stricte (type ET valeur), pour éviter des comparaisons approximatives qui pourraient laisser passer une valeur inattendue.

---

## 5. Résumé pour la soutenance

Si on te demande : *"Pourquoi avoir séparé le CPT dans un plugin plutôt que dans le thème ?"*

> "Parce qu'un Custom Post Type définit une structure de données, pas un affichage. Si je change de thème, mes soirées et leurs champs doivent continuer d'exister et rester gérables dans l'admin. En le mettant dans un plugin indépendant, je découple la donnée de la présentation — c'est le thème qui va chercher les données du plugin pour les afficher, jamais l'inverse."

Si on te demande : *"Comment sécurisez-vous la sauvegarde des champs personnalisés ?"*

> "Je vérifie un nonce pour éviter les attaques CSRF, je vérifie que ce n'est pas une sauvegarde automatique, je vérifie les droits de l'utilisateur avec `current_user_can()`, et enfin je nettoie chaque champ avec la fonction de sanitization adaptée à son type avant de l'enregistrer."
