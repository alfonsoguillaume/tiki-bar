<?php
/**
 * Custom Post Type "reservation" : une demande de réservation = un post de ce type.
 * Contrairement aux soirées, ce CPT n'est PAS public : personne ne doit pouvoir
 * accéder à /reservation/xxx/ depuis le front, ces données sont privées.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TikiBar_CPT_Reservation {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type() {

		$labels = array(
			'name'          => __( 'Réservations', 'tiki-bar-reservations' ),
			'singular_name' => __( 'Réservation', 'tiki-bar-reservations' ),
			'edit_item'     => __( 'Modifier la réservation', 'tiki-bar-reservations' ),
			'view_item'     => __( 'Voir la réservation', 'tiki-bar-reservations' ),
			'search_items'  => __( 'Rechercher une réservation', 'tiki-bar-reservations' ),
			'not_found'     => __( 'Aucune réservation trouvée', 'tiki-bar-reservations' ),
			'menu_name'     => __( 'Réservations', 'tiki-bar-reservations' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => false,        // pas d'URL publique, pas d'archive front
			'show_ui'         => true,          // mais bien visible et gérable dans l'admin
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-clipboard',
			'menu_position'   => 6,
			'capability_type' => 'post',
			'supports'        => array( 'title' ),
			'has_archive'     => false,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'show_in_rest'    => false,          // on ne l'expose pas sur l'API REST publique (données sensibles)
		);

		register_post_type( 'reservation', $args );
	}
}
