# Problèmes rencontrés et résolutions

Ce document liste les vrais problèmes rencontrés pendant le développement, comment on les a diagnostiqués, et comment on les a résolus. C'est souvent la partie la plus appréciée en soutenance : elle prouve une vraie compréhension, pas juste "j'ai suivi des instructions".

---

## 1. Identifiants de base de données exposés publiquement sur GitHub

### Le problème

Après avoir mis en place le dépôt Git, tout le contenu de l'installation WordPress avait été versionné et poussé sur GitHub — y compris `wp-config.php`, le fichier qui contient les identifiants de connexion à la base de données, et `wp-admin`/`wp-includes` (le cœur de WordPress, qui n'est pas du code personnel).

### Pourquoi c'est grave

`wp-config.php` contient le nom de la base, l'utilisateur, le mot de passe MySQL, et les clés de sécurité du site. Sur un dépôt **public**, ces informations sont visibles par n'importe qui dans le monde. En local avec WampServer, le risque réel est faible (mot de passe souvent vide), mais c'est une erreur qui, sur un vrai site en production, peut mener à un piratage complet de la base de données.

### Diagnostic

Vérification directe du contenu du dépôt sur GitHub : présence de fichiers qui n'auraient jamais dû être versionnés (tout le cœur WordPress + le fichier de config).

### Résolution

1. `git rm -r --cached` sur tous les fichiers/dossiers concernés (`wp-admin`, `wp-includes`, `wp-config.php`, et les fichiers PHP racine de WordPress) — cette commande retire les fichiers du **suivi Git** sans les supprimer du disque (WordPress continue de fonctionner).
2. Complétion du `.gitignore` pour empêcher que ces fichiers soient re-suivis à l'avenir.
3. Nettoyage complet de **l'historique Git** avec `git filter-branch`, pour que même les anciens commits ne contiennent plus le fichier sensible (contrairement à un simple `git rm`, qui laisse une trace dans l'historique).
4. `git push --force` pour appliquer l'historique nettoyé sur GitHub.

### Ce qu'il faut retenir

Un dépôt Git ne doit contenir **que le code qu'on a écrit soi-même** (thème + plugins), jamais le cœur d'un CMS ni des fichiers de configuration contenant des secrets. Le réflexe à avoir : créer le `.gitignore` **avant** le premier commit, pas après.

---

## 2. Dossiers imbriqués après extraction d'une archive ZIP

### Le problème

En début de projet, après avoir dézippé le thème, WordPress affichait deux thèmes "Le Tiki Bar" dans la liste, alors qu'un seul avait été créé.

### Diagnostic

Vérification directe de l'arborescence dans VSCode : certains fichiers du thème se retrouvaient directement dans `wp-content/themes/` (à la racine), au lieu d'être dans un sous-dossier `wp-content/themes/tiki-bar/`.

### Pourquoi ça arrive

WordPress considère chaque **sous-dossier direct** de `wp-content/themes/` comme un thème potentiel, à condition d'y trouver un `style.css` avec le bon en-tête. En extrayant une archive ZIP de façon désordonnée (parfois en glissant le contenu au lieu du dossier lui-même), on peut se retrouver avec des fichiers à un niveau différent de celui attendu.

### Résolution

Réorganisation manuelle : tous les fichiers du thème regroupés dans un seul dossier `wp-content/themes/tiki-bar/`, suppression des doublons.

### Ce qu'il faut retenir

Toujours vérifier l'arborescence exacte après extraction d'une archive, surtout avec des outils qui créent parfois un dossier supplémentaire autour du contenu réel.

---

## 3. Boucle de redirection sur `/robots.txt`

### Le problème

Un audit Lighthouse a signalé que `/robots.txt` renvoyait une erreur serveur (HTTP 500). En testant manuellement, la page affichait la 404 personnalisée du thème plutôt que le contenu attendu, et le journal Apache (`apache_error.log`) montrait :

```
AH00124: Request exceeded the limit of 10 internal redirects due to probable
configuration error.
```

### Diagnostic, étape par étape

1. Vérification du fichier `.htaccess` : les règles de réécriture de WordPress et celles de Wordfence (installé juste avant) étaient correctement séparées, donc pas de conflit évident entre les deux blocs.
2. Test d'une URL clairement inexistante (`/ceci-nexiste-pas`) : elle affichait normalement la 404 du thème, **sans** erreur de boucle — donc le problème était spécifique à `/robots.txt`, pas général à tout le site.
3. Vérification du statut du pare-feu Wordfence : encore en "Learning Mode" (mode observation, ne bloque rien activement) — ça a permis d'écarter Wordfence comme cause probable, sans avoir à le désactiver complètement pour tester.
4. Régénération des règles de permaliens (Réglages > Permaliens > Enregistrer) : cette manipulation a fait disparaître l'erreur serveur (boucle de redirection), mais `/robots.txt` continuait d'afficher la 404 du thème au lieu du contenu spécial attendu.

### Pourquoi WordPress gère `/robots.txt` de façon spéciale

