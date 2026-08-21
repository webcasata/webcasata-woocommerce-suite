( function ( $ ) {
	'use strict';

	// Accordion behavior: each feature's config/appearance panel (if it has
	// one) lives in the same .wwcs-feature-group as its toggle. The panel's
	// initial open/closed state is rendered server-side (see
	// class-wwcs-admin-page.php) to avoid a flash of the wrong state before
	// JS runs; from here on, JS just animates the transition when the
	// toggle changes.
	$( document ).on( 'change', '.wwcs-feature-row .wwcs-switch input[type="checkbox"]', function () {
		var $panel = $( this ).closest( '.wwcs-feature-group' ).find( '.wwcs-feature-fields' );
		if ( ! $panel.length ) {
			return;
		}

		if ( $( this ).is( ':checked' ) ) {
			$panel.slideDown( 200 );
		} else {
			$panel.slideUp( 200 );
		}
	} );
} )( jQuery );
