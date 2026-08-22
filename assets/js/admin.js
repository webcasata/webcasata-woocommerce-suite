( function ( $ ) {
	'use strict';

	// The settings/appearance panel (if a feature has one) is collapsed by
	// default regardless of the feature's own on/off state — the on/off
	// toggle and "view its settings" are two independent actions, so a
	// feature can be on with its panel closed, or off with its panel open
	// to configure it before switching it on.
	$( document ).on( 'click', '.wwcs-feature-expand', function () {
		var $btn     = $( this );
		var $group   = $btn.closest( '.wwcs-feature-group' );
		var $panel   = $group.find( '.wwcs-feature-fields' );
		var expanded = 'true' === $btn.attr( 'aria-expanded' );

		if ( ! $panel.length ) {
			return;
		}

		$btn.attr( 'aria-expanded', expanded ? 'false' : 'true' );
		$btn.toggleClass( 'wwcs-feature-expand-open', ! expanded );
		$group.toggleClass( 'wwcs-feature-group-open', ! expanded );
		$panel.slideToggle( 200 );
	} );
} )( jQuery );