Par défaut, WordPress génère un `robots.txt` "virtuel" — il n'existe pas physiquement sur le disque, mais WordPress détecte cette requête précise et génère son contenu à la volée. Dans une installation en sous-dossier (`/tiki/`), avec plusieurs Custom Post Types ajoutant leurs propres règles de réécriture d'URL, ce mécanisme s'est mal déclenché.

### Résolution

Plutôt que de continuer à chercher la cause exacte du dysfonctionnement du mécanisme virtuel de WordPress (chronophage, et pas garanti de trouver une cause précise), création d'un **fichier `robots.txt` physique** à la racine du site. Comme Apache trouve alors un vrai fichier existant, il le sert directement, sans jamais passer par WordPress — contournement simple et totalement fiable.

### Ce qu'il faut retenir

Face à un bug dont la cause profonde est difficile à isoler, il est parfois plus efficace (et tout aussi valide) de choisir une solution de contournement robuste plutôt que de s'acharner à comprendre le mécanisme interne exact. Le plus important : la solution choisie doit être fiable, pas juste "ça marche par hasard".

---

## 4. Contraste de texte insuffisant (accessibilité)

### Le problème

Un audit Lighthouse a signalé un score Accessibilité de 90/100, avec un problème de contraste texte/fond insuffisant.

### Diagnostic

En reprenant chaque couleur de la palette (`--tiki-bamboo`, un ambre doré) utilisée comme couleur de **texte** directement sur les fonds du site (lagon clair et sable), le contraste s'est révélé insuffisant par rapport aux seuils de lisibilité (WCAG) — alors que visuellement, à l'œil, ça semblait à peu près correct.

### Pourquoi ça arrive

Une couleur de luminosité moyenne (ni très claire, ni très foncée) peut sembler fonctionner visuellement sur un fond clair ET sur un fond moyen, mais échouer aux deux tests de contraste stricts en même temps. C'est particulièrement piégeux quand le site utilise **deux fonds de luminosité différente** (ici, le fond lagon et les cartes sable) : une couleur de texte doit être suffisamment contrastée sur les deux.

### Résolution

Remplacement de cette couleur ambre par une teinte beaucoup plus foncée (`--tiki-hibiscus`, un teal profond) partout où elle servait de couleur de **texte** (liens, sous-titres, méta-informations). L'ambre reste utilisée, mais uniquement pour des éléments décoratifs (bordures, dividers), où les exigences de contraste sont moins strictes.

### Ce qu'il faut retenir

Une couleur "accent" qui fonctionne très bien en fond de bouton (avec un texte contrasté par-dessus) n'est pas forcément un bon choix comme couleur de texte affichée directement sur la page. Toujours vérifier concrètement (avec un outil comme Lighthouse ou un vérificateur de contraste en ligne), pas seulement à l'œil.

---

## 5. Liens sans nom accessible

### Le problème

Le même audit Lighthouse a signalé des liens "sans nom visible" — un problème d'accessibilité pour les utilisateurs de lecteurs d'écran.

### Diagnostic

Les vignettes des soirées utilisaient un lien contenant uniquement une image (`<a href="..."><?php the_post_thumbnail(...); ?></a>`), sans aucun texte. Si l'image n'a pas de texte alternatif rempli dans la médiathèque (ce qui dépend de l'administrateur du site, donc pas garanti), le lien n'a alors **aucun nom** compréhensible pour une technologie d'assistance.

### Résolution

Ajout d'un texte caché visuellement mais lisible par les lecteurs d'écran à l'intérieur de chaque lien concerné :

```php
<a href="..."><?php the_post_thumbnail(); ?><span class="screen-reader-text"><?php the_title(); ?></span></a>
```

### Ce qu'il faut retenir

Un lien doit toujours avoir un nom accessible, indépendamment de si son contenu visuel (une image) a lui-même un texte alternatif correctement rempli. C'est une sécurité supplémentaire : le site reste accessible même si un administrateur oublie de remplir un champ.

---

## 6. Ordre d'activation des plugins et capacités manquantes

### Le problème potentiel (anticipé et corrigé avant qu'il ne se produise)

En personnalisant les capacités du Custom Post Type `reservation` dans le plugin `tiki-bar-reservations`, le rôle "Gestionnaire" (créé dans l'autre plugin, `tiki-bar-activites`) pouvait ne pas exister encore si les deux plugins étaient activés dans un ordre différent de celui prévu, ou si un seul des deux plugins était utilisé.

### Résolution

Le plugin `tiki-bar-reservations` attribue lui-même, dans son propre hook d'activation, les capacités liées aux réservations à l'administrateur ET au rôle "Gestionnaire" **s'il existe déjà** (vérification avec `get_role()`, qui renvoie simplement `null` si le rôle n'existe pas encore, sans erreur).

### Ce qu'il faut retenir

Deux plugins indépendants doivent rester fonctionnels quel que soit l'ordre dans lequel un administrateur les active — ne jamais supposer qu'un autre plugin a forcément été activé avant.
