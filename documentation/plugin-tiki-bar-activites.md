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

## 5. `includes/class-roles.php` — le rôle "Gestionnaire"

### Le principe : rôles et capacités

WordPress sépare deux notions liées mais différentes :
- **Une capacité** (`capability`) : une permission précise, ex. `edit_soirees` ou `manage_options`.
- **Un rôle** : juste un nom qui regroupe un paquet de capacités.

Par défaut, un Custom Post Type utilise les mêmes capacités que les articles de blog (`edit_posts`...). Le problème : impossible de donner à quelqu'un le droit de gérer les soirées sans lui donner aussi le droit de gérer tous les articles du blog, puisque c'est la même capacité qui contrôle les deux.

### `capability_type` personnalisé

```php
'capability_type' => array( 'soiree', 'soirees' ),
'map_meta_cap'    => true,
```

En donnant un couple singulier/pluriel personnalisé, WordPress génère un jeu de capacités **propre au CPT** (`edit_soirees`, `publish_soirees`, `delete_soirees`...), complètement indépendant de celles des articles. `map_meta_cap => true` est indispensable : c'est ce qui permet à WordPress de traduire automatiquement une vérification du type *"cet utilisateur peut-il modifier CETTE soirée précise ?"* vers la bonne capacité générale.

### Pourquoi il faut explicitement redonner les capacités à l'Administrateur

Piège important : **WordPress ne donne jamais automatiquement une nouvelle capacité personnalisée à un rôle**, pas même à l'Administrateur. Sans intervention, même le rôle Administrateur perdrait l'accès à ses propres soirées dès qu'on personnalise le `capability_type`. C'est pour ça que `grant_capabilities_to_administrator()` existe : elle rajoute explicitement toutes nos capacités personnalisées au rôle Administrateur natif, à l'activation du plugin.

### Créer le rôle Gestionnaire

```php
add_role(
    self::ROLE_SLUG,
    __( 'Gestionnaire', 'tiki-bar-activites' ),
    array( 'read' => true, 'upload_files' => true )
);
```

