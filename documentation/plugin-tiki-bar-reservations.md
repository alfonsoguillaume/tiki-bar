# Le plugin `tiki-bar-reservations` — explication complète

C'est la partie du projet la plus scrutée niveau sécurité : un formulaire **public**, rempli par n'importe quel visiteur non connecté, qui écrit dans la base de données. C'est exactement le genre d'endroit où les failles de sécurité arrivent si on n'est pas rigoureux.

---

## 1. Le choix d'architecture : CPT plutôt que table SQL

Deux solutions étaient possibles pour stocker les réservations : un Custom Post Type (comme pour les soirées) ou une table SQL personnalisée. On a choisi le **CPT**, pour ces raisons :

- Cohérence avec le reste du projet (même mécanisme que les soirées).
- WordPress fournit gratuitement l'écran d'admin (liste, recherche...).
- Moins de code à écrire = moins de surface d'attaque possible (pas de requêtes SQL à sécuriser soi-même).

Le compromis assumé : moins performant qu'une table SQL dédiée à très grande échelle (des dizaines de milliers de réservations), mais largement suffisant pour un bar de jardin, et beaucoup plus rapide à développer correctement en toute sécurité pour un premier projet.

---

## 2. `includes/class-cpt-reservation.php`

Presque identique au CPT `soiree`, avec une différence essentielle :

```php
'public'              => false,
'publicly_queryable'  => false,
'exclude_from_search' => true,
'show_in_rest'        => false,
```

