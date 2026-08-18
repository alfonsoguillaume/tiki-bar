/**
 * Menu mobile : ouverture/fermeture accessible au clavier.
 * Vanilla JS, aucune dépendance (on évite le poids de jQuery pour ça).
 */
( function () {
	'use strict';

	var toggle = document.querySelector( '.primary-menu-toggle' );
	var menu = document.querySelector( '.primary-navigation' );

	if ( ! toggle || ! menu ) {
		return;
	}

	toggle.addEventListener( 'click', function () {
		var isOpen = menu.classList.toggle( 'is-open' );
		toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
	} );
} )();
