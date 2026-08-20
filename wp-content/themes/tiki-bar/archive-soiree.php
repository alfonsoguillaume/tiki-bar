<?php
/**
 * Affichage de l'archive des soirées (/soiree/), avec recherche et filtrage
 * dynamique (catégorie, ambiance, période, lieu) géré par le plugin
 * tiki-bar-activites via AJAX — voir includes/class-search.php.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="container content-area">
	<h1><?php esc_html_e( 'Toutes les soirées', 'tiki-bar' ); ?></h1>

	<?php
	/**
	 * Le formulaire ET le conteneur des résultats sont générés par le
	 * plugin : le thème ne fait qu'appeler cette fonction, il ne connaît
	 * pas la logique de filtrage. Si le plugin est désactivé, on affiche
	 * un message de repli plutôt qu'une erreur PHP.
	 */
	if ( function_exists( 'tikibar_render_search_filters' ) ) {
		tikibar_render_search_filters();
	} else {
		esc_html_e( 'La recherche de soirées est temporairement indisponible.', 'tiki-bar' );
	}
	?>
</div>

<?php get_footer(); ?>
