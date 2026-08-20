# Le thème `tiki-bar` — explication complète

Ce document explique, fichier par fichier, ce que fait le thème et pourquoi il est construit ainsi. L'objectif : que tu puisses répondre à n'importe quelle question de ton jury sur cette partie du code.

---

## 1. Le principe général : le Template Hierarchy

WordPress ne devine pas quel fichier PHP afficher : il suit une règle de priorité fixe appelée **Template Hierarchy**. En simplifiant, pour chaque page demandée, WordPress cherche le fichier le plus précis possible, et si ce fichier n'existe pas, il redescend vers un fichier plus générique.

Exemples concrets dans notre thème :
- Page d'accueil → WordPress cherche `front-page.php` en premier.
- Fiche d'une soirée → WordPress cherche `single-soiree.php` (car le CPT s'appelle `soiree`) avant de se rabattre sur `single.php`.
- Liste des soirées → `archive-soiree.php` avant `archive.php`.
- Absolument n'importe quoi d'autre non prévu → `index.php`, le filet de sécurité final.

C'est pour ça que `style.css` (avec son commentaire d'en-tête `Theme Name: ...`) et `index.php` sont **les deux seuls fichiers strictement obligatoires** pour qu'un dossier soit reconnu comme un thème WordPress valide.

---

## 2. `style.css`

Deux rôles bien distincts dans ce fichier :

1. **Le commentaire d'en-tête tout en haut** (`/* Theme Name: Le Tiki Bar ... */`) n'est **pas du CSS** au sens strict — c'est une carte d'identité que WordPress lit pour savoir que ce dossier est un thème, avec son nom, sa description, etc. Sans lui, WordPress ignorerait complètement le dossier.
2. **Tout le reste** est du CSS classique, organisé par sections numérotées en commentaires (design tokens, header, boutons, cartes...).

### Les "design tokens" (`:root { --tiki-void: ...; }`)

En haut du fichier, on définit des **variables CSS** (`--tiki-void`, `--tiki-ember`, etc.). L'intérêt : toutes les couleurs du site sont définies à un seul endroit. Quand on a voulu changer toute la palette de couleurs (plusieurs fois !), on n'a modifié que ces 6 lignes, et tout le site a suivi automatiquement — c'est pour ça qu'on ne changeait jamais que `style.css` à chaque fois.

---

## 3. `functions.php`

C'est le fichier "configuration" du thème. Rien d'affiché directement ici, seulement des réglages. Trois mécanismes WordPress à connaître :

### Les hooks (`add_action`)

WordPress fonctionne avec un système d'événements : à des moments précis de son exécution, il "appelle" (déclenche) des points d'ancrage nommés (les **hooks**), et n'importe quel code peut s'y brancher avec `add_action('nom_du_hook', 'ma_fonction')`.

- `after_setup_theme` : se déclenche au tout début, quand WordPress charge le thème. On l'utilise pour activer des fonctionnalités (`add_theme_support`) : logo personnalisable, image mise en avant, balise `<title>` automatique.
- `wp_enqueue_scripts` : se déclenche quand WordPress prépare l'affichage d'une page publique. On l'utilise pour charger nos fichiers CSS/JS proprement, via `wp_enqueue_style()` et `wp_enqueue_script()`.
- `widgets_init` : permet de déclarer une zone où l'admin peut glisser des widgets (ici, le pied de page).

### Pourquoi `wp_enqueue_style()` et pas `<link>` en dur dans le HTML ?

Mauvaise pratique : écrire `<link rel="stylesheet" href="style.css">` directement dans `header.php`. Bonne pratique WordPress : passer par `wp_enqueue_style()`. Pourquoi ? Parce que ça permet à WordPress de gérer intelligemment l'ordre de chargement, d'éviter qu'un même fichier soit chargé deux fois par deux bouts de code différents, et de gérer les dépendances entre fichiers.

### Le numéro de version anti-cache

```php
wp_enqueue_style('tikibar-style', get_stylesheet_uri(), array(), filemtime(get_stylesheet_directory() . '/style.css'));
```

Le 4ᵉ paramètre (`filemtime(...)`) donne la date de dernière modification du fichier comme "numéro de version". Ça force le navigateur à retélécharger le CSS dès qu'on le modifie, au lieu de servir une vieille version gardée en cache. C'est pour ça qu'on n'a presque jamais eu besoin d'expliquer "vide ton cache" en dehors du Ctrl+F5 par prudence.

---

## 4. `header.php` et `footer.php`

Ces deux fichiers "encadrent" tout le contenu de chaque page. `header.php` ouvre le HTML (`<html>`, `<head>`, `<body>`) et la balise `<main>` ; `footer.php` referme `<main>` et ajoute le pied de page.

Chaque template (page.php, single.php, etc.) appelle `get_header()` puis `get_footer()` — ça évite de dupliquer le menu et le pied de page dans chaque fichier.

### Deux hooks obligatoires à ne jamais oublier

