( function ( $ ) {
	'use strict';

	function openModal( tab ) {
		$( '#wwcs-login-popup' ).addClass( 'wwcs-lp-open' );
		$( 'body' ).addClass( 'wwcs-lp-lock-scroll' );
		if ( tab ) {
			switchTab( tab );
		}
	}

	function closeModal() {
		$( '#wwcs-login-popup' ).removeClass( 'wwcs-lp-open' );
		$( 'body' ).removeClass( 'wwcs-lp-lock-scroll' );
	}

	function switchTab( tab ) {
		var $popup = $( '#wwcs-login-popup' );
		var index  = 'register' === tab ? 1 : 0;

		$popup.find( '.wwcs-lp-tab' ).removeClass( 'wwcs-lp-tab-active' );
		$popup.find( '.wwcs-lp-tab[data-tab="' + tab + '"]' ).addClass( 'wwcs-lp-tab-active' );

		var $panes = $popup.find( '.wwcs-lp-pane' );
		$panes.removeClass( 'wwcs-lp-pane-active' );
		$panes.eq( index ).addClass( 'wwcs-lp-pane-active' );
	}

	function isMyAccountLink( $link ) {
		var href   = ( $link.attr( 'href' ) || '' ).replace( /\/$/, '' );
		var target = ( window.wwcsLoginPopup ? wwcsLoginPopup.myAccountUrl : '' ) || '';
		target     = target.replace( /\/$/, '' );
		return !! target && href === target;
	}

	function triggerSelector() {
		var classes = ( window.wwcsLoginPopup && wwcsLoginPopup.triggerClasses ) || [ 'wwcs-login-trigger' ];
		if ( ! classes.length ) {
			return null;
		}
		return classes.map( function ( c ) { return '.' + c; } ).join( ', ' );
	}

	// Intercept clicks on whatever link the active theme already uses for
	// "My Account" (header icon, menu item, widget...) rather than
	// requiring a new dedicated trigger element.
	$( document ).on( 'click', 'a[href]', function ( e ) {
		if ( isMyAccountLink( $( this ) ) ) {
			e.preventDefault();
			openModal( 'login' );
		}
	} );

	// Also open on any element (link, button, image, plain text...)
	// carrying one of the configured trigger classes — this is the
	// general-purpose trigger for buttons that aren't the theme's My
	// Account link. An optional data-tab="register" attribute opens
	// straight to the Register tab.
	var selector = triggerSelector();
	if ( selector ) {
		$( document ).on( 'click', selector, function ( e ) {
			e.preventDefault();
			openModal( $( this ).data( 'tab' ) || 'login' );
		} );
	}

	$( document ).on( 'click', '.wwcs-lp-tab', function () {
		switchTab( $( this ).data( 'tab' ) );
	} );

	$( document ).on( 'click', '.wwcs-lp-close, .wwcs-lp-overlay', function ( e ) {
		if ( e.target === this ) {
			closeModal();
		}
	} );

	$( document ).on( 'keyup', function ( e ) {
		if ( 27 === e.keyCode ) {
			closeModal();
		}
	} );

	$( function () {
		if ( window.wwcsLoginPopup && wwcsLoginPopup.autoOpenTab ) {
			openModal( wwcsLoginPopup.autoOpenTab );
		}
	} );
} )( jQuery );
