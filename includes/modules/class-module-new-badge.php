<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_New_Badge {

	public static function init() {
		// Priority 4 — renders before the discount badge (priority 5) and the
		// out-of-stock ribbon (priority 6), so the CSS stacking rules line up.
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_badge' ), 4 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-new-badge', WWCS_URL . 'assets/css/new-badge.css', array(), WWCS_VERSION );

		wp_add_inline_style(
			'wwcs-new-badge',
			WWCS_Settings::build_badge_css(
				'.wwcs-new-badge',
				'new_badge',
				array(
					'bg_color'      => '#2a9d8f',
					'text_color'    => '#ffffff',
					'border_radius' => 4,
					'font_size'     => 11,
				)
			)
		);
	}

	public static function render_badge() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$days = (int) WWCS_Settings::get_field_value( 'new_badge_days', 30 );
		if ( $days <= 0 ) {
			return;
		}

		$published = get_post_time( 'U', true, $product->get_id() );
		if ( ! $published ) {
			return;
		}

		$age_in_days = ( time() - $published ) / DAY_IN_SECONDS;
		if ( $age_in_days > $days ) {
			return;
		}

		echo '<span class="wwcs-new-badge">' . esc_html__( 'New', 'webcasata-woocommerce-suite' ) . '</span>';
	}
}
