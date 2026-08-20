<?php
/**
 * Plugin Name: Tiki Bar - Activités
 * Plugin URI: https://example.com
 * Description: Déclare le Custom Post Type "Soirée" et ses taxonomies (type, ambiance). Indépendant du thème : les soirées restent gérables même si le thème change.
 * Version: 1.0
 * Author: Toi
 * Text Domain: tiki-bar-activites
 */

// Sécurité : on interdit l'accès direct au fichier.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constantes utiles pour construire des chemins/URLs proprement dans tout le plugin.
define( 'TIKIBAR_ACTIVITES_PATH', plugin_dir_path( __FILE__ ) );
define( 'TIKIBAR_ACTIVITES_URL', plugin_dir_url( __FILE__ ) );

// On charge les classes du plugin.
require_once TIKIBAR_ACTIVITES_PATH . 'includes/class-cpt-soiree.php';
require_once TIKIBAR_ACTIVITES_PATH . 'includes/class-meta-box.php';
require_once TIKIBAR_ACTIVITES_PATH . 'includes/class-roles.php';
require_once TIKIBAR_ACTIVITES_PATH . 'includes/class-dashboard.php';
require_once TIKIBAR_ACTIVITES_PATH . 'includes/class-search.php';

/**
 * On instancie les classes et on les "branche" sur WordPress au chargement des plugins.
 */
function tikibar_activites_init() {
	new TikiBar_CPT_Soiree();
	new TikiBar_Soiree_Meta_Box();
	new TikiBar_Search();
	if ( is_admin() ) {
		new TikiBar_Dashboard();
	}
}
add_action( 'plugins_loaded', 'tikibar_activites_init' );

/**
 * Fonction pont pour le thème : affiche le formulaire de recherche/filtrage
 * sans que le thème ait besoin de connaître les détails internes du plugin.
 */
function tikibar_render_search_filters() {
	static $search = null;
	if ( null === $search ) {
		$search = new TikiBar_Search();
	}
	$search->render_filters();
}

/**
 * À l'activation du plugin : on force WordPress à régénérer ses "rewrite rules"
 * (les URLs). Sans ça, /soiree/nom-de-la-soiree/ renverrait une 404 tant qu'on
 * n'irait pas resauvegarder les permaliens manuellement.
 */
function tikibar_activites_activation() {
	// On enregistre le CPT et les taxonomies avant de flush, sinon leurs
	// règles d'URL ne seraient pas encore connues de WordPress.
	$cpt = new TikiBar_CPT_Soiree();
	$cpt->register_post_type();
	$cpt->register_taxonomies();

	// Rôles et capacités : l'administrateur récupère les nouvelles capacités
	// (sinon il perdrait l'accès à ses propres soirées), et le rôle
	// "Gestionnaire" est créé avec un accès limité au contenu métier.
	TikiBar_Roles::grant_capabilities_to_administrator();
	TikiBar_Roles::create_role();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tikibar_activites_activation' );

/**
 * À la désactivation : on nettoie les règles d'URL. On NE supprime PAS les
 * soirées existantes (les données doivent survivre à la désactivation).
 */
function tikibar_activites_deactivation() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'tikibar_activites_deactivation' );
