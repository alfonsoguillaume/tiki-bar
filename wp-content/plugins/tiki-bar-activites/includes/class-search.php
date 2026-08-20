<?php
/**
 * Recherche et filtrage dynamique des soirées : formulaire (catégorie,
 * ambiance, lieu, période) + traitement en arrière-plan (AJAX) qui renvoie
 * uniquement le HTML des résultats, sans recharger la page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TikiBar_Search {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Deux hooks : un pour les visiteurs non connectés (le cas normal
		// ici), un pour les utilisateurs connectés (ex : toi en train de
		// tester en étant connecté à l'admin dans un autre onglet).
		add_action( 'wp_ajax_tikibar_filter_soirees', array( $this, 'handle_ajax_filter' ) );
		add_action( 'wp_ajax_nopriv_tikibar_filter_soirees', array( $this, 'handle_ajax_filter' ) );
	}

	public function enqueue_assets() {
		// On ne charge ce script QUE sur la page d'archive des soirées,
		// pas besoin ailleurs sur le site.
		if ( ! is_post_type_archive( 'soiree' ) ) {
			return;
		}

		wp_enqueue_script(
			'tikibar-search-filter',
			TIKIBAR_ACTIVITES_URL . 'public/js/search-filter.js',
			array(),
			filemtime( TIKIBAR_ACTIVITES_PATH . 'public/js/search-filter.js' ),
			true
		);

		// wp_localize_script transmet des données PHP vers le JS. Sur le
		// front (contrairement à l'admin), la variable globale "ajaxurl"
		// n'existe pas automatiquement — il faut la fournir nous-mêmes,
		// avec un nonce pour sécuriser la requête AJAX.
		wp_localize_script( 'tikibar-search-filter', 'tikibarSearch', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'tikibar_search_filter' ),
		) );
	}

	/**
	 * Affiche le formulaire de filtres. Appelé depuis le thème
	 * (archive-soiree.php) via la fonction pont tikibar_render_search_filters().
	 */
	public function render_filters() {

		$types    = get_terms( array( 'taxonomy' => 'type_soiree', 'hide_empty' => false ) );
		$ambiances = get_terms( array( 'taxonomy' => 'niveau_ambiance', 'hide_empty' => false ) );
		?>
		<form id="tikibar-search-form" class="tikibar-search-form">

			<div class="tikibar-search-field">
				<label for="tikibar-filtre-categorie"><?php esc_html_e( 'Catégorie', 'tiki-bar-activites' ); ?></label>
				<select id="tikibar-filtre-categorie" name="categorie">
					<option value=""><?php esc_html_e( 'Toutes', 'tiki-bar-activites' ); ?></option>
					<?php if ( ! is_wp_error( $types ) ) : foreach ( $types as $type ) : ?>
						<option value="<?php echo esc_attr( $type->slug ); ?>"><?php echo esc_html( $type->name ); ?></option>
					<?php endforeach; endif; ?>
				</select>
			</div>

			<div class="tikibar-search-field">
				<label for="tikibar-filtre-ambiance"><?php esc_html_e( 'Ambiance', 'tiki-bar-activites' ); ?></label>
				<select id="tikibar-filtre-ambiance" name="ambiance">
					<option value=""><?php esc_html_e( 'Toutes', 'tiki-bar-activites' ); ?></option>
					<?php if ( ! is_wp_error( $ambiances ) ) : foreach ( $ambiances as $ambiance ) : ?>
						<option value="<?php echo esc_attr( $ambiance->slug ); ?>"><?php echo esc_html( $ambiance->name ); ?></option>
					<?php endforeach; endif; ?>
				</select>
			</div>

			<div class="tikibar-search-field">
				<label for="tikibar-filtre-periode"><?php esc_html_e( 'Période', 'tiki-bar-activites' ); ?></label>
				<select id="tikibar-filtre-periode" name="periode">
					<option value="toutes"><?php esc_html_e( 'À venir', 'tiki-bar-activites' ); ?></option>
					<option value="ce-mois"><?php esc_html_e( 'Ce mois-ci', 'tiki-bar-activites' ); ?></option>
					<option value="mois-prochain"><?php esc_html_e( 'Mois prochain', 'tiki-bar-activites' ); ?></option>
					<option value="passees"><?php esc_html_e( 'Passées', 'tiki-bar-activites' ); ?></option>
				</select>
			</div>

			<div class="tikibar-search-field">
				<label for="tikibar-filtre-lieu"><?php esc_html_e( 'Lieu', 'tiki-bar-activites' ); ?></label>
				<input type="text" id="tikibar-filtre-lieu" name="lieu" placeholder="<?php esc_attr_e( 'ex : jardin', 'tiki-bar-activites' ); ?>">
			</div>

			<button type="submit" class="btn btn-ember"><?php esc_html_e( 'Filtrer', 'tiki-bar-activites' ); ?></button>
		</form>

		<p id="tikibar-search-status" aria-live="polite" class="tikibar-search-status"></p>

		<div id="tikibar-search-results" class="card-grid">
			<?php // Rempli initialement côté serveur par archive-soiree.php, puis remplacé dynamiquement par le JS. ?>
		</div>
		<?php
	}

	/**
	 * Traitement de la requête AJAX. Renvoie du JSON contenant le HTML des
	 * résultats et le nombre de soirées trouvées.
	 */
	public function handle_ajax_filter() {

		// Vérifie le nonce automatiquement ; arrête tout avec une erreur si
		// invalide (comportement par défaut de check_ajax_referer).
		check_ajax_referer( 'tikibar_search_filter', 'nonce' );

		// --- Sanitization + validation de chaque filtre reçu ---

		// Catégorie et ambiance : on nettoie le format (sanitize_key = lettres
		// minuscules, chiffres, tirets uniquement — le format d'un "slug"),
		// PUIS on vérifie que ce slug correspond à un terme qui existe
		// vraiment, plutôt que de faire confiance à une chaîne arbitraire.
		$categorie = isset( $_POST['categorie'] ) ? sanitize_key( $_POST['categorie'] ) : '';
		if ( $categorie && ! term_exists( $categorie, 'type_soiree' ) ) {
			$categorie = '';
		}

		$ambiance = isset( $_POST['ambiance'] ) ? sanitize_key( $_POST['ambiance'] ) : '';
		if ( $ambiance && ! term_exists( $ambiance, 'niveau_ambiance' ) ) {
			$ambiance = '';
		}

		// Période : liste blanche stricte, comme pour l'archive classique.
		$periodes_valides = array( 'toutes', 'ce-mois', 'mois-prochain', 'passees' );
		$periode = isset( $_POST['periode'] ) ? sanitize_text_field( $_POST['periode'] ) : 'toutes';
		if ( ! in_array( $periode, $periodes_valides, true ) ) {
			$periode = 'toutes';
		}

		// Lieu : champ texte libre, on le nettoie simplement. Utilisé plus
		// bas dans une comparaison "LIKE" — WordPress échappe automatiquement
		// les caractères spéciaux de ce type de comparaison en interne.
		$lieu = isset( $_POST['lieu'] ) ? sanitize_text_field( $_POST['lieu'] ) : '';

		// --- Construction de la requête ---

		$args = array(
			'post_type'      => 'soiree',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'meta_key'       => '_tikibar_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		);

		$meta_query = array();
		$tax_query  = array();

		$aujourdhui = new DateTime( 'today' );

		if ( 'passees' === $periode ) {
			$meta_query[] = array( 'key' => '_tikibar_date', 'value' => $aujourdhui->format( 'Y-m-d' ), 'compare' => '<', 'type' => 'DATE' );
			$args['order'] = 'DESC';
		} else {
			$meta_query[] = array( 'key' => '_tikibar_date', 'value' => $aujourdhui->format( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' );
			if ( 'ce-mois' === $periode ) {
				$fin = new DateTime( 'last day of this month' );
				$meta_query[] = array( 'key' => '_tikibar_date', 'value' => $fin->format( 'Y-m-d' ), 'compare' => '<=', 'type' => 'DATE' );
			} elseif ( 'mois-prochain' === $periode ) {
				$debut = new DateTime( 'first day of next month' );
				$fin   = new DateTime( 'last day of next month' );
				$meta_query[0]['value'] = $debut->format( 'Y-m-d' );
				$meta_query[] = array( 'key' => '_tikibar_date', 'value' => $fin->format( 'Y-m-d' ), 'compare' => '<=', 'type' => 'DATE' );
			}
		}

		if ( $lieu ) {
			$meta_query[] = array( 'key' => '_tikibar_lieu', 'value' => $lieu, 'compare' => 'LIKE' );
		}

		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'AND';
		}
		$args['meta_query'] = $meta_query;

		if ( $categorie ) {
			$tax_query[] = array( 'taxonomy' => 'type_soiree', 'field' => 'slug', 'terms' => $categorie );
		}
		if ( $ambiance ) {
			$tax_query[] = array( 'taxonomy' => 'niveau_ambiance', 'field' => 'slug', 'terms' => $ambiance );
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		if ( $tax_query ) {
			$args['tax_query'] = $tax_query;
		}

		$query = new WP_Query( $args );

		// --- Génération du HTML des résultats ---
		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$date  = get_post_meta( get_the_ID(), '_tikibar_date', true );
				$lieu_affiche = get_post_meta( get_the_ID(), '_tikibar_lieu', true );
				$tarif = get_post_meta( get_the_ID(), '_tikibar_tarif', true );
				?>
				<article <?php post_class( 'card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?><span class="screen-reader-text"><?php the_title(); ?></span></a>
					<?php endif; ?>
					<div class="card-body">
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p class="meta">
							<?php
							if ( $date ) { echo esc_html( date_i18n( 'j F Y', strtotime( $date ) ) ); }
							if ( $lieu_affiche ) { echo ' — ' . esc_html( $lieu_affiche ); }
							?>
						</p>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
						<?php if ( $tarif ) : ?>
							<p class="meta"><?php echo esc_html( number_format_i18n( (float) $tarif, 2 ) ); ?> €</p>
						<?php endif; ?>
					</div>
				</article>
				<?php
			}
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'Aucune soirée ne correspond à ces critères.', 'tiki-bar-activites' ) . '</p>';
		}
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html'  => $html,
			'count' => $query->found_posts,
		) );
	}
}
