<?php
/**
 * Template de la page d'accueil.
 * Dans le Template Hierarchy WordPress, front-page.php a la priorité
 * absolue pour la page d'accueil dès qu'il existe.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
get_template_part( 'template-parts/legal-modal' );
?>

<section class="hero">
	<div class="container">
		<p class="eyebrow"><?php esc_html_e( 'Bar clandestin de jardin — sur invitation', 'tiki-bar' ); ?></p>
		<h1><?php esc_html_e( 'Le Tiki Bar', 'tiki-bar' ); ?></h1>
		<p class="lead">
			<?php esc_html_e( 'Un coin de jardin transformé en refuge tropical le temps d\'une soirée. Cocktails maison, lumière de torches, places limitées.', 'tiki-bar' ); ?>
		</p>
		<div class="hero-actions">
			<a class="btn btn-ember" href="<?php echo esc_url( get_post_type_archive_link( 'soiree' ) ); ?>"><?php esc_html_e( 'Voir les prochaines soirées', 'tiki-bar' ); ?></a>
		</div>
	</div>
</section>

<div class="frond-divider" aria-hidden="true"></div>

<section id="prochaines-soirees" class="container">
	<h2><?php esc_html_e( 'Prochaines soirées', 'tiki-bar' ); ?></h2>

	<?php
	/**
	 * Requête dédiée sur le CPT "soiree" (géré par le plugin tiki-bar-activites),
	 * limitée aux 3 prochaines soirées, triées par date de soirée (champ meta)
	 * plutôt que par date de publication de l'article.
	 */
	$soirees = new WP_Query( array(
		'post_type'      => 'soiree',
		'posts_per_page' => 3,
		'meta_key'       => '_tikibar_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_tikibar_date',
				'value'   => date( 'Y-m-d' ),
				'compare' => '>=', // on ne montre que les soirées à venir
				'type'    => 'DATE',
			),
		),
	) );

	if ( $soirees->have_posts() ) :
		echo '<div class="card-grid">';
		while ( $soirees->have_posts() ) :
			$soirees->the_post();
			$date  = get_post_meta( get_the_ID(), '_tikibar_date', true );
			$lieu  = get_post_meta( get_the_ID(), '_tikibar_lieu', true );
			?>
			<article <?php post_class( 'card' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?><span class="screen-reader-text"><?php the_title(); ?></span></a>
				<?php endif; ?>
				<div class="card-body">
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p class="meta">
						<?php
						if ( $date ) {
							echo esc_html( date_i18n( 'j F Y', strtotime( $date ) ) );
						}
						if ( $lieu ) {
							echo ' — ' . esc_html( $lieu );
						}
						?>
					</p>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
				</div>
			</article>
			<?php
		endwhile;
		echo '</div>';
		wp_reset_postdata();
	else :
		?>
		<p><?php esc_html_e( 'Aucune soirée annoncée pour le moment — revenez bientôt.', 'tiki-bar' ); ?></p>
		<?php
	endif;
	?>
</section>

<?php get_footer(); ?>
