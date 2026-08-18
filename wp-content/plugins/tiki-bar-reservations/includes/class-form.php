<?php
/**
 * Formulaire de réservation (front) + traitement sécurisé côté serveur.
 *
 * Rappel de sécurité (à savoir par cœur pour la soutenance) :
 * on NE FAIT JAMAIS confiance à des données venant d'un formulaire, même si
 * des attributs HTML comme "required" ou "type=email" sont présents : ils ne
 * protègent que l'utilisateur normal, pas quelqu'un qui envoie une requête
 * directement (curl, Postman...). La vraie sécurité est TOUJOURS côté serveur.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TikiBar_Reservation_Form {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// admin-post.php est le point d'entrée natif WordPress pour traiter
		// un formulaire public sans passer par admin-ajax : il gère à la fois
		// les visiteurs connectés (admin_post_{action}) et non connectés
		// (admin_post_nopriv_{action}) — on branche les deux sur le même traitement.
		add_action( 'admin_post_tikibar_submit_reservation', array( $this, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_tikibar_submit_reservation', array( $this, 'handle_submission' ) );

		// Bonus : un shortcode pour pouvoir aussi insérer le formulaire dans
		// une page classique via [tiki_reservation soiree_id="12"].
		add_shortcode( 'tiki_reservation', array( $this, 'shortcode' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_style(
			'tikibar-reservation-form',
			TIKIBAR_RESA_URL . 'public/css/reservation-form.css',
			array(),
			filemtime( TIKIBAR_RESA_PATH . 'public/css/reservation-form.css' )
		);
	}

	public function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'soiree_id' => get_the_ID() ), $atts );
		ob_start();
		$this->render( (int) $atts['soiree_id'] );
		return ob_get_clean();
	}

	/**
	 * Affiche le formulaire. Appelé directement depuis single-soiree.php du thème,
	 * ou via le shortcode ci-dessus.
	 */
	public function render( $soiree_id ) {

		// On affiche un message de retour si on vient d'être redirigé après soumission.
		if ( isset( $_GET['reservation'] ) ) {
			if ( $_GET['reservation'] === 'success' ) {
				echo '<p class="tikibar-form-notice tikibar-form-success">' .
					esc_html__( 'Votre demande a bien été envoyée. Vous recevrez une réponse par e-mail.', 'tiki-bar-reservations' ) .
					'</p>';
				return; // on ne réaffiche pas le formulaire après un succès
			} else {
				echo '<p class="tikibar-form-notice tikibar-form-error">' .
					esc_html__( 'Votre demande n\'a pas pu être envoyée. Vérifiez les champs et réessayez.', 'tiki-bar-reservations' ) .
					'</p>';
			}
		}
		?>
		<form class="tikibar-reservation-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="tikibar_submit_reservation">
			<input type="hidden" name="soiree_id" value="<?php echo esc_attr( $soiree_id ); ?>">
			<?php wp_nonce_field( 'tikibar_reservation_' . $soiree_id, 'tikibar_reservation_nonce' ); ?>

			<!-- Honeypot anti-spam : champ invisible pour un humain, mais que les
			     robots remplissent souvent automatiquement. On le vérifie côté serveur. -->
			<div style="position:absolute; left:-9999px;" aria-hidden="true">
				<label for="tikibar_website">Laisser vide</label>
				<input type="text" id="tikibar_website" name="tikibar_website" tabindex="-1" autocomplete="off">
			</div>

			<div class="form-row">
				<label for="tikibar_prenom"><?php esc_html_e( 'Prénom', 'tiki-bar-reservations' ); ?> *</label>
				<input type="text" id="tikibar_prenom" name="tikibar_prenom" required>
			</div>

			<div class="form-row">
				<label for="tikibar_nom"><?php esc_html_e( 'Nom', 'tiki-bar-reservations' ); ?> *</label>
				<input type="text" id="tikibar_nom" name="tikibar_nom" required>
			</div>

			<div class="form-row">
				<label for="tikibar_email"><?php esc_html_e( 'E-mail', 'tiki-bar-reservations' ); ?> *</label>
				<input type="email" id="tikibar_email" name="tikibar_email" required>
			</div>

			<div class="form-row">
				<label for="tikibar_telephone"><?php esc_html_e( 'Téléphone', 'tiki-bar-reservations' ); ?></label>
				<input type="tel" id="tikibar_telephone" name="tikibar_telephone">
			</div>

			<div class="form-row">
				<label for="tikibar_participants"><?php esc_html_e( 'Nombre de participants', 'tiki-bar-reservations' ); ?> *</label>
				<input type="number" id="tikibar_participants" name="tikibar_participants" min="1" max="20" value="1" required>
			</div>

			<div class="form-row">
				<label for="tikibar_commentaire"><?php esc_html_e( 'Commentaire (allergies, questions...)', 'tiki-bar-reservations' ); ?></label>
				<textarea id="tikibar_commentaire" name="tikibar_commentaire" rows="3"></textarea>
			</div>

			<button type="submit" class="btn btn-ember"><?php esc_html_e( 'Envoyer la demande', 'tiki-bar-reservations' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Traitement de la soumission. C'est ICI que se joue toute la sécurité.
	 */
	public function handle_submission() {

		$soiree_id = isset( $_POST['soiree_id'] ) ? (int) $_POST['soiree_id'] : 0;

		// 1. NONCE : on vérifie que la requête vient bien du formulaire affiché
		// par WordPress, et pas d'un site tiers (protection CSRF).
		if (
			! isset( $_POST['tikibar_reservation_nonce'] ) ||
			! wp_verify_nonce( $_POST['tikibar_reservation_nonce'], 'tikibar_reservation_' . $soiree_id )
		) {
			$this->redirect_with_status( $soiree_id, 'error' );
		}

		// 2. HONEYPOT : si ce champ caché est rempli, c'est très probablement un bot.
		// On fait semblant que ça a marché pour ne pas l'aider à s'améliorer.
		if ( ! empty( $_POST['tikibar_website'] ) ) {
			$this->redirect_with_status( $soiree_id, 'success' );
		}

		// 3. La soirée référencée doit exister et être bien du bon type de contenu.
		if ( ! $soiree_id || get_post_type( $soiree_id ) !== 'soiree' ) {
			$this->redirect_with_status( $soiree_id, 'error' );
		}

		// 4. SANITIZATION : chaque champ est nettoyé selon sa nature.
		// - sanitize_text_field() : retire les balises HTML, espaces superflus
		// - sanitize_email() : format email
		// - absint() : force un entier positif
		$prenom       = sanitize_text_field( wp_unslash( $_POST['tikibar_prenom'] ?? '' ) );
		$nom          = sanitize_text_field( wp_unslash( $_POST['tikibar_nom'] ?? '' ) );
		$email        = sanitize_email( wp_unslash( $_POST['tikibar_email'] ?? '' ) );
		$telephone    = sanitize_text_field( wp_unslash( $_POST['tikibar_telephone'] ?? '' ) );
		$participants = absint( $_POST['tikibar_participants'] ?? 0 );
		$commentaire  = sanitize_textarea_field( wp_unslash( $_POST['tikibar_commentaire'] ?? '' ) );

		// 5. VALIDATION : on vérifie que les données nettoyées ont un sens métier.
		// is_email() vérifie le FORMAT, pas que l'adresse existe réellement.
		if (
			empty( $prenom ) || empty( $nom ) ||
			empty( $email ) || ! is_email( $email ) ||
			$participants < 1 || $participants > 20
		) {
			$this->redirect_with_status( $soiree_id, 'error' );
		}

		// 6. Création de la réservation. wp_insert_post() échappe déjà en interne
		// ce qui va dans la base, mais on lui donne des données déjà nettoyées.
		$reservation_id = wp_insert_post( array(
			'post_type'   => 'reservation',
			'post_status' => 'publish',
			'post_title'  => sprintf( '%s %s — %s', $prenom, $nom, get_the_title( $soiree_id ) ),
		) );

		if ( ! $reservation_id || is_wp_error( $reservation_id ) ) {
			$this->redirect_with_status( $soiree_id, 'error' );
		}

		// 7. Enregistrement des champs en post meta.
		update_post_meta( $reservation_id, '_tikibar_soiree_id', $soiree_id );
		update_post_meta( $reservation_id, '_tikibar_prenom', $prenom );
		update_post_meta( $reservation_id, '_tikibar_nom', $nom );
		update_post_meta( $reservation_id, '_tikibar_email', $email );
		update_post_meta( $reservation_id, '_tikibar_telephone', $telephone );
		update_post_meta( $reservation_id, '_tikibar_participants', $participants );
		update_post_meta( $reservation_id, '_tikibar_commentaire', $commentaire );
		update_post_meta( $reservation_id, '_tikibar_statut', 'en_attente' );

		/**
		 * Hook custom : permet à d'autres bouts de code (ex : un futur envoi
		 * d'e-mail de confirmation) de se brancher sans modifier ce fichier.
		 */
		do_action( 'tikibar_reservation_created', $reservation_id, $soiree_id );

		$this->redirect_with_status( $soiree_id, 'success' );
	}

	private function redirect_with_status( $soiree_id, $status ) {
		$url = $soiree_id ? get_permalink( $soiree_id ) : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'reservation', $status, $url ) );
		exit;
	}
}
