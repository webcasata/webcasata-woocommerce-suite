<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_You_Save {

	public static function init() {
		// Priority 11 — right after WooCommerce's own price (priority 10).
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'render_savings' ), 11 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-you-save', WWCS_URL . 'assets/css/you-save.css', array(), WWCS_VERSION );

		wp_add_inline_style(
			'wwcs-you-save',
			WWCS_Settings::build_badge_css(
				'.wwcs-you-save',
				'you_save',
				array(
					'bg_color'      => '#e8f6f3',
					'text_color'    => '#2a9d8f',
					'border_radius' => 4,
					'font_size'     => 12,
				)
			)
		);
	}

	public static function render_savings() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
			return;
		}

		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

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

		$saved = $regular - $sale;

		printf(
			'<span class="wwcs-you-save">%s</span>',
			wp_kses_post(
				sprintf(
					/* translators: %s: amount saved, formatted as a price */
					__( 'You save %s', 'webcasata-woocommerce-suite' ),
					wc_price( $saved )
				)
			)
		);
	}
}
