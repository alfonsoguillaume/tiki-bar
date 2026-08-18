<?php
/**
 * Modale d'avertissement légal (alcool), affichée par-dessus la page d'accueil.
 * Masquée par défaut en CSS ; le JS (assets/js/legal-modal.js) l'affiche au
 * chargement si le visiteur ne l'a pas déjà fermée pendant cette visite.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="legal-modal-overlay" id="legal-modal-overlay">
	<div class="legal-modal" role="dialog" aria-modal="true" aria-labelledby="legal-modal-title">
		<p id="legal-modal-title" class="legal-modal-text">
			<?php esc_html_e( 'L\'abus d\'alcool est dangereux pour la santé. À consommer avec modération.', 'tiki-bar' ); ?>
		</p>
		<button type="button" id="legal-modal-ok" class="btn btn-ember">
			<?php esc_html_e( 'OK, j\'ai compris', 'tiki-bar' ); ?>
		</button>
	</div>
</div>
