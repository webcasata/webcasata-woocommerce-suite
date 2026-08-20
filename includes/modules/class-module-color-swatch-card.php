<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_Color_Swatch_Card {

	public static function init() {
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'render_swatches' ), 15 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-color-swatch', WWCS_URL . 'assets/css/color-swatch.css', array(), WWCS_VERSION );
	}

	public static function render_swatches() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$taxonomy = self::find_color_taxonomy( $product );
		if ( ! $taxonomy ) {
			return;
		}

		$terms = get_the_terms( $product->get_id(), $taxonomy );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		echo '<div class="wwcs-color-swatches">';
		foreach ( $terms as $term ) {
			printf(
				'<span class="wwcs-color-dot" style="background-color:%s" title="%s"></span>',
				esc_attr( self::term_color( $term ) ),
				esc_attr( $term->name )
			);
		}
		echo '</div>';
	}

	/**
	 * Finds a taxonomy attribute whose slug looks like a color attribute
	 * (pa_color, pa_colour, etc). Falls back to the common pa_color convention.
	 */
	private static function find_color_taxonomy( $product ) {
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->is_taxonomy() ) {
				continue;
			}
			$taxonomy = $attribute->get_name();
			if ( false !== strpos( $taxonomy, 'color' ) || false !== strpos( $taxonomy, 'colour' ) ) {
				return $taxonomy;
			}
		}

		return taxonomy_exists( 'pa_color' ) ? 'pa_color' : false;
	}

	/**
	 * Resolves a CSS color for a term. Checks the term meta keys used by
	 * several popular swatch plugins first, then falls back to treating the
	 * term name itself as a CSS color keyword (works for simple cases like
	 * "Red" / "Navy", silently no-ops otherwise without breaking layout).
	 */
	private static function term_color( $term ) {
		$meta_keys = array( 'product_attribute_color', 'color', 'swatch_color', 'wcpa_color' );

		foreach ( $meta_keys as $key ) {
			$value = get_term_meta( $term->term_id, $key, true );
			if ( $value ) {
				return $value;
			}
		}

		return sanitize_html_class( strtolower( $term->name ) );
	}
}