- `wp_head()` dans `<head>` : sans lui, les plugins (SEO, sécurité...) ne peuvent pas injecter leur propre code dans l'en-tête. Rien ne s'afficherait, ou pire, certains plugins planteraient silencieusement.
- `wp_footer()` juste avant `</body>` : pareil, mais pour le pied de page (souvent utilisé pour des scripts de tracking, des popins, etc.).

### Le "skip link"

```html
<a class="skip-link screen-reader-text" href="#main-content">Aller au contenu principal</a>
```

C'est un lien invisible à l'écran (mais visible au clavier/lecteur d'écran) qui permet à quelqu'un naviguant au clavier ou avec une technologie d'assistance de sauter directement au contenu principal, sans devoir tabuler à travers tout le menu à chaque page. C'est un point d'accessibilité que ton jury pourrait tester.

---

## 5. `front-page.php`

Template de la page d'accueil. Deux parties :

1. **Le hero** (bandeau d'intro) : du HTML/CSS pur, rien de dynamique.
2. **La modale d'avertissement légal** : appelée via `get_template_part('template-parts/legal-modal')`.
3. **La grille des prochaines soirées** : c'est ici qu'on utilise `WP_Query`, l'outil WordPress pour aller chercher des contenus en base de données selon des critères précis.

### Comprendre `WP_Query`

```php
$soirees = new WP_Query(array(
    'post_type'      => 'soiree',
    'posts_per_page' => 3,
    'meta_key'       => '_tikibar_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => array(
        array('key' => '_tikibar_date', 'value' => date('Y-m-d'), 'compare' => '>=', 'type' => 'DATE'),
    ),
));
```

Traduction en français : *"Va chercher des contenus de type `soiree`, limite à 3 résultats, trie-les par leur champ personnalisé `_tikibar_date` du plus proche au plus lointain, et ne garde que ceux dont cette date est aujourd'hui ou après."*

Le `meta_query` est la partie qui filtre sur un **champ personnalisé** (post meta), pas sur les champs natifs de WordPress (titre, date de publication...). C'est indispensable ici puisque la date de la soirée n'a rien à voir avec la date de publication de l'article dans WordPress.

### Pourquoi `wp_reset_postdata()` à la fin ?

Quand on utilise une requête personnalisée (`WP_Query`) en plus de la requête principale de la page, WordPress mélange temporairement ses repères internes (quel est "le post courant"). `wp_reset_postdata()` remet tout en ordre après la boucle, pour que le reste de la page (footer, widgets...) ne soit pas perturbé.

---

## 6. `page.php`, `single.php`, `archive.php`, `404.php`, `index.php`

Ce sont les templates "génériques" du thème :

- `page.php` : pages statiques classiques (une page "Contact" par exemple).
- `single.php` : un article de blog classique (pas une soirée).
- `archive.php` : liste générique (catégories, dates...).
- `404.php` : affiché quand aucune URL ne correspond à rien.
- `index.php` : le filet de sécurité final (voir section 1).

Tous suivent le même schéma : `get_header()` → boucle WordPress (`while (have_posts()) : the_post(); ... endwhile;`) → `get_footer()`.

---

## 7. `single-soiree.php` et `archive-soiree.php`

Ces deux fichiers sont **spécifiques au Custom Post Type `soiree`** (déclaré dans le plugin `tiki-bar-activites`, pas dans le thème — voir la doc du plugin pour comprendre pourquoi).

### `single-soiree.php`

Affiche une soirée précise : titre, image, et tous les champs personnalisés récupérés avec `get_post_meta()` :

```php
$date = get_post_meta(get_the_ID(), '_tikibar_date', true);
```

`get_post_meta()` prend 3 arguments : l'ID du contenu, le nom technique du champ (toujours préfixé `_tikibar_` dans ce projet pour éviter les conflits avec d'autres plugins), et `true` pour dire "renvoie une seule valeur simple" (sinon WordPress renvoie un tableau, utile seulement si un champ peut avoir plusieurs valeurs).

À la fin du fichier, si le statut est "disponible", on appelle `tikibar_render_reservation_form()` — une fonction fournie par le plugin de réservations, pas codée ici. C'est un choix voulu : le thème ne sait pas *comment* le formulaire fonctionne, il sait juste qu'il peut demander au plugin de l'afficher. Si le plugin est désactivé, un message de repli s'affiche à la place, plutôt qu'une erreur PHP qui casserait la page.

### `archive-soiree.php`

Liste toutes les soirées, avec un filtre par période (Toutes / Ce mois-ci / Mois prochain / Passées) géré via l'URL (`?periode=ce-mois`).

Point de sécurité important :

```php
$periodes_valides = array('toutes', 'ce-mois', 'mois-prochain', 'passees');
$periode = isset($_GET['periode']) ? sanitize_text_field($_GET['periode']) : 'toutes';
if (!in_array($periode, $periodes_valides, true)) {
    $periode = 'toutes';
}
```

La valeur du filtre vient de l'URL, donc potentiellement modifiable par n'importe qui (en tapant une URL différente à la main). On ne lui fait jamais confiance directement : on la compare à une **liste blanche** de valeurs autorisées, et si elle ne correspond à rien de connu, on revient sur une valeur par défaut sûre. C'est un réflexe de sécurité à citer si on te demande "comment gérez-vous les entrées utilisateur ?"

---

## 8. `template-parts/legal-modal.php`

Un petit bout de HTML réutilisable, chargé via `get_template_part()`. L'intérêt de découper ce bloc dans son propre fichier plutôt que de l'écrire directement dans `front-page.php` : ça garde `front-page.php` plus lisible, et si un jour on veut afficher cette modale sur une autre page, on réutilise le même fichier sans dupliquer le code.

Le JS associé (`assets/js/legal-modal.js`) utilise `sessionStorage` (mémoire du navigateur qui s'efface à la fermeture de l'onglet) pour ne pas réafficher la modale à chaque clic pendant la même visite.

---

---

## 10. Les corrections faites suite à l'audit Lighthouse

Après un premier audit, plusieurs points ont été corrigés directement dans le thème. Bon à savoir : ces corrections sont un excellent sujet de soutenance, ça montre une vraie démarche de test/amélioration, pas juste "j'ai codé et c'est fini".

### Liens sans nom accessible

Avant :
```php
<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a>
```

Un lien qui ne contient qu'une image, sans texte, n'a **aucun nom accessible** pour un lecteur d'écran si l'image elle-même n'a pas de texte alternatif rempli (ce qui dépend de l'admin qui a uploadé l'image — on ne peut pas s'y fier). Correction :

