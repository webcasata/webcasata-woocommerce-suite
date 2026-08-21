( function ( $ ) {
	'use strict';

	var $modal = null;

	function ensureModal() {
		if ( $modal && $modal.length ) {
			return $modal;
		}

		$modal = $(
			'<div class="wwcs-qv-overlay" id="wwcs-qv-overlay">' +
				'<div class="wwcs-qv-modal" role="dialog" aria-modal="true">' +
					'<button type="button" class="wwcs-qv-close" aria-label="Close">&times;</button>' +
					'<div class="wwcs-qv-body"></div>' +
				'</div>' +
			'</div>'
		);
		$( 'body' ).append( $modal );
		return $modal;
	}

	function openModal( productId ) {
		var $m = ensureModal();

		$m.find( '.wwcs-qv-body' ).html( '<div class="wwcs-qv-loading">' + wwcsQuickView.i18n.loading + '</div>' );
		$m.addClass( 'wwcs-qv-open' );
		$( 'body' ).addClass( 'wwcs-qv-lock-scroll' );

		$.post( wwcsQuickView.ajax_url, {
			action: 'wwcs_quick_view',
			nonce: wwcsQuickView.nonce,
			product_id: productId
		} ).done( function ( response ) {
			if ( response && response.success ) {
				$m.find( '.wwcs-qv-body' ).html( response.data.html );

				// Let any other active module (e.g. Plus/Minus buttons) pick
				// up the newly-injected quantity field — this is a standard
				// WooCommerce event several core/plugin scripts already listen for.
				$( document.body ).trigger( 'updated_wc_div' );

				// If this product is variable, the form just injected is
				// WooCommerce's own .variations_form markup. The variation
				// script's own document-ready init ran before this HTML
				// existed, so we re-run its init directly on this instance —
				// the same technique WooCommerce's own AJAX-loaded content
				// (e.g. the Store API legacy blocks) uses.
				var $variationsForm = $m.find( '.variations_form' );
				if ( $variationsForm.length && $.fn.wc_variation_form ) {
					$variationsForm.each( function () {
						$( this ).wc_variation_form();
						$( this ).find( '.variations select' ).trigger( 'change' );
					} );
				}
			} else {
				$m.find( '.wwcs-qv-body' ).html( '<p class="wwcs-qv-error">' + wwcsQuickView.i18n.error + '</p>' );
			}
		} ).fail( function () {
			$m.find( '.wwcs-qv-body' ).html( '<p class="wwcs-qv-error">' + wwcsQuickView.i18n.error + '</p>' );
		} );
	}

	function closeModal() {
		if ( ! $modal ) {
			return;
		}
		$modal.removeClass( 'wwcs-qv-open' );
		$( 'body' ).removeClass( 'wwcs-qv-lock-scroll' );
	}

	$( document ).on( 'click', '.wwcs-quick-view-btn', function ( e ) {
		e.preventDefault();
		openModal( $( this ).data( 'product-id' ) );
	} );

	$( document ).on( 'click', '.wwcs-qv-close, .wwcs-qv-overlay', function ( e ) {
		if ( e.target === this ) {
			closeModal();
		}
	} );

	$( document ).on( 'keyup', function ( e ) {
		if ( 27 === e.keyCode ) {
			closeModal();
		}
	} );

	// AJAX add-to-cart for both the simple-product form and WooCommerce's
	// own variation form, both rendered inside the modal.
	$( document ).on( 'submit', '.wwcs-qv-summary form.cart', function ( e ) {
		e.preventDefault();

		var $form    = $( this );
		var $button  = $form.find( '.single_add_to_cart_button, .wwcs-qv-add-to-cart' );
		var $message = $form.closest( '.wwcs-qv-summary' ).find( '.wwcs-qv-message' );

		// Variation form: WooCommerce's own script disables/marks this
		// button until a complete, purchasable combination is selected.
		if ( $button.prop( 'disabled' ) || $button.hasClass( 'disabled' ) ) {
			return;
		}

		if ( ! window.wc_add_to_cart_params ) {
			$message.addClass( 'wwcs-qv-error' ).text( wwcsQuickView.i18n.error );
			return;
		}

		// Serialize the whole form so a variation form's variation_id and
		// attribute_xxx fields (set by wc-add-to-cart-variation.js as the
		// shopper picks options) are included, not just product_id/quantity.
		var payload = {};
		$.each( $form.serializeArray(), function ( _, field ) {
			payload[ field.name ] = field.value;
		} );
		if ( ! payload.quantity ) {
			payload.quantity = 1;
		}
		if ( ! payload.product_id ) {
			payload.product_id = $form.data( 'product_id' );
		}

		$button.prop( 'disabled', true ).addClass( 'wwcs-qv-btn-loading' );
		$message.removeClass( 'wwcs-qv-error' ).text( '' );

		$.ajax( {
			type: 'POST',
			url: wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' ),
			data: payload
		} ).done( function ( response ) {
			if ( ! response || response.error ) {
				$message.addClass( 'wwcs-qv-error' ).text( wwcsQuickView.i18n.error );
				return;
			}
			$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $button ] );
			$message.text( wwcsQuickView.i18n.added );
		} ).fail( function () {
			$message.addClass( 'wwcs-qv-error' ).text( wwcsQuickView.i18n.error );
		} ).always( function () {
			$button.prop( 'disabled', false ).removeClass( 'wwcs-qv-btn-loading' );
		} );
	} );
} )( jQuery );
