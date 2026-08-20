<?php
/**
 * Template générique pour les pages d'archives (catégories, dates...).
 * (archive-soiree.php prendra le relais spécifiquement pour le CPT "soiree".)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="container content-area">
	<h1 class="entry-title"><?php the_archive_title(); ?></h1>
	<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>

	<?php if ( have_posts() ) : ?>
		<div class="card-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?><span class="screen-reader-text"><?php the_title(); ?></span></a>
					<?php endif; ?>
					<div class="card-body">
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p class="meta"><?php echo esc_html( get_the_date() ); ?></p>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
					</div>
				</article>
				<?php
			endwhile;
			?>
		</div>

		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Aucun contenu à afficher pour le moment.', 'tiki-bar' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
