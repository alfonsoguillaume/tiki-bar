<?php
/**
 * Plugin Name: Tiki Bar - Réservations
 * Description: Permet aux visiteurs de demander une réservation pour une soirée, et à l'administrateur de gérer les demandes (statut : en attente / acceptée / refusée). Totalement indépendant du thème.
 * Version: 1.0
 * Author: Toi
 * Text Domain: tiki-bar-reservations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TIKIBAR_RESA_PATH', plugin_dir_path( __FILE__ ) );
define( 'TIKIBAR_RESA_URL', plugin_dir_url( __FILE__ ) );

require_once TIKIBAR_RESA_PATH . 'includes/class-cpt-reservation.php';
require_once TIKIBAR_RESA_PATH . 'includes/class-form.php';
require_once TIKIBAR_RESA_PATH . 'includes/class-admin.php';

function tikibar_resa_init() {
	new TikiBar_CPT_Reservation();
	new TikiBar_Reservation_Form();
	if ( is_admin() ) {
		new TikiBar_Reservation_Admin();
	}
}
add_action( 'plugins_loaded', 'tikibar_resa_init' );

function tikibar_resa_activation() {
	$cpt = new TikiBar_CPT_Reservation();
	$cpt->register_post_type();

	// Sécurité d'ordre d'activation : on attribue les capacités liées aux
	// réservations à l'administrateur ET au rôle "Gestionnaire" ici aussi
	// (même logique que dans tiki-bar-activites), au cas où ce plugin serait
	// activé avant, après, ou indépendamment de tiki-bar-activites.
	$capacites_reservations = array(
		'edit_reservations', 'edit_others_reservations', 'publish_reservations',
		'read_private_reservations', 'delete_reservations', 'delete_private_reservations',
		'delete_published_reservations', 'delete_others_reservations',
		'edit_private_reservations', 'edit_published_reservations',
	);

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( $capacites_reservations as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	// Le rôle "Gestionnaire" n'existe que si tiki-bar-activites est actif ;
	// get_role() renvoie simplement null si ce n'est pas le cas, pas d'erreur.
	$gestionnaire = get_role( 'gestionnaire_tikibar' );
	if ( $gestionnaire ) {
		foreach ( $capacites_reservations as $cap ) {
			$gestionnaire->add_cap( $cap );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tikibar_resa_activation' );

function tikibar_resa_deactivation() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'tikibar_resa_deactivation' );

/**
 * Fonction "pont" entre le thème et le plugin : le thème appelle uniquement
 * cette fonction, sans jamais savoir comment le plugin est construit en
 * interne (classe, fichiers...). C'est ce découplage qui garantit que le
 * thème peut changer sans casser la fonctionnalité de réservation.
 */
function tikibar_render_reservation_form( $soiree_id ) {
	static $form = null;
	if ( null === $form ) {
		$form = new TikiBar_Reservation_Form();
	}
	$form->render( (int) $soiree_id );
}
