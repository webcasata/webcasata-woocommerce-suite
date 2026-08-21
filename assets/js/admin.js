( function ( $ ) {
	'use strict';

	function syncFields( $row ) {
		var checked = $row.find( '.wwcs-switch input[type="checkbox"]' ).is( ':checked' );
		$row.find( '.wwcs-feature-fields' ).toggleClass( 'wwcs-fields-disabled', ! checked );
		$row.find( '.wwcs-feature-fields input' ).prop( 'disabled', ! checked );
	}

	$( function () {
		$( '.wwcs-feature-row' ).each( function () {
			syncFields( $( this ) );
		} );

		$( document ).on( 'change', '.wwcs-feature-row .wwcs-switch input[type="checkbox"]', function () {
			syncFields( $( this ).closest( '.wwcs-feature-row' ) );
		} );
	} );
} )( jQuery );
