<?php
/**
 * Déclare le Custom Post Type "soiree" et ses deux taxonomies.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TikiBar_CPT_Soiree {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Custom Post Type "soiree".
	 * On utilise register_post_type(), la fonction native de WordPress -
	 * c'est ce qui permet d'avoir un écran d'admin dédié, sans rien coder
	 * "à la main" pour lister/éditer/supprimer les soirées.
	 */
	public function register_post_type() {

		$labels = array(
			'name'               => __( 'Soirées', 'tiki-bar-activites' ),
			'singular_name'      => __( 'Soirée', 'tiki-bar-activites' ),
			'add_new_item'       => __( 'Ajouter une soirée', 'tiki-bar-activites' ),
			'edit_item'          => __( 'Modifier la soirée', 'tiki-bar-activites' ),
			'new_item'           => __( 'Nouvelle soirée', 'tiki-bar-activites' ),
			'view_item'          => __( 'Voir la soirée', 'tiki-bar-activites' ),
			'search_items'       => __( 'Rechercher une soirée', 'tiki-bar-activites' ),
			'not_found'          => __( 'Aucune soirée trouvée', 'tiki-bar-activites' ),
			'menu_name'          => __( 'Soirées', 'tiki-bar-activites' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-tickets-alt',
			'has_archive'        => true,          // active /soiree/ (archive.php dédié)
			'rewrite'            => array( 'slug' => 'soiree' ),
			'show_in_rest'       => true,           // indispensable pour Gutenberg + future API REST
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'menu_position'      => 5,
			'capability_type'    => array( 'soiree', 'soirees' ), // capacités propres au CPT (edit_soirees, publish_soirees...) plutôt que celles, partagées, des articles de blog
			'map_meta_cap'       => true, // indispensable : indique à WordPress de traduire automatiquement les vérifications ("peut modifier CETTE soirée précise") vers les bonnes capacités générales
		);

		register_post_type( 'soiree', $args );
	}

	/**
	 * Deux taxonomies personnalisées, exploitables sur les pages d'archives
	 * (ex : /soiree/type_soiree/degustation-cocktails/).
	 */
	public function register_taxonomies() {

		// Taxonomie 1 : type de soirée.
		register_taxonomy( 'type_soiree', 'soiree', array(
			'labels' => array(
				'name'          => __( 'Types de soirée', 'tiki-bar-activites' ),
				'singular_name' => __( 'Type de soirée', 'tiki-bar-activites' ),
			),
			'hierarchical'  => true, // comportement "catégorie" (choix unique/multiple dans une liste)
			'public'        => true,
			'show_in_rest'  => true,
			'rewrite'       => array( 'slug' => 'type-soiree' ),
		) );

		// Taxonomie 2 : ambiance (équivalent du "niveau" du cahier des charges,
		// adapté au contexte bar plutôt qu'à la randonnée).
		register_taxonomy( 'niveau_ambiance', 'soiree', array(
			'labels' => array(
				'name'          => __( 'Niveaux d\'ambiance', 'tiki-bar-activites' ),
				'singular_name' => __( 'Niveau d\'ambiance', 'tiki-bar-activites' ),
			),
			'hierarchical'  => true,
			'public'        => true,
			'show_in_rest'  => true,
			'rewrite'       => array( 'slug' => 'ambiance' ),
		) );
	}
}
