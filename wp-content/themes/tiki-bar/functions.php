<?php
/**
 * Le Tiki Bar - fonctions du thème
 *
 * On centralise ici tout ce qui "configure" WordPress pour notre thème :
 * support de fonctionnalités, menus, styles/scripts, widgets.
 * Rien de "métier" ne doit vivre ici (ça, c'est le rôle du plugin).
 */

// Sécurité : on empêche l'accès direct au fichier hors de WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. Configuration générale du thème (add_theme_support)
 * Hooké sur 'after_setup_theme', l'action qui se déclenche au chargement du thème.
 */
function tikibar_setup() {

	// Le <title> de chaque page est généré automatiquement par WordPress.
	add_theme_support( 'title-tag' );

	// Permet d'avoir une image mise en avant sur les articles / soirées.
	add_theme_support( 'post-thumbnails' );

	// Rendu HTML5 propre pour les formulaires de recherche, commentaires, galeries...
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	// Permet de définir un logo depuis Apparence > Personnaliser.
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 80,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	// Le thème gère lui-même la largeur du contenu (utile pour l'intégration de médias).
	add_theme_support( 'responsive-embeds' );

	// Déclaration de l'emplacement de menu principal, utilisable dans Apparence > Menus.
	register_nav_menus( array(
		'primary' => __( 'Menu principal', 'tiki-bar' ),
		'footer'  => __( 'Menu de pied de page', 'tiki-bar' ),
	) );
}
add_action( 'after_setup_theme', 'tikibar_setup' );

/**
 * 2. Chargement des styles et scripts (JAMAIS en dur dans le HTML)
 * Hooké sur 'wp_enqueue_scripts', l'action dédiée au front.
 */
function tikibar_enqueue_assets() {

	// Polices : Google Fonts (display + texte + mono pour les prix/dates)
	wp_enqueue_style(
		'tikibar-fonts',
		'https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Manrope:wght@400;500;700;800&family=JetBrains+Mono:wght@400;600&display=swap',
		array(),
		null
	);

	// Feuille de style principale : on utilise la date de modification du fichier
	// comme numéro de version, pour éviter les problèmes de cache navigateur.
	wp_enqueue_style(
		'tikibar-style',
		get_stylesheet_uri(),
		array(),
		filemtime( get_stylesheet_directory() . '/style.css' )
	);

	// JS du thème (menu mobile, etc.)
	wp_enqueue_script(
		'tikibar-navigation',
		get_template_directory_uri() . '/assets/js/navigation.js',
		array(),
		filemtime( get_template_directory() . '/assets/js/navigation.js' ),
		true // chargé en pied de page
	);

	// Commentaires natifs WordPress (thread + reply), seulement si nécessaire.
	if ( is_singular() && comments_open() ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Script de la modale d'avertissement légal, uniquement sur la page d'accueil
	// (is_front_page() : pas besoin de le charger ailleurs sur le site).
	if ( is_front_page() ) {
		wp_enqueue_script(
			'tikibar-legal-modal',
			get_template_directory_uri() . '/assets/js/legal-modal.js',
			array(),
			filemtime( get_template_directory() . '/assets/js/legal-modal.js' ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'tikibar_enqueue_assets' );

/**
 * 3. Zone de widgets (pied de page)
 */
function tikibar_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Pied de page', 'tiki-bar' ),
		'id'            => 'footer-1',
		'description'   => __( 'Widgets affichés dans le pied de page.', 'tiki-bar' ),
		'before_widget' => '<div class="footer-widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3>',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'tikibar_widgets_init' );

/**
 * 4. Largeur d'image par défaut pour cohérence avec le CSS (.card img)
 */
if ( ! isset( $content_width ) ) {
	$content_width = 1140;
}

/**
 * 5. Nettoyage du <head> : on retire des infos qui n'apportent rien
 * en matière de sécurité / performance (ex : la version de WP visible publiquement).
 */
remove_action( 'wp_head', 'wp_generator' );
