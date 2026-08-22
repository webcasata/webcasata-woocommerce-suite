( function ( $ ) {
	'use strict';

	function toggleWishlist( productId, callback ) {
		$.post( wwcsWishlist.ajax_url, {
			action: 'wwcs_toggle_wishlist',
			nonce: wwcsWishlist.nonce,
			product_id: productId
		} ).done( function ( response ) {
			if ( typeof callback === 'function' ) {
				callback( response );
			}
		} ).fail( function () {
			if ( typeof callback === 'function' ) {
				callback( null );
			}
		} );
	}

	function showToast( message ) {
		var html = message;
		if ( wwcsWishlist.wishlistUrl ) {
			html += ' <a href="' + wwcsWishlist.wishlistUrl + '">' + wwcsWishlist.i18n.viewWishlist + '</a>';
		}

		var $toast = $( '<div class="wwcs-wishlist-toast">' + html + '</div>' );
		$( 'body' ).append( $toast );

		// Next tick, so the transition actually animates in instead of
		// starting already-visible.
		window.setTimeout( function () {
			$toast.addClass( 'wwcs-wishlist-toast-visible' );
		}, 10 );

		window.setTimeout( function () {
			$toast.removeClass( 'wwcs-wishlist-toast-visible' );
			window.setTimeout( function () {
				$toast.remove();
			}, 300 );
		}, 3000 );
	}

	// Heart icon toggle — shared by the product card icon and the single
	// product page button, they're both just ".wwcs-wishlist-btn".
	$( document ).on( 'click', '.wwcs-wishlist-btn', function ( e ) {
		e.preventDefault();

		var $btn = $( this );
		if ( $btn.hasClass( 'wwcs-wishlist-loading' ) ) {
			return;
		}

		var productId = $btn.data( 'product_id' );
		$btn.addClass( 'wwcs-wishlist-loading' );

		toggleWishlist( productId, function ( response ) {
			$btn.removeClass( 'wwcs-wishlist-loading' );

			if ( ! response || ! response.success ) {
				showToast( wwcsWishlist.i18n.error );
				return;
			}

			var inWishlist = response.data.in_wishlist;
			$btn
				.toggleClass( 'wwcs-wishlist-active', inWishlist )
				.attr( 'aria-pressed', inWishlist ? 'true' : 'false' );

			var $label = $btn.find( '.wwcs-wishlist-label' );
			if ( $label.length ) {
				$label.text( inWishlist ? wwcsWishlist.i18n.inWishlist : wwcsWishlist.i18n.addToWishlist );
			}

			showToast( inWishlist ? wwcsWishlist.i18n.added : wwcsWishlist.i18n.removed );
		} );
	} );

	// Remove button on the wishlist page itself — always removes (rather
	// than toggling), then fades the row out.
	$( document ).on( 'click', '.wwcs-wishlist-remove', function ( e ) {
		e.preventDefault();

		var $btn  = $( this );
		var $item = $btn.closest( '.wwcs-wishlist-item' );
		var productId = $btn.data( 'product_id' );

		if ( $btn.hasClass( 'wwcs-wishlist-loading' ) ) {
			return;
		}
		$btn.addClass( 'wwcs-wishlist-loading' );

		toggleWishlist( productId, function ( response ) {
			if ( ! response || ! response.success ) {
				$btn.removeClass( 'wwcs-wishlist-loading' );
				showToast( wwcsWishlist.i18n.error );
				return;
			}

			$item.fadeOut( 200, function () {
				$item.remove();
				if ( ! $( '.wwcs-wishlist-item' ).length ) {
					window.location.reload(); // Simplest way to show the empty-state message.
				}
			} );
		} );
	} );
} )( jQuery );
