<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_Rating_Review {

	public static function init() {
		// WooCommerce's default loop template already outputs a star rating
		// at this same hook/priority (woocommerce_template_loop_rating, 5).
		// We remove it and replace it with our own version that also shows
		// the review count, so we don't end up with two ratings stacked.
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'render_rating' ), 5 );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-rating-review', WWCS_URL . 'assets/css/rating-review.css', array(), WWCS_VERSION );
	}

	public static function render_rating() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$count   = $product->get_review_count();
		$average = $product->get_average_rating();

		if ( $count < 1 && (float) $average <= 0 ) {
			return; // Nothing to show — matches WooCommerce's own default behavior.
		}

		echo '<div class="wwcs-rating-row">';
		echo wp_kses_post( wc_get_rating_html( $average, $count ) );

		if ( $count > 0 ) {
			printf(
				'<span class="wwcs-review-count">(%d)</span>',
				esc_html( $count )
			);
		}
		echo '</div>';
	}
}
