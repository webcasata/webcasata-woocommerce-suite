<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_Plus_Minus {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		// Loaded on product, cart and checkout pages — everywhere WooCommerce
		// might render a quantity input.
		if ( ! ( is_product() || is_cart() || is_checkout() ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-plus-minus', WWCS_URL . 'assets/css/plus-minus.css', array(), WWCS_VERSION );
		wp_enqueue_script( 'wwcs-plus-minus', WWCS_URL . 'assets/js/plus-minus.js', array( 'jquery' ), WWCS_VERSION, true );
	}
}
