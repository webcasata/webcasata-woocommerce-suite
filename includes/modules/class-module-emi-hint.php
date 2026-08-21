<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_EMI_Hint {

	public static function init() {
		// Priority 12 — after price (10) and the "You save" label (11), if active.
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'render_hint' ), 12 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-emi-hint', WWCS_URL . 'assets/css/emi-hint.css', array(), WWCS_VERSION );
	}

	public static function render_hint() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$installments = (int) WWCS_Settings::get_field_value( 'emi_installments', 3 );
		if ( $installments < 2 ) {
			return;
		}

		$price = (float) $product->get_price();
		if ( $price <= 0 ) {
			return;
		}

		$per_installment = $price / $installments;

		printf(
			'<span class="wwcs-emi-hint">%s</span>',
			wp_kses_post(
				sprintf(
					/* translators: 1: price per installment, formatted, 2: number of installments */
					__( 'or %1$s &times; %2$d', 'webcasata-woocommerce-suite' ),
					wc_price( $per_installment ),
					$installments
				)
			)
		);
	}
}