`add_role()` crée un nouveau rôle avec un jeu de capacités de départ minimal (juste de quoi se connecter à l'admin et uploader des images). On lui ajoute ensuite, une par une, les capacités liées aux soirées et réservations — mais **jamais** `manage_options`, `edit_theme_options`, `install_plugins`, `edit_users`... Ce sont précisément les capacités qu'on choisit de NE PAS donner qui définissent les limites du rôle.

### Pourquoi `uninstall.php` plutôt que le hook de désactivation

```php
register_activation_hook( __FILE__, 'tikibar_activites_activation' );   // création du rôle
// ... mais la SUPPRESSION du rôle se fait dans uninstall.php, pas ici
```

Désactiver un plugin, c'est fréquent et souvent temporaire (pour tester, dépanner...). Si on supprimait le rôle "Gestionnaire" à chaque désactivation, on perdrait cette configuration à la moindre manipulation. La suppression n'a lieu que si l'administrateur va jusqu'à **supprimer complètement** le plugin depuis l'admin — WordPress exécute alors automatiquement le fichier `uninstall.php` s'il existe.

---

## 6. `includes/class-dashboard.php` — le tableau de bord

### `add_menu_page()`

```php
add_menu_page(
    'Tableau de bord Tiki Bar', 'Tableau de bord',
    'edit_soirees',              // capacité requise pour voir ce menu
    'tikibar-dashboard',
    array( $this, 'render' ),
    'dashicons-chart-bar', 3
);
```

C'est la fonction native de WordPress pour ajouter une page complètement personnalisée dans le menu d'admin (comme "Réglages" ou "Apparence", mais la nôtre). Le 3ᵉ paramètre (`'edit_soirees'`) est important : **seuls les utilisateurs ayant cette capacité verront ce menu**. Grâce au travail fait sur les rôles juste avant, ça inclut Administrateur et Gestionnaire, mais exclut n'importe quel autre rôle (Auteur, Abonné...).

### Compter sans tout charger

```php
$query_a_venir = new WP_Query( array(
    'post_type' => 'soiree',
    'fields'    => 'ids',   // ne récupère que les identifiants, pas le contenu complet
    ...
) );
$nombre = $query_a_venir->found_posts;
```

`'fields' => 'ids'` est une optimisation : quand on veut juste **compter** des résultats (pas afficher leur contenu), demander uniquement les identifiants à la base de données est beaucoup plus rapide que de charger tous les champs de chaque soirée pour ensuite les jeter.

### CSS conditionnel

```php
public function enqueue_assets( $hook ) {
    if ( $hook !== $this->hook_suffix ) {
        return;
    }
    wp_enqueue_style( ... );
}
```

`add_menu_page()` renvoie un identifiant unique ("hook suffix") pour la page qu'elle vient de créer. En comparant ce hook à celui de la page admin actuellement affichée, on s'assure que le CSS du tableau de bord n'est chargé QUE sur cette page précise, jamais sur le reste de l'admin.

---

## 7. `includes/class-search.php` — recherche et filtrage dynamique

Le sujet demande un affichage **dynamique** des résultats (sans recharger la page). Ça implique une requête en arrière-plan : de l'AJAX.

### Le principe de l'AJAX dans WordPress

```php
add_action( 'wp_ajax_tikibar_filter_soirees', array( $this, 'handle_ajax_filter' ) );
add_action( 'wp_ajax_nopriv_tikibar_filter_soirees', array( $this, 'handle_ajax_filter' ) );
```

Même logique que pour `admin-post.php` dans le plugin de réservations : deux hooks, un pour les visiteurs connectés, un pour les non-connectés — sur la même fonction de traitement. `admin-ajax.php` est le point d'entrée générique de WordPress pour ce genre de requête en arrière-plan.

### `wp_localize_script()`

```php
wp_localize_script( 'tikibar-search-filter', 'tikibarSearch', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'tikibar_search_filter' ),
) );
```

Le JavaScript, exécuté dans le navigateur, ne connaît pas l'adresse exacte d'`admin-ajax.php`, ni le nonce (qui doit être généré côté PHP à chaque affichage de page). `wp_localize_script()` fait le pont : elle injecte ces informations PHP dans une variable JavaScript globale (`tikibarSearch`) accessible depuis notre fichier `.js`.

### La double sécurité sur les filtres

```php
$categorie = isset( $_POST['categorie'] ) ? sanitize_key( $_POST['categorie'] ) : '';
if ( $categorie && ! term_exists( $categorie, 'type_soiree' ) ) {
    $categorie = '';
}
```

Deux étapes bien distinctes : `sanitize_key()` nettoie le **format** (ne garde que des lettres minuscules, chiffres et tirets — le format d'un identifiant de catégorie). Mais nettoyer le format ne suffit pas : quelqu'un pourrait envoyer un slug qui a le bon format mais qui ne correspond à **aucune vraie catégorie**. `term_exists()` vérifie que ce qu'on nous a envoyé correspond réellement à quelque chose qui existe en base, avant de s'en servir dans une requête.

### Pourquoi renvoyer du JSON avec du HTML dedans

```php
wp_send_json_success( array(
    'html'  => $html,
    'count' => $query->found_posts,
) );
```

Le PHP génère le HTML des résultats (avec `ob_start()` / `ob_get_clean()`, une technique pour "capturer" tout ce qu'un bout de code affiche au lieu de l'envoyer directement au navigateur), puis l'envoie encapsulé dans une réponse JSON. Le JavaScript n'a plus qu'à remplacer le contenu d'une simple `<div>` avec ce HTML reçu — c'est beaucoup plus simple que de reconstruire l'affichage des cartes entièrement en JavaScript.

---

## 8. Résumé pour la soutenance

Si on te demande : *"Pourquoi avoir séparé le CPT dans un plugin plutôt que dans le thème ?"*

> "Parce qu'un Custom Post Type définit une structure de données, pas un affichage. Si je change de thème, mes soirées et leurs champs doivent continuer d'exister et rester gérables dans l'admin. En le mettant dans un plugin indépendant, je découple la donnée de la présentation — c'est le thème qui va chercher les données du plugin pour les afficher, jamais l'inverse."

Si on te demande : *"Comment sécurisez-vous la sauvegarde des champs personnalisés ?"*

> "Je vérifie un nonce pour éviter les attaques CSRF, je vérifie que ce n'est pas une sauvegarde automatique, je vérifie les droits de l'utilisateur avec `current_user_can()`, et enfin je nettoie chaque champ avec la fonction de sanitization adaptée à son type avant de l'enregistrer."

Si on te demande : *"Comment fonctionne votre rôle Gestionnaire techniquement ?"*

> "J'ai personnalisé le `capability_type` de mes deux CPT pour qu'ils aient leurs propres capacités, indépendantes des articles de blog. J'ai ensuite créé un rôle 'Gestionnaire' avec `add_role()`, auquel je n'ai donné QUE les capacités liées aux soirées et réservations — jamais les capacités générales comme `manage_options`. J'ai aussi dû explicitement redonner ces mêmes capacités au rôle Administrateur, parce que WordPress ne le fait jamais automatiquement pour des capacités personnalisées."

Si on te demande : *"Comment fonctionne votre recherche dynamique ?"*

> "Le formulaire est intercepté en JavaScript pour éviter le rechargement de page, et envoie les critères via `fetch()` vers `admin-ajax.php`, le point d'entrée standard de WordPress pour ce type de requête. Le PHP vérifie un nonce, valide chaque filtre — notamment en vérifiant que les catégories envoyées existent vraiment avec `term_exists()` — puis renvoie le HTML des résultats en JSON, que le JavaScript vient simplement injecter dans la page."
