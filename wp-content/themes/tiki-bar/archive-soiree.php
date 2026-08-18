<?php
/**
 * Affichage de l'archive des soirées (/soiree/), avec filtre rapide par période.
 * archive-{post_type}.php a priorité sur archive.php pour ce CPT précis.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

// La période sélectionnée vient de l'URL (?periode=ce-mois), avec "toutes" par défaut.
// whitelist stricte : on ne fait confiance qu'à ces 3 valeurs précises.
$periodes_valides = array( 'toutes', 'ce-mois', 'mois-prochain', 'passees' );
$periode = isset( $_GET['periode'] ) ? sanitize_text_field( $_GET['periode'] ) : 'toutes';
if ( ! in_array( $periode, $periodes_valides, true ) ) {
	$periode = 'toutes';
}

// On calcule les bornes de dates selon la période choisie.
$aujourdhui = new DateTime( 'today' );
$order = 'ASC'; // les soirées à venir se trient de la plus proche à la plus lointaine

if ( 'passees' === $periode ) {

	// Cas particulier : on veut les soirées AVANT aujourd'hui, triées en partant
	// de la plus récente (la dernière soirée passée en premier, plus logique
	// pour un historique que de commencer par la plus ancienne).
	$meta_query = array(
		array(
			'key'     => '_tikibar_date',
			'value'   => $aujourdhui->format( 'Y-m-d' ),
			'compare' => '<',
			'type'    => 'DATE',
		),
	);
	$order = 'DESC';

} else {

	// Dans tous les autres cas (toutes / ce-mois / mois-prochain), base commune :
	// on ne montre jamais les soirées déjà passées.
	$meta_query = array(
		array(
			'key'     => '_tikibar_date',
			'value'   => $aujourdhui->format( 'Y-m-d' ),
			'compare' => '>=',
			'type'    => 'DATE',
		),
	);

	if ( 'ce-mois' === $periode ) {
		$fin_mois = new DateTime( 'last day of this month' );
		$meta_query[] = array(
			'key'     => '_tikibar_date',
			'value'   => $fin_mois->format( 'Y-m-d' ),
			'compare' => '<=',
			'type'    => 'DATE',
		);
	} elseif ( 'mois-prochain' === $periode ) {
		$debut = new DateTime( 'first day of next month' );
		$fin   = new DateTime( 'last day of next month' );
		// on écrase la borne basse générique par le début du mois prochain
		$meta_query[0]['value'] = $debut->format( 'Y-m-d' );
		$meta_query[] = array(
			'key'     => '_tikibar_date',
			'value'   => $fin->format( 'Y-m-d' ),
			'compare' => '<=',
			'type'    => 'DATE',
		);
	}
}

$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$soirees_query = new WP_Query( array(
	'post_type'      => 'soiree',
	'posts_per_page' => 9,
	'paged'          => $paged,
	'meta_key'       => '_tikibar_date',
	'orderby'        => 'meta_value',
	'order'          => $order,
	'meta_query'     => $meta_query,
) );

// On reconstruit l'URL de base (sans le paramètre "periode") pour générer les liens des boutons.
$base_url = get_post_type_archive_link( 'soiree' );
?>

<div class="container content-area">
	<h1><?php esc_html_e( 'Toutes les soirées', 'tiki-bar' ); ?></h1>

	<div class="periode-filter" role="group" aria-label="<?php esc_attr_e( 'Filtrer par période', 'tiki-bar' ); ?>" style="display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:2rem;">
		<a class="btn <?php echo 'toutes' === $periode ? 'btn-ember' : 'btn-outline'; ?>" href="<?php echo esc_url( add_query_arg( 'periode', 'toutes', $base_url ) ); ?>">
			<?php esc_html_e( 'Toutes', 'tiki-bar' ); ?>
		</a>
		<a class="btn <?php echo 'ce-mois' === $periode ? 'btn-ember' : 'btn-outline'; ?>" href="<?php echo esc_url( add_query_arg( 'periode', 'ce-mois', $base_url ) ); ?>">
			<?php esc_html_e( 'Ce mois-ci', 'tiki-bar' ); ?>
		</a>
		<a class="btn <?php echo 'mois-prochain' === $periode ? 'btn-ember' : 'btn-outline'; ?>" href="<?php echo esc_url( add_query_arg( 'periode', 'mois-prochain', $base_url ) ); ?>">
			<?php esc_html_e( 'Mois prochain', 'tiki-bar' ); ?>
		</a>
		<a class="btn <?php echo 'passees' === $periode ? 'btn-ember' : 'btn-outline'; ?>" href="<?php echo esc_url( add_query_arg( 'periode', 'passees', $base_url ) ); ?>">
			<?php esc_html_e( 'Passées', 'tiki-bar' ); ?>
		</a>
	</div>

	<?php if ( $soirees_query->have_posts() ) : ?>
		<div class="card-grid">
			<?php
			while ( $soirees_query->have_posts() ) :
				$soirees_query->the_post();
				$date  = get_post_meta( get_the_ID(), '_tikibar_date', true );
				$lieu  = get_post_meta( get_the_ID(), '_tikibar_lieu', true );
				$tarif = get_post_meta( get_the_ID(), '_tikibar_tarif', true );
				?>
				<article <?php post_class( 'card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a>
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
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
						<?php if ( $tarif ) : ?>
							<p class="meta"><?php echo esc_html( number_format_i18n( (float) $tarif, 2 ) ); ?> €</p>
						<?php endif; ?>
					</div>
				</article>
				<?php
			endwhile;
			?>
		</div>

		<?php
		echo paginate_links( array(
			'total'    => $soirees_query->max_num_pages,
			'current'  => $paged,
			'format'   => '?paged=%#%',
			'add_args' => array( 'periode' => $periode ),
		) );
		wp_reset_postdata();
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'Aucune soirée à afficher pour cette période.', 'tiki-bar' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
