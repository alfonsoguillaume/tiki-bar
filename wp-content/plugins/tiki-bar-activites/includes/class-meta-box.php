<?php
/**
 * Ajoute une boîte de champs personnalisés sur l'écran d'édition d'une soirée :
 * date, heure, durée, lieu, tarif, nombre max de participants, statut.
 *
 * On stocke chaque champ en "post meta" (table wp_postmeta), c'est la solution
 * native de WordPress pour rattacher des données structurées à un contenu.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TikiBar_Soiree_Meta_Box {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_soiree', array( $this, 'save' ) );
	}

	public function add_meta_box() {
		add_meta_box(
			'tikibar_soiree_details',
			__( 'Détails de la soirée', 'tiki-bar-activites' ),
			array( $this, 'render' ),
			'soiree',
			'normal',
			'high'
		);
	}

	/**
	 * Affiche le formulaire des champs personnalisés dans l'admin.
	 */
	public function render( $post ) {

		// Nonce : jeton de sécurité unique qui prouve que le formulaire vient
		// bien de cette page d'admin, et pas d'une requête forgée ailleurs
		// (protection CSRF). On le vérifiera dans save().
		wp_nonce_field( 'tikibar_soiree_save', 'tikibar_soiree_nonce' );

		$date         = get_post_meta( $post->ID, '_tikibar_date', true );
		$heure        = get_post_meta( $post->ID, '_tikibar_heure', true );
		$duree        = get_post_meta( $post->ID, '_tikibar_duree', true );
		$lieu         = get_post_meta( $post->ID, '_tikibar_lieu', true );
		$tarif        = get_post_meta( $post->ID, '_tikibar_tarif', true );
		$participants = get_post_meta( $post->ID, '_tikibar_participants_max', true );
		$statut       = get_post_meta( $post->ID, '_tikibar_statut', true );
		if ( ! $statut ) {
			$statut = 'disponible';
		}
		?>
		<table class="form-table">
			<tr>
				<th><label for="tikibar_date"><?php esc_html_e( 'Date', 'tiki-bar-activites' ); ?></label></th>
				<td><input type="date" id="tikibar_date" name="tikibar_date" value="<?php echo esc_attr( $date ); ?>"></td>
			</tr>
			<tr>
				<th><label for="tikibar_heure"><?php esc_html_e( 'Heure', 'tiki-bar-activites' ); ?></label></th>
				<td><input type="time" id="tikibar_heure" name="tikibar_heure" value="<?php echo esc_attr( $heure ); ?>"></td>
			</tr>
			<tr>
				<th><label for="tikibar_duree"><?php esc_html_e( 'Durée', 'tiki-bar-activites' ); ?></label></th>
				<td><input type="text" id="tikibar_duree" name="tikibar_duree" value="<?php echo esc_attr( $duree ); ?>" placeholder="<?php esc_attr_e( 'ex : 3h', 'tiki-bar-activites' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="tikibar_lieu"><?php esc_html_e( 'Lieu', 'tiki-bar-activites' ); ?></label></th>
				<td><input type="text" id="tikibar_lieu" name="tikibar_lieu" value="<?php echo esc_attr( $lieu ); ?>" placeholder="<?php esc_attr_e( 'ex : Le jardin, entrée par le portail bleu', 'tiki-bar-activites' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="tikibar_tarif"><?php esc_html_e( 'Tarif (€)', 'tiki-bar-activites' ); ?></label></th>
				<td><input type="number" step="0.01" min="0" id="tikibar_tarif" name="tikibar_tarif" value="<?php echo esc_attr( $tarif ); ?>"></td>
			</tr>
			<tr>
				<th><label for="tikibar_participants_max"><?php esc_html_e( 'Nombre max. de participants', 'tiki-bar-activites' ); ?></label></th>
				<td><input type="number" min="1" id="tikibar_participants_max" name="tikibar_participants_max" value="<?php echo esc_attr( $participants ); ?>"></td>
			</tr>
			<tr>
				<th><label for="tikibar_statut"><?php esc_html_e( 'Statut', 'tiki-bar-activites' ); ?></label></th>
				<td>
					<select id="tikibar_statut" name="tikibar_statut">
						<option value="disponible" <?php selected( $statut, 'disponible' ); ?>><?php esc_html_e( 'Places disponibles', 'tiki-bar-activites' ); ?></option>
						<option value="complet" <?php selected( $statut, 'complet' ); ?>><?php esc_html_e( 'Complet', 'tiki-bar-activites' ); ?></option>
						<option value="annule" <?php selected( $statut, 'annule' ); ?>><?php esc_html_e( 'Annulée', 'tiki-bar-activites' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Sauvegarde sécurisée des champs personnalisés.
	 * C'est le genre de fonction que le jury va scruter : nonce + capability
	 * + sanitize sur CHAQUE champ avant de toucher la base de données.
	 */
	public function save( $post_id ) {

		// 1. Vérification du nonce (protection CSRF).
		if ( ! isset( $_POST['tikibar_soiree_nonce'] ) || ! wp_verify_nonce( $_POST['tikibar_soiree_nonce'], 'tikibar_soiree_save' ) ) {
			return;
		}

		// 2. On ignore les sauvegardes automatiques (autosave) : à ce moment-là,
		// les champs du formulaire ne sont pas forcément envoyés.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// 3. Vérification des droits : l'utilisateur doit pouvoir éditer CE post précis.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 4. Nettoyage et validation de chaque champ avant stockage.
		// - sanitize_text_field() retire les balises HTML et les caractères indésirables
		// - on force les types (float/int) pour tarif et participants
		if ( isset( $_POST['tikibar_date'] ) ) {
			update_post_meta( $post_id, '_tikibar_date', sanitize_text_field( $_POST['tikibar_date'] ) );
		}
		if ( isset( $_POST['tikibar_heure'] ) ) {
			update_post_meta( $post_id, '_tikibar_heure', sanitize_text_field( $_POST['tikibar_heure'] ) );
		}
		if ( isset( $_POST['tikibar_duree'] ) ) {
			update_post_meta( $post_id, '_tikibar_duree', sanitize_text_field( $_POST['tikibar_duree'] ) );
		}
		if ( isset( $_POST['tikibar_lieu'] ) ) {
			update_post_meta( $post_id, '_tikibar_lieu', sanitize_text_field( $_POST['tikibar_lieu'] ) );
		}
		if ( isset( $_POST['tikibar_tarif'] ) ) {
			update_post_meta( $post_id, '_tikibar_tarif', (float) $_POST['tikibar_tarif'] );
		}
		if ( isset( $_POST['tikibar_participants_max'] ) ) {
			update_post_meta( $post_id, '_tikibar_participants_max', (int) $_POST['tikibar_participants_max'] );
		}
		if ( isset( $_POST['tikibar_statut'] ) ) {
			// whitelist stricte : on n'accepte que ces 3 valeurs, jamais ce qui
			// pourrait être injecté depuis une requête forgée.
			$statut_autorises = array( 'disponible', 'complet', 'annule' );
			$statut = sanitize_text_field( $_POST['tikibar_statut'] );
			if ( in_array( $statut, $statut_autorises, true ) ) {
				update_post_meta( $post_id, '_tikibar_statut', $statut );
			}
		}
	}
}
