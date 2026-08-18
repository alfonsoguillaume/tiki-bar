/**
 * Modale d'avertissement légal : affichée au chargement de l'accueil si le
 * visiteur ne l'a pas déjà fermée pendant cette visite (sessionStorage =
 * mémorisé tant que l'onglet/navigateur reste ouvert, oublié à la fermeture).
 */
( function () {
	'use strict';

	var overlay = document.getElementById( 'legal-modal-overlay' );
	var okButton = document.getElementById( 'legal-modal-ok' );

	if ( ! overlay || ! okButton ) {
		return;
	}

	var STORAGE_KEY = 'tikibar_legal_warning_dismissed';

	function openModal() {
		overlay.classList.add( 'is-open' );
		document.body.classList.add( 'tikibar-modal-open' );
		okButton.focus(); // accessibilité : le focus clavier va directement sur le bouton
	}

	function closeModal() {
		overlay.classList.remove( 'is-open' );
		document.body.classList.remove( 'tikibar-modal-open' );
		try {
			sessionStorage.setItem( STORAGE_KEY, '1' );
		} catch ( e ) {
			// Si sessionStorage est bloqué (navigation privée stricte, etc.),
			// on ignore simplement : la modale réapparaîtra à la page suivante,
			// ce n'est pas bloquant pour l'utilisateur.
		}
	}

	var dejaFerme = false;
	try {
		dejaFerme = sessionStorage.getItem( STORAGE_KEY ) === '1';
	} catch ( e ) {
		dejaFerme = false;
	}

	if ( ! dejaFerme ) {
		openModal();
	}

	okButton.addEventListener( 'click', closeModal );

	// Permet aussi de fermer avec la touche Échap.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && overlay.classList.contains( 'is-open' ) ) {
			closeModal();
		}
	} );
} )();
