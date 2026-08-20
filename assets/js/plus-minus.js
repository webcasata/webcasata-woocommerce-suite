( function ( $ ) {
	'use strict';

	function wrapQty( $input ) {
		if ( $input.parent().hasClass( 'wwcs-qty-wrap' ) ) {
			return;
		}
		$input.wrap( '<div class="wwcs-qty-wrap"></div>' );
		$input.before( '<button type="button" class="wwcs-qty-btn wwcs-qty-minus">&minus;</button>' );
		$input.after( '<button type="button" class="wwcs-qty-btn wwcs-qty-plus">+</button>' );
	}

	function step( $input, direction ) {
		var min  = parseFloat( $input.attr( 'min' ) );
		var max  = parseFloat( $input.attr( 'max' ) );
		var stepVal = parseFloat( $input.attr( 'step' ) ) || 1;
		var val  = parseFloat( $input.val() );

		if ( isNaN( min ) ) { min = 0; }
		if ( isNaN( val ) ) { val = min; }

		val = 'up' === direction ? val + stepVal : val - stepVal;

		if ( val < min ) { val = min; }
		if ( ! isNaN( max ) && val > max ) { val = max; }

		$input.val( val ).trigger( 'change' );
	}

	function init() {
		$( '.quantity input.qty' ).each( function () {
			wrapQty( $( this ) );
		} );
	}

	$( document ).on( 'click', '.wwcs-qty-plus', function () {
		step( $( this ).siblings( 'input.qty' ), 'up' );
	} );

	$( document ).on( 'click', '.wwcs-qty-minus', function () {
		step( $( this ).siblings( 'input.qty' ), 'down' );
	} );

	// Re-run whenever WooCommerce swaps in new markup (variation change, cart AJAX update, etc).
	$( document.body ).on( 'wc_fragments_refreshed updated_wc_div updated_cart_totals found_variation reset_data', init );
	$( init );
} )( jQuery );
