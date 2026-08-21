<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_OOS_Ribbon {

	public static function init() {
		// Priority 6 — after the New badge (4) and Discount badge (5), before
		// the product thumbnail (10), so the CSS "dim the image" sibling
		// selector below can target images that come after this ribbon in
		// the DOM.
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_ribbon' ), 6 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-oos-ribbon', WWCS_URL . 'assets/css/oos-ribbon.css', array(), WWCS_VERSION );
	}

	public static function render_ribbon() {
		global $product;

		if ( ! $product instanceof WC_Product || $product->is_in_stock() ) {
			return;
		}

		echo '<span class="wwcs-oos-ribbon">' . esc_html__( 'Out of Stock', 'webcasata-woocommerce-suite' ) . '</span>';
	}
}
