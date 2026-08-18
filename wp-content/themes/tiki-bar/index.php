<?php
/**
 * Template de secours obligatoire dans WordPress.
 * C'est le dernier maillon du Template Hierarchy : si aucun fichier plus
 * spécifique ne correspond (front-page.php, single.php, page.php...),
 * WordPress se rabat toujours sur index.php. Sans lui, le thème est invalide.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="container content-area">
	<?php if ( have_posts() ) : ?>
		<div class="card-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'card' ); ?>>
					<div class="card-body">
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
					</div>
				</article>
				<?php
			endwhile;
			?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'Rien à afficher pour le moment.', 'tiki-bar' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
