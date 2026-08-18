<?php
/**
 * Interface d'administration des réservations :
 * - colonnes personnalisées dans la liste (statut, soirée, participants...)
 * - filtre par statut
 * - boîte de champs sur l'écran d'édition pour voir la demande et changer le statut
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TikiBar_Reservation_Admin {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Colonnes de la liste des réservations.
		add_filter( 'manage_reservation_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_reservation_posts_custom_column', array( $this, 'render_column' ), 10, 2 );

		// Filtre par statut au-dessus de la liste.
		add_action( 'restrict_manage_posts', array( $this, 'status_filter' ) );
		add_filter( 'parse_query', array( $this, 'apply_status_filter' ) );

		// Boîte de détails + statut sur l'écran d'édition.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_reservation', array( $this, 'save_status' ) );

		// On désactive l'éditeur de contenu par défaut (inutile pour ce CPT).
		add_action( 'admin_head', array( $this, 'hide_unused_boxes' ) );
	}

	public function enqueue_assets( $hook ) {
		global $post_type;
		if ( 'reservation' === $post_type ) {
			wp_enqueue_style(
				'tikibar-reservation-admin',
				TIKIBAR_RESA_URL . 'admin/css/admin.css',
				array(),
				filemtime( TIKIBAR_RESA_PATH . 'admin/css/admin.css' )
			);
		}
	}

	public function columns( $columns ) {
		$new = array();
		$new['cb'] = $columns['cb'];
		$new['title'] = __( 'Demandeur', 'tiki-bar-reservations' );
		$new['soiree'] = __( 'Soirée', 'tiki-bar-reservations' );
		$new['contact'] = __( 'Contact', 'tiki-bar-reservations' );
		$new['participants'] = __( 'Participants', 'tiki-bar-reservations' );
		$new['statut'] = __( 'Statut', 'tiki-bar-reservations' );
		$new['date'] = $columns['date'];
		return $new;
	}

	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'soiree':
				$soiree_id = get_post_meta( $post_id, '_tikibar_soiree_id', true );
				if ( $soiree_id && get_post( $soiree_id ) ) {
					echo '<a href="' . esc_url( get_edit_post_link( $soiree_id ) ) . '">' . esc_html( get_the_title( $soiree_id ) ) . '</a>';
				} else {
					esc_html_e( 'Soirée supprimée', 'tiki-bar-reservations' );
				}
				break;

			case 'contact':
				$email = get_post_meta( $post_id, '_tikibar_email', true );
				$tel   = get_post_meta( $post_id, '_tikibar_telephone', true );
				echo esc_html( $email );
				if ( $tel ) {
					echo '<br>' . esc_html( $tel );
				}
				break;

			case 'participants':
				echo esc_html( get_post_meta( $post_id, '_tikibar_participants', true ) );
				break;

			case 'statut':
				$statut = get_post_meta( $post_id, '_tikibar_statut', true );
				echo $this->status_badge( $statut );
				break;
		}
	}

	private function status_labels() {
		return array(
			'en_attente' => __( 'En attente', 'tiki-bar-reservations' ),
			'acceptee'   => __( 'Acceptée', 'tiki-bar-reservations' ),
			'refusee'    => __( 'Refusée', 'tiki-bar-reservations' ),
		);
	}

	private function status_badge( $statut ) {
		$labels = $this->status_labels();
		$label  = $labels[ $statut ] ?? $statut;
		$class  = 'tikibar-badge tikibar-badge-' . sanitize_html_class( $statut );
		return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
	}

	public function status_filter() {
		global $typenow;
		if ( 'reservation' !== $typenow ) {
			return;
		}
		$current = isset( $_GET['tikibar_statut'] ) ? sanitize_text_field( $_GET['tikibar_statut'] ) : '';
		?>
		<select name="tikibar_statut">
			<option value=""><?php esc_html_e( 'Tous les statuts', 'tiki-bar-reservations' ); ?></option>
			<?php foreach ( $this->status_labels() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function apply_status_filter( $query ) {
		global $pagenow, $typenow;
		if ( is_admin() && 'edit.php' === $pagenow && 'reservation' === $typenow && ! empty( $_GET['tikibar_statut'] ) ) {
			$statut = sanitize_text_field( $_GET['tikibar_statut'] );
			$query->query_vars['meta_key']   = '_tikibar_statut';
			$query->query_vars['meta_value'] = $statut;
		}
		return $query;
	}

	public function add_meta_box() {
		add_meta_box(
			'tikibar_reservation_details',
			__( 'Détails de la demande', 'tiki-bar-reservations' ),
			array( $this, 'render_meta_box' ),
			'reservation',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'tikibar_reservation_status_save', 'tikibar_reservation_status_nonce' );

		$soiree_id    = get_post_meta( $post->ID, '_tikibar_soiree_id', true );
		$prenom       = get_post_meta( $post->ID, '_tikibar_prenom', true );
		$nom          = get_post_meta( $post->ID, '_tikibar_nom', true );
		$email        = get_post_meta( $post->ID, '_tikibar_email', true );
		$telephone    = get_post_meta( $post->ID, '_tikibar_telephone', true );
		$participants = get_post_meta( $post->ID, '_tikibar_participants', true );
		$commentaire  = get_post_meta( $post->ID, '_tikibar_commentaire', true );
		$statut       = get_post_meta( $post->ID, '_tikibar_statut', true );
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Soirée concernée', 'tiki-bar-reservations' ); ?></th>
				<td>
					<?php if ( $soiree_id && get_post( $soiree_id ) ) : ?>
						<a href="<?php echo esc_url( get_edit_post_link( $soiree_id ) ); ?>"><?php echo esc_html( get_the_title( $soiree_id ) ); ?></a>
					<?php else : ?>
						<em><?php esc_html_e( 'Soirée supprimée', 'tiki-bar-reservations' ); ?></em>
					<?php endif; ?>
				</td>
			</tr>
			<tr><th><?php esc_html_e( 'Nom complet', 'tiki-bar-reservations' ); ?></th><td><?php echo esc_html( $prenom . ' ' . $nom ); ?></td></tr>
			<tr><th><?php esc_html_e( 'E-mail', 'tiki-bar-reservations' ); ?></th><td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td></tr>
			<tr><th><?php esc_html_e( 'Téléphone', 'tiki-bar-reservations' ); ?></th><td><?php echo esc_html( $telephone ?: '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Participants', 'tiki-bar-reservations' ); ?></th><td><?php echo esc_html( $participants ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Commentaire', 'tiki-bar-reservations' ); ?></th><td><?php echo nl2br( esc_html( $commentaire ?: '—' ) ); ?></td></tr>
			<tr>
				<th><label for="tikibar_statut_select"><?php esc_html_e( 'Statut', 'tiki-bar-reservations' ); ?></label></th>
				<td>
					<select id="tikibar_statut_select" name="tikibar_statut_select">
						<?php foreach ( $this->status_labels() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $statut, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_status( $post_id ) {
		if ( ! isset( $_POST['tikibar_reservation_status_nonce'] ) ||
			! wp_verify_nonce( $_POST['tikibar_reservation_status_nonce'], 'tikibar_reservation_status_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// Capacité vérifiée : seul un utilisateur autorisé à éditer CE post peut changer le statut.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( isset( $_POST['tikibar_statut_select'] ) ) {
			$autorises = array_keys( $this->status_labels() );
			$statut = sanitize_text_field( $_POST['tikibar_statut_select'] );
			if ( in_array( $statut, $autorises, true ) ) {
				update_post_meta( $post_id, '_tikibar_statut', $statut );
			}
		}
	}

	/**
	 * On masque la boîte "Éditeur" par défaut : ce CPT n'a pas de contenu
	 * éditorial, toutes les données passent par notre boîte personnalisée.
	 */
	public function hide_unused_boxes() {
		global $post_type;
		if ( 'reservation' === $post_type ) {
			echo '<style>#postdivrich{display:none;}</style>';
		}
	}
}
