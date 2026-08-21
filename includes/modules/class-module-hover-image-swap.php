<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_Hover_Image_Swap {

	public static function init() {
		// Output the hover image right after WooCommerce's own thumbnail
		// (woocommerce_template_loop_product_thumbnail runs at priority 10).
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_hover_image' ), 11 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}
		// CSS-only — the swap is a pure :hover opacity transition, no JS needed.
		wp_enqueue_style( 'wwcs-hover-image-swap', WWCS_URL . 'assets/css/hover-image-swap.css', array(), WWCS_VERSION );
	}

	public static function render_hover_image() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$gallery_ids = $product->get_gallery_image_ids();
		if ( empty( $gallery_ids ) ) {
			return; // Nothing to swap to.
		}

		echo wp_get_attachment_image(
			$gallery_ids[0],
			'woocommerce_thumbnail',
			false,
			array( 'class' => 'wwcs-hover-image' )
		);
	}
}
