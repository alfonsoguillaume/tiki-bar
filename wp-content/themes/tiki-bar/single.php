<?php
/**
 * Template générique pour un article de blog (post) individuel.
 * (single-soiree.php prendra le relais spécifiquement pour le CPT "soiree".)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="container content-area">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class(); ?>>
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<p class="meta">
				<?php
				printf(
					/* translators: %s : date de publication */
					esc_html__( 'Publié le %s', 'tiki-bar' ),
					esc_html( get_the_date() )
				);
				?>
			</p>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="entry-thumbnail"><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>

		<?php
		if ( comments_open() || get_comments_number() ) :
			comments_template();
		endif;
	endwhile;
	?>
</div>

<?php get_footer(); ?>
