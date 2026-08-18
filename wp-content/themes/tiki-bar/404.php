<?php
/**
 * Template affiché quand aucune URL ne correspond (erreur 404).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="container content-area" style="text-align:center;">
	<p class="eyebrow"><?php esc_html_e( 'Erreur 404', 'tiki-bar' ); ?></p>
	<h1><?php esc_html_e( 'Ce chemin ne mène à aucune soirée', 'tiki-bar' ); ?></h1>
	<p><?php esc_html_e( 'La page que vous cherchez n\'existe pas ou plus. Retournez au bar.', 'tiki-bar' ); ?></p>
	<a class="btn btn-ember" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Retour à l\'accueil', 'tiki-bar' ); ?></a>
</div>

<?php get_footer(); ?>