```php
<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?><span class="screen-reader-text"><?php the_title(); ?></span></a>
```

`screen-reader-text` est une classe qui cache visuellement le texte (voir `style.css`) mais le laisse parfaitement lisible par les technologies d'assistance. Ce texte caché donne un nom explicite au lien (le titre de la soirée), indépendamment du texte alternatif de l'image.

### Contraste de texte insuffisant

L'ambre (`--tiki-bamboo`) est une couleur trop claire pour servir de **couleur de texte** directement sur nos fonds (lagon clair ou sable) — le contraste ne respecte pas les seuils de lisibilité (WCAG), même si visuellement ça semblait correct à l'œil. Elle reste utilisée pour des bordures/dividers (où la règle de contraste est moins stricte), mais plus jamais comme couleur de texte : `--tiki-hibiscus` (foncé) l'a remplacée partout où c'était le cas (liens, sous-titres, méta-informations des cartes, titres `<h2>`).

**Leçon à retenir** : une couleur peut fonctionner très bien en fond de bouton (avec un texte contrasté par-dessus) tout en étant un mauvais choix comme couleur de texte sur la page elle-même — ce sont deux usages différents avec des exigences de contraste différentes.

### Chargement non-bloquant des polices

```php
add_filter( 'style_loader_tag', 'tikibar_async_load_fonts', 10, 2 );
```

Par défaut, le navigateur attend d'avoir complètement téléchargé une feuille de style externe (ici, les polices Google) avant d'afficher quoi que ce soit à l'écran — ça "bloque le rendu". La technique utilisée (charger d'abord en `media="print"`, puis basculer en `media="all"` une fois prête via l'attribut `onload`) permet à la page de s'afficher immédiatement avec une police de secours, puis de basculer discrètement vers la police définitive dès qu'elle est chargée. On ajoute aussi une balise `<noscript>` en secours, pour les cas rares où JavaScript est désactivé.

### `preconnect` vers Google Fonts

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
```

`preconnect` dit au navigateur *"tu vas bientôt avoir besoin de te connecter à ce serveur, commence la négociation (DNS, connexion sécurisée) dès maintenant, en parallèle du reste"*. Ça réduit le temps d'attente au moment où la police est réellement demandée.

---

## 11. Résumé pour la soutenance (complément)

Si on te demande : *"Comment avez-vous vérifié l'accessibilité de votre site ?"*

> "J'ai utilisé Lighthouse (intégré à Chrome) pour un premier audit automatique. Il a détecté des liens sans nom accessible sur mes images cliquables, que j'ai corrigés avec du texte caché visuellement mais lisible par les lecteurs d'écran, ainsi que des problèmes de contraste de texte que j'ai corrigés en revoyant quelles couleurs de ma palette pouvaient servir de texte et lesquelles devaient rester réservées aux fonds et bordures."

Si on te demande : *"Explique-moi l'architecture de ton thème"* :

> "Mon thème suit le Template Hierarchy natif de WordPress. J'ai un fichier générique par type de contenu (`page.php`, `single.php`, `archive.php`) et des fichiers spécifiques pour mon Custom Post Type `soiree` (`single-soiree.php`, `archive-soiree.php`) qui prennent automatiquement le dessus grâce aux conventions de nommage de WordPress. Tout le CSS passe par des variables centralisées pour pouvoir changer l'identité visuelle en un seul endroit. Et surtout, mon thème ne contient aucune logique métier : il affiche des données, il ne les définit pas — ça, c'est le rôle de mes plugins."