Une réservation contient des données **privées** (email, téléphone d'un visiteur). On désactive donc tout ce qui rendrait ce contenu accessible publiquement : pas d'URL du type `/reservation/123/`, pas d'apparition dans la recherche du site, pas d'exposition via l'API REST. Seul un administrateur connecté peut consulter ces données, via l'écran d'admin classique (`show_ui => true`).

---

## 3. `includes/class-form.php` — la partie la plus sensible

### Le principe de `admin-post.php`

```php
add_action('admin_post_tikibar_submit_reservation', array($this, 'handle_submission'));
add_action('admin_post_nopriv_tikibar_submit_reservation', array($this, 'handle_submission'));
```

`admin-post.php` est le point d'entrée que WordPress fournit nativement pour traiter un formulaire venant du site public. On doit brancher **deux hooks** sur la même fonction :

- `admin_post_{action}` : pour un utilisateur **connecté** qui soumet le formulaire.
- `admin_post_nopriv_{action}` : pour un visiteur **non connecté** (le cas normal ici, un client du bar n'a pas de compte WordPress).

Sans le second hook, le formulaire échouerait silencieusement pour 99% des visiteurs.

### Étape par étape du traitement (`handle_submission`)

**1. Vérification du nonce**

```php
if (!isset($_POST['tikibar_reservation_nonce']) || !wp_verify_nonce($_POST['tikibar_reservation_nonce'], 'tikibar_reservation_' . $soiree_id)) {
    $this->redirect_with_status($soiree_id, 'error');
}
```

Même logique que pour les soirées : preuve que la requête vient bien du formulaire affiché par WordPress. On remarque que le nonce est spécifique à **chaque soirée** (`'tikibar_reservation_' . $soiree_id`) — ça évite qu'un nonce généré pour une soirée puisse être réutilisé pour en réserver une autre.

**2. Le honeypot anti-spam**

```php
if (!empty($_POST['tikibar_website'])) {
    $this->redirect_with_status($soiree_id, 'success');
}
```

Un "honeypot" (pot de miel) est un champ de formulaire **invisible pour un humain** (caché en CSS avec `position: absolute; left: -9999px`), mais que beaucoup de robots spammeurs remplissent automatiquement parce qu'ils remplissent tous les champs qu'ils trouvent dans le HTML. Si ce champ contient quelque chose, on sait qu'il s'agit très probablement d'un bot. Astuce supplémentaire : on fait semblant que ça a réussi (`'success'`) plutôt que d'afficher une erreur — ça évite de donner un indice au bot pour qu'il ajuste son comportement.

**3. Vérification que la soirée existe et est du bon type**

```php
if (!$soiree_id || get_post_type($soiree_id) !== 'soiree') {
    $this->redirect_with_status($soiree_id, 'error');
}
```

On ne fait pas confiance à l'ID de soirée envoyé dans le formulaire — quelqu'un pourrait le modifier manuellement pour pointer vers n'importe quel contenu du site. On vérifie qu'il correspond bien à un contenu qui existe ET qui est du bon type.

**4. La sanitization : une fonction adaptée par type de champ**

```php
$prenom       = sanitize_text_field(wp_unslash($_POST['tikibar_prenom'] ?? ''));
$email        = sanitize_email(wp_unslash($_POST['tikibar_email'] ?? ''));
$participants = absint($_POST['tikibar_participants'] ?? 0);
$commentaire  = sanitize_textarea_field(wp_unslash($_POST['tikibar_commentaire'] ?? ''));
```

| Fonction | Rôle |
|---|---|
| `sanitize_text_field()` | Retire les balises HTML et les espaces superflus d'un champ texte simple |
| `sanitize_email()` | Nettoie le format d'une adresse email |
| `sanitize_textarea_field()` | Comme `sanitize_text_field`, mais garde les retours à la ligne |
| `absint()` | Force un entier positif — impossible d'y glisser autre chose qu'un nombre |
| `wp_unslash()` | Retire des antislashs que WordPress ajoute automatiquement par précaution à certaines données `$_POST` |

Le `?? ''` (opérateur de coalescence null) donne une valeur par défaut si le champ n'existe pas du tout dans `$_POST` — ça évite une erreur PHP si quelqu'un soumet une requête bricolée sans certains champs.

**5. La validation métier**

```php
if (empty($prenom) || empty($nom) || empty($email) || !is_email($email) || $participants < 1 || $participants > 20) {
    $this->redirect_with_status($soiree_id, 'error');
}
```

Nettoyer (sanitization) et valider (validation) sont deux choses différentes. Nettoyer retire ce qui est dangereux ; valider vérifie que la donnée, une fois propre, a du sens (un email a bien un `@` et un domaine, un nombre de participants est raisonnable). `is_email()` vérifie le **format**, pas que l'adresse existe réellement — on ne peut pas savoir ça sans envoyer un vrai email de confirmation.

**6. Écriture en base**

```php
$reservation_id = wp_insert_post(array(
    'post_type'   => 'reservation',
    'post_status' => 'publish',
    'post_title'  => sprintf('%s %s — %s', $prenom, $nom, get_the_title($soiree_id)),
));
update_post_meta($reservation_id, '_tikibar_email', $email);
// ... etc pour chaque champ
```

`wp_insert_post()` est la fonction native de WordPress pour créer un contenu (elle gère elle-même l'échappement nécessaire pour l'insertion en base — on n'écrit jamais de requête SQL à la main ici, donc pas de risque d'injection SQL de notre fait).

**7. Le hook custom**

```php
do_action('tikibar_reservation_created', $reservation_id, $soiree_id);
```

`do_action()` crée un **nouveau point d'ancrage personnalisé**, réutilisable plus tard (par exemple si un jour on ajoute l'envoi d'un email de confirmation) sans avoir à modifier ce fichier — on ajouterait simplement un `add_action('tikibar_reservation_created', 'ma_nouvelle_fonction')` ailleurs. C'est le même principe que les hooks natifs de WordPress, mais qu'on crée nous-mêmes pour rendre notre propre code extensible.

---

## 4. `includes/class-admin.php` — l'interface d'administration

### Colonnes personnalisées dans la liste

```php
add_filter('manage_reservation_posts_columns', array($this, 'columns'));
add_action('manage_reservation_posts_custom_column', array($this, 'render_column'), 10, 2);
```

Deux hooks complémentaires : le premier **définit quelles colonnes existent** dans le tableau (Soirée, Contact, Participants, Statut...), le second **remplit le contenu** de chaque colonne pour chaque ligne.

### Le filtre par statut

```php
public function apply_status_filter($query) {
    global $pagenow, $typenow;
    if (is_admin() && 'edit.php' === $pagenow && 'reservation' === $typenow && !empty($_GET['tikibar_statut'])) {
        $statut = sanitize_text_field($_GET['tikibar_statut']);
        $query->query_vars['meta_key']   = '_tikibar_statut';
        $query->query_vars['meta_value'] = $statut;
    }
    return $query;
}
```

On modifie la requête que WordPress s'apprête à exécuter pour la liste des réservations, en y ajoutant un critère supplémentaire basé sur le filtre choisi dans le menu déroulant. Même ici, la valeur vient de l'URL donc on la nettoie avec `sanitize_text_field()` avant de l'utiliser.

### Le changement de statut

Même schéma de sécurité que pour les champs des soirées (nonce + autosave + `current_user_can` + liste blanche des valeurs) — voir la documentation du plugin `tiki-bar-activites` pour le détail, la logique est identique.

---

## 5. Le CSS du formulaire (`public/css/reservation-form.css`)

Rien de "métier" ici, mais un point d'architecture à noter : ce CSS reste **volontairement sobre et indépendant** du thème (couleurs neutres au départ, ajustées ensuite à la palette du thème). L'idée : même si demain le thème change, le formulaire reste présentable, pas cassé visuellement.

---

## 6. Le pont entre le plugin et le thème

Dans `tiki-bar-reservations.php` :

```php
function tikibar_render_reservation_form($soiree_id) {
    static $form = null;
    if (null === $form) {
        $form = new TikiBar_Reservation_Form();
    }
    $form->render((int) $soiree_id);
}
```

Cette fonction est le **seul point de contact** entre le thème et ce plugin. Le thème appelle `tikibar_render_reservation_form()` sans jamais savoir comment le plugin est construit en interne (classes, fichiers...). Si un jour on refait tout le plugin autrement, tant que cette fonction existe toujours et se comporte pareil, le thème n'a besoin d'aucune modification. C'est ce découplage qui garantit que le thème peut être testé/changé sans casser la fonctionnalité de réservation (et inversement).

---

## 7. Résumé pour la soutenance

Si on te demande : *"Comment sécurisez-vous un formulaire public rempli par des visiteurs non connectés ?"*

> "Plusieurs couches : un nonce spécifique à chaque soirée contre le CSRF, un champ honeypot invisible contre les robots spammeurs, une vérification que l'ID de soirée reçu correspond à un contenu réel du bon type, puis une sanitization de chaque champ selon sa nature (texte, email, entier), et enfin une validation métier (email valide, nombre de participants cohérent) avant toute écriture en base."

Si on te demande : *"Pourquoi utiliser `admin-post.php` plutôt que traiter le formulaire directement dans le thème ?"*

> "C'est le point d'entrée natif de WordPress pour traiter des formulaires côté serveur, avec une gestion propre des utilisateurs connectés et non connectés. Ça garde aussi le traitement dans le plugin plutôt que dans le thème, cohérent avec le principe que la logique métier ne doit jamais dépendre de l'apparence du site."
