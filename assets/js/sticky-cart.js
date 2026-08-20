( function ( $ ) {
	'use strict';

	$( function () {
		var $bar = $( '#wwcs-sticky-bar' );
		if ( ! $bar.length ) {
			return;
		}

		var $trigger = $( 'form.cart .single_add_to_cart_button' ).first();
		if ( ! $trigger.length ) {
			$trigger = $( 'form.cart' ).first();
		}

		function toggleBar() {
			if ( ! $trigger.length ) {
				return;
			}
			var triggerBottom = $trigger.offset().top + $trigger.outerHeight();
			if ( $( window ).scrollTop() > triggerBottom ) {
				$bar.addClass( 'wwcs-visible' );
			} else {
				$bar.removeClass( 'wwcs-visible' );
			}
		}

		$( window ).on( 'scroll resize', toggleBar );
		toggleBar();

		// Variable product: scroll back up to the option selectors.
		$bar.on( 'click', '.wwcs-sticky-scroll', function ( e ) {
			e.preventDefault();
			if ( $( 'form.cart' ).length ) {
				$( 'html, body' ).animate( { scrollTop: $( 'form.cart' ).offset().top - 100 }, 400 );
			}
		} );

		// Simple product: trigger the real Add to Cart button.
		$bar.on( 'click', '.wwcs-sticky-add-to-cart', function ( e ) {
			e.preventDefault();
			$( 'form.cart .single_add_to_cart_button' ).trigger( 'click' );
		} );
	} );
} )( jQuery );
