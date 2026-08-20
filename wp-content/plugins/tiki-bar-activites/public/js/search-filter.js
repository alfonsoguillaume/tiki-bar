/**
 * Interception du formulaire de filtres : au lieu de recharger la page,
 * on envoie les critères en arrière-plan (AJAX) et on remplace juste les
 * résultats avec la réponse de WordPress.
 */
( function () {
	'use strict';

	var form = document.getElementById( 'tikibar-search-form' );
	var resultsBox = document.getElementById( 'tikibar-search-results' );
	var statusBox = document.getElementById( 'tikibar-search-status' );

	if ( ! form || ! resultsBox || typeof tikibarSearch === 'undefined' ) {
		return;
	}

	function fetchResults() {
		statusBox.textContent = 'Recherche en cours…';

		var formData = new FormData( form );
		formData.append( 'action', 'tikibar_filter_soirees' );
		formData.append( 'nonce', tikibarSearch.nonce );

		fetch( tikibarSearch.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( data.success ) {
					resultsBox.innerHTML = data.data.html;
					statusBox.textContent = data.data.count + ' soirée(s) trouvée(s).';
				} else {
					statusBox.textContent = 'Une erreur est survenue, réessayez.';
				}
			} )
			.catch( function () {
				statusBox.textContent = 'Une erreur est survenue, réessayez.';
			} );
	}

	// Empêche l'envoi classique du formulaire (qui rechargerait la page).
	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		fetchResults();
	} );

	// Bonus confort : filtrer automatiquement dès qu'on change une case,
	// sans attendre que le visiteur clique sur "Filtrer".
	form.querySelectorAll( 'select' ).forEach( function ( select ) {
		select.addEventListener( 'change', fetchResults );
	} );

	// Chargement initial des résultats dès l'arrivée sur la page.
	fetchResults();
} )();
