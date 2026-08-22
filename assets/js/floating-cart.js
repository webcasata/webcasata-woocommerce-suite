( function ( $ ) {
	'use strict';

	function setOpen( show ) {
		var $fc = $( '#wwcs-floating-cart' );
		if ( ! $fc.length ) {
			return;
		}
		if ( undefined === show ) {
			$fc.toggleClass( 'wwcs-fc-open' );
		} else if ( show ) {
			$fc.addClass( 'wwcs-fc-open' );
		} else {
			$fc.removeClass( 'wwcs-fc-open' );
		}
	}

	function applyFragments( fragments ) {
		if ( ! fragments ) {
			return;
		}
		$.each( fragments, function ( selector, html ) {
			$( selector ).replaceWith( html );
		} );
	}

	$( document ).on( 'click', '.wwcs-floating-cart-toggle', function ( e ) {
		e.preventDefault();
		setOpen();
	} );

	$( document ).on( 'click', '.wwcs-floating-cart-close, .wwcs-floating-cart-overlay, .wwcs-fc-continue', function ( e ) {
		e.preventDefault();
		setOpen( false );
	} );

	$( document ).on( 'keyup', function ( e ) {
		if ( 27 === e.keyCode ) {
			setOpen( false );
		}
	} );

	// Quantity +/- stepper — the one part of the drawer not covered by
	// WooCommerce's own mini-cart AJAX, so it gets its own small endpoint.
	$( document ).on( 'click', '.wwcs-fc-qty-plus, .wwcs-fc-qty-minus', function ( e ) {
		e.preventDefault();

		var $btn      = $( this );
		var $stepper  = $btn.closest( '.wwcs-fc-qty-stepper' );
		var current   = parseInt( $stepper.find( '.wwcs-fc-qty-value' ).text(), 10 ) || 1;
		var next      = $btn.hasClass( 'wwcs-fc-qty-plus' ) ? current + 1 : current - 1;
		var itemKey   = $stepper.data( 'cart_item_key' );

		if ( next < 0 || $stepper.hasClass( 'wwcs-fc-qty-loading' ) ) {
			return;
		}

		$stepper.addClass( 'wwcs-fc-qty-loading' );

		$.post( wwcsFloatingCart.ajax_url, {
			action: 'wwcs_update_cart_item_qty',
			nonce: wwcsFloatingCart.nonce,
			cart_item_key: itemKey,
			quantity: next
		} ).done( function ( response ) {
			if ( response && response.success && response.data ) {
				applyFragments( response.data.fragments );
			}
		} ).always( function () {
			// The stepper itself may no longer exist post-replacement — harmless if so.
			$stepper.removeClass( 'wwcs-fc-qty-loading' );
		} );
	} );

	// Cross-sell strip: click a dot to scroll to that card.
	$( document ).on( 'click', '.wwcs-fc-dot', function () {
		var index   = $( this ).data( 'index' );
		var $track  = $( this ).closest( '.wwcs-floating-cart-content' ).find( '.wwcs-fc-crosssell-track' );
		var $target = $track.children().eq( index );

		if ( $target.length ) {
			$track.animate( { scrollLeft: $target.position().left + $track.scrollLeft() }, 200 );
		}

		$( this ).addClass( 'wwcs-fc-dot-active' ).siblings().removeClass( 'wwcs-fc-dot-active' );
	} );

	if ( window.wwcsFloatingCart && wwcsFloatingCart.autoOpen ) {
		$( document.body ).on( 'added_to_cart', function () {
			setOpen( true );
		} );
	}
} )( jQuery );
