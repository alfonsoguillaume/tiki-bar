<?php
/**
 * Tableau de bord admin : affiche les chiffres clés du site (soirées et
 * réservations). Volontairement simple, comme demandé dans le cahier des
 * charges : les 4 indicateurs minimum requis, pas plus.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TikiBar_Dashboard {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	private $hook_suffix;

	/**
	 * Ajoute une page dans le menu admin. La capacité requise ('edit_soirees')
	 * correspond à celle qu'on a attribuée à l'administrateur ET au rôle
	 * Gestionnaire — les deux profils doivent pouvoir consulter ce tableau
	 * de bord, contrairement aux réglages généraux du site.
	 */
	public function register_page() {
		$this->hook_suffix = add_menu_page(
			__( 'Tableau de bord Tiki Bar', 'tiki-bar-activites' ),
			__( 'Tableau de bord', 'tiki-bar-activites' ),
			'edit_soirees',
			'tikibar-dashboard',
			array( $this, 'render' ),
			'dashicons-chart-bar',
			3 // position dans le menu, avant "Soirées" et "Réservations"
		);
	}

	/**
	 * On ne charge ce CSS QUE sur notre page de tableau de bord, jamais sur
	 * le reste de l'admin ($hook permet de vérifier "quelle page admin
	 * est en train de s'afficher").
	 */
	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}
		wp_enqueue_style(
			'tikibar-dashboard',
			TIKIBAR_ACTIVITES_URL . 'admin/css/dashboard.css',
			array(),
			filemtime( TIKIBAR_ACTIVITES_PATH . 'admin/css/dashboard.css' )
		);
	}

	/**
	 * Calcule les 4 indicateurs demandés par le cahier des charges.
	 */
	private function get_stats() {

		// 1. Nombre total d'activités (soirées publiées).
		$total = wp_count_posts( 'soiree' );
		$nombre_total_activites = isset( $total->publish ) ? (int) $total->publish : 0;

		// 2. Nombre d'activités à venir (date >= aujourd'hui).
		$query_a_venir = new WP_Query( array(
			'post_type'      => 'soiree',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids', // on ne charge que les ID, pas tout le contenu : plus rapide pour un simple comptage
			'meta_query'     => array(
				array(
					'key'     => '_tikibar_date',
					'value'   => date( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		) );
		$nombre_activites_a_venir = $query_a_venir->found_posts;

		// 3. Réservations en attente.
		$query_attente = new WP_Query( array(
			'post_type'      => 'reservation',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => '_tikibar_statut', 'value' => 'en_attente' ),
			),
		) );
		$nombre_reservations_attente = $query_attente->found_posts;

		// 4. Réservations acceptées.
		$query_acceptees = new WP_Query( array(
			'post_type'      => 'reservation',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => '_tikibar_statut', 'value' => 'acceptee' ),
			),
		) );
		$nombre_reservations_acceptees = $query_acceptees->found_posts;

		return array(
			'total_activites'        => $nombre_total_activites,
			'activites_a_venir'      => $nombre_activites_a_venir,
			'reservations_attente'   => $nombre_reservations_attente,
			'reservations_acceptees' => $nombre_reservations_acceptees,
		);
	}

	public function render() {
		$stats = $this->get_stats();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tableau de bord — Le Tiki Bar', 'tiki-bar-activites' ); ?></h1>

			<div class="tikibar-dashboard-grid">

				<div class="tikibar-dashboard-card">
					<span class="tikibar-dashboard-number"><?php echo esc_html( $stats['total_activites'] ); ?></span>
					<span class="tikibar-dashboard-label"><?php esc_html_e( 'Activités au total', 'tiki-bar-activites' ); ?></span>
				</div>

				<div class="tikibar-dashboard-card">
					<span class="tikibar-dashboard-number"><?php echo esc_html( $stats['activites_a_venir'] ); ?></span>
					<span class="tikibar-dashboard-label"><?php esc_html_e( 'Activités à venir', 'tiki-bar-activites' ); ?></span>
				</div>

				<div class="tikibar-dashboard-card">
					<span class="tikibar-dashboard-number"><?php echo esc_html( $stats['reservations_attente'] ); ?></span>
					<span class="tikibar-dashboard-label"><?php esc_html_e( 'Réservations en attente', 'tiki-bar-activites' ); ?></span>
				</div>

				<div class="tikibar-dashboard-card">
					<span class="tikibar-dashboard-number"><?php echo esc_html( $stats['reservations_acceptees'] ); ?></span>
					<span class="tikibar-dashboard-label"><?php esc_html_e( 'Réservations acceptées', 'tiki-bar-activites' ); ?></span>
				</div>

			</div>
		</div>
		<?php
	}
}
