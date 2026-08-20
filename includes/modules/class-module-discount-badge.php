<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_Discount_Badge {

	public static function init() {
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_badge' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-discount-badge', WWCS_URL . 'assets/css/discount-badge.css', array(), WWCS_VERSION );
	}

	public static function render_badge() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
			return;
		}

		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		// Variable products: use the min/lowest active prices across variations.
		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_prices( true );
			if ( empty( $prices['regular_price'] ) || empty( $prices['sale_price'] ) ) {
				return;
			}
			$regular = (float) reset( $prices['regular_price'] );
			$sale    = (float) reset( $prices['sale_price'] );
		}

		if ( $regular <= 0 || $sale >= $regular ) {
			return;
		}

		$percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );

		if ( $percent <= 0 ) {
			return;
		}

		printf(
			'<span class="wwcs-discount-badge">-%d%%</span>',
			esc_html( $percent )
		);
	}
}
