<?php
/**
 * Affichage d'une soirée individuelle.
 * Dans le Template Hierarchy, single-{post_type}.php a priorité sur single.php
 * pour ce type de contenu précis : WordPress choisira ce fichier automatiquement.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) :
	the_post();

	$date         = get_post_meta( get_the_ID(), '_tikibar_date', true );
	$heure        = get_post_meta( get_the_ID(), '_tikibar_heure', true );
	$duree        = get_post_meta( get_the_ID(), '_tikibar_duree', true );
	$lieu         = get_post_meta( get_the_ID(), '_tikibar_lieu', true );
	$tarif        = get_post_meta( get_the_ID(), '_tikibar_tarif', true );
	$participants = get_post_meta( get_the_ID(), '_tikibar_participants_max', true );
	$statut       = get_post_meta( get_the_ID(), '_tikibar_statut', true );

	$statut_labels = array(
		'disponible' => __( 'Places disponibles', 'tiki-bar' ),
		'complet'    => __( 'Complet', 'tiki-bar' ),
		'annule'     => __( 'Annulée', 'tiki-bar' ),
	);
	?>

	<article class="container content-area">

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="entry-thumbnail"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>

		<p class="eyebrow">
			<?php
			$types = get_the_terms( get_the_ID(), 'type_soiree' );
			if ( $types && ! is_wp_error( $types ) ) {
				echo esc_html( wp_list_pluck( $types, 'name' )[0] );
			}
			?>
		</p>
		<h1><?php the_title(); ?></h1>

		<ul class="soiree-meta" style="list-style:none; padding:0; font-family:var(--font-mono); color:var(--tiki-bamboo); display:flex; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem;">
			<?php if ( $date ) : ?>
				<li>📅 <?php echo esc_html( date_i18n( 'j F Y', strtotime( $date ) ) ); ?></li>
			<?php endif; ?>
			<?php if ( $heure ) : ?>
				<li>🕗 <?php echo esc_html( $heure ); ?></li>
			<?php endif; ?>
			<?php if ( $duree ) : ?>
				<li>⏱ <?php echo esc_html( $duree ); ?></li>
			<?php endif; ?>
			<?php if ( $lieu ) : ?>
				<li>📍 <?php echo esc_html( $lieu ); ?></li>
			<?php endif; ?>
			<?php if ( $tarif ) : ?>
				<li>💶 <?php echo esc_html( number_format_i18n( (float) $tarif, 2 ) ); ?> €</li>
			<?php endif; ?>
			<?php if ( $participants ) : ?>
				<li>👥 <?php echo esc_html( $participants ); ?> <?php esc_html_e( 'places max', 'tiki-bar' ); ?></li>
			<?php endif; ?>
			<?php if ( $statut && isset( $statut_labels[ $statut ] ) ) : ?>
				<li><strong><?php echo esc_html( $statut_labels[ $statut ] ); ?></strong></li>
			<?php endif; ?>
		</ul>

		<div class="entry-content">
			<?php the_content(); ?>
		</div>

		<?php if ( $statut === 'disponible' ) : ?>
			<div class="reservation-cta" style="margin-top:2rem; padding:1.5rem; background:var(--tiki-frond); border-radius:var(--radius);">
				<h2><?php esc_html_e( 'Demander une réservation', 'tiki-bar' ); ?></h2>
				<?php
				/**
				 * Le formulaire est fourni par le plugin tiki-bar-reservations,
				 * pas par le thème : si demain on change de thème, la fonctionnalité
				 * de réservation continue de fonctionner à l'identique.
				 */
				if ( function_exists( 'tikibar_render_reservation_form' ) ) {
					tikibar_render_reservation_form( get_the_ID() );
				} else {
					// Le plugin est désactivé : le thème ne plante pas, il informe simplement.
					esc_html_e( 'Les réservations sont temporairement indisponibles.', 'tiki-bar' );
				}
				?>
			</div>
		<?php endif; ?>

	</article>

	<?php
endwhile;

get_footer();
