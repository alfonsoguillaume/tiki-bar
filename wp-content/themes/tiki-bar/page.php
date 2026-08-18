<?php
/**
 * Template générique pour les pages (Contact, Infos pratiques, etc.).
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

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="entry-thumbnail"><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>

			<div class="entry-content">
				<?php
				the_content();

				wp_link_pages( array(
					'before' => '<nav class="page-links">' . esc_html__( 'Pages :', 'tiki-bar' ),
					'after'  => '</nav>',
				) );
				?>
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
