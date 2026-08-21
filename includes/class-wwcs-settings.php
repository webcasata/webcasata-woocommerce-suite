<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registry of every tab + feature in the plugin.
 *
 * This ONE array drives:
 *  - the admin toggle UI (WWCS_Admin_Page)
 *  - which options get saved / sanitized
 *  - (together with WWCS_Loader::$module_map) which module files get loaded
 *
 * To add a new feature: add an entry here, add a matching entry in
 * WWCS_Loader::$module_map, and create the module class file.
 */
class WWCS_Settings {

	const OPTION_KEY           = 'wwcs_settings';
	const UNINSTALL_OPTION_KEY = 'wwcs_delete_data_on_uninstall';

	public static $registry = array();

	public static function init() {
		self::build_registry();
	}

	public static function build_registry() {
		self::$registry = array(

			'general' => array(
				'label'    => __( 'General', 'webcasata-woocommerce-suite' ),
				'features' => array(
					'sticky_cart' => array(
						'label'       => __( 'Sticky Add to Cart', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows a sticky bar with product title, price and an Add to Cart button once the default button scrolls out of view.', 'webcasata-woocommerce-suite' ),
					),
					'plus_minus' => array(
						'label'       => __( 'Plus / Minus Quantity Buttons', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Adds styled +/- buttons around the quantity field on product, cart and checkout pages.', 'webcasata-woocommerce-suite' ),
					),
					'quick_view' => array(
						'label'       => __( 'Quick View', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Preview a product — image, price, description, and Add to Cart — in a modal without leaving the shop page. Products with variations link through to the full product page instead of adding to cart inline.', 'webcasata-woocommerce-suite' ),
					),
				),
			),

			'product_card' => array(
				'label'    => __( 'Product Card', 'webcasata-woocommerce-suite' ),
				'features' => array(
					'discount_badge' => array(
						'label'       => __( 'Percentage Discount Badge', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows a "-X%" badge on product cards for products currently on sale.', 'webcasata-woocommerce-suite' ),
						'fields'      => array(
							'discount_badge_bg_color'      => array( 'type' => 'color', 'label' => __( 'Background color', 'webcasata-woocommerce-suite' ), 'default' => '#e63946' ),
							'discount_badge_text_color'    => array( 'type' => 'color', 'label' => __( 'Text color', 'webcasata-woocommerce-suite' ), 'default' => '#ffffff' ),
							'discount_badge_border_radius' => array( 'type' => 'number', 'label' => __( 'Border radius', 'webcasata-woocommerce-suite' ), 'default' => 4, 'suffix' => 'px' ),
							'discount_badge_font_size'     => array( 'type' => 'number', 'label' => __( 'Font size', 'webcasata-woocommerce-suite' ), 'default' => 12, 'suffix' => 'px' ),
						),
					),
					'color_swatch_card' => array(
						'label'       => __( 'Color Attribute on Product Card', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows small color swatches on shop/archive product cards for variable products that have a color attribute.', 'webcasata-woocommerce-suite' ),
					),
					'hover_image_swap' => array(
						'label'       => __( 'Hover Image Swap', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Swaps to the product\'s second gallery image when a shopper hovers over its card.', 'webcasata-woocommerce-suite' ),
					),
					'auto_new_badge' => array(
						'label'       => __( 'Auto "New" Badge', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows a NEW badge on products published within a set number of days — no manual tagging needed.', 'webcasata-woocommerce-suite' ),
						'fields'      => array(
							'new_badge_days'          => array( 'type' => 'number', 'label' => __( 'Show for products newer than', 'webcasata-woocommerce-suite' ), 'default' => 30, 'suffix' => 'days' ),
							'new_badge_bg_color'      => array( 'type' => 'color', 'label' => __( 'Background color', 'webcasata-woocommerce-suite' ), 'default' => '#2a9d8f' ),
							'new_badge_text_color'    => array( 'type' => 'color', 'label' => __( 'Text color', 'webcasata-woocommerce-suite' ), 'default' => '#ffffff' ),
							'new_badge_border_radius' => array( 'type' => 'number', 'label' => __( 'Border radius', 'webcasata-woocommerce-suite' ), 'default' => 4, 'suffix' => 'px' ),
							'new_badge_font_size'     => array( 'type' => 'number', 'label' => __( 'Font size', 'webcasata-woocommerce-suite' ), 'default' => 11, 'suffix' => 'px' ),
						),
					),
					'you_save_label' => array(
						'label'       => __( '"You Save" Label', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows the exact amount saved (e.g. "You save $10") on sale product cards, alongside the discount badge.', 'webcasata-woocommerce-suite' ),
						'fields'      => array(
							'you_save_bg_color'      => array( 'type' => 'color', 'label' => __( 'Background color', 'webcasata-woocommerce-suite' ), 'default' => '#e8f6f3' ),
							'you_save_text_color'    => array( 'type' => 'color', 'label' => __( 'Text color', 'webcasata-woocommerce-suite' ), 'default' => '#2a9d8f' ),
							'you_save_border_radius' => array( 'type' => 'number', 'label' => __( 'Border radius', 'webcasata-woocommerce-suite' ), 'default' => 4, 'suffix' => 'px' ),
							'you_save_font_size'     => array( 'type' => 'number', 'label' => __( 'Font size', 'webcasata-woocommerce-suite' ), 'default' => 12, 'suffix' => 'px' ),
						),
					),
					'rating_review_count' => array(
						'label'       => __( 'Star Rating + Review Count', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows star rating and review count on product cards, consistently, regardless of theme.', 'webcasata-woocommerce-suite' ),
					),
					'oos_ribbon' => array(
						'label'       => __( 'Out of Stock Ribbon', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows a clear "Out of Stock" ribbon and dims the image for out-of-stock products.', 'webcasata-woocommerce-suite' ),
						'fields'      => array(
							'oos_ribbon_bg_color'      => array( 'type' => 'color', 'label' => __( 'Background color', 'webcasata-woocommerce-suite' ), 'default' => '#6c757d' ),
							'oos_ribbon_text_color'    => array( 'type' => 'color', 'label' => __( 'Text color', 'webcasata-woocommerce-suite' ), 'default' => '#ffffff' ),
							'oos_ribbon_border_radius' => array( 'type' => 'number', 'label' => __( 'Border radius', 'webcasata-woocommerce-suite' ), 'default' => 4, 'suffix' => 'px' ),
							'oos_ribbon_font_size'     => array( 'type' => 'number', 'label' => __( 'Font size', 'webcasata-woocommerce-suite' ), 'default' => 11, 'suffix' => 'px' ),
						),
					),
					'emi_price_hint' => array(
						'label'       => __( 'Installment / EMI Price Hint', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows an "or $X × N" instalment hint under the price, to make higher-ticket items feel more affordable.', 'webcasata-woocommerce-suite' ),
						'fields'      => array(
							'emi_installments' => array(
								'type'    => 'number',
								'label'   => __( 'Number of installments', 'webcasata-woocommerce-suite' ),
								'default' => 3,
							),
						),
					),
				),
			),

			'cart' => array(
				'label'    => __( 'Cart', 'webcasata-woocommerce-suite' ),
				'features' => array(
					'floating_cart' => array(
						'label'       => __( 'Floating / Mini Cart', 'webcasata-woocommerce-suite' ),
						'description' => __( 'A floating cart icon with item count and a slide-out mini-cart.', 'webcasata-woocommerce-suite' ),
						'coming_soon' => true,
					),
				),
			),

			'login_register' => array(
				'label'    => __( 'Login / Register', 'webcasata-woocommerce-suite' ),
				'features' => array(
					'login_popup' => array(
						'label'       => __( 'Login / Register Popup', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Opens login/registration in a modal instead of a separate page.', 'webcasata-woocommerce-suite' ),
						'coming_soon' => true,
					),
				),
			),
		);

		// Let other code (or future Pro add-on) register more modules.
		self::$registry = apply_filters( 'wwcs_module_registry', self::$registry );
	}

	/**
	 * Called once on activation. Doesn't overwrite existing settings on re-activation.
	 */
	public static function set_defaults() {
		if ( false === get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, array() );
		}
	}

	public static function get_all() {
		$saved = get_option( self::OPTION_KEY, array() );
		return is_array( $saved ) ? $saved : array();
	}

	public static function is_enabled( $feature_key ) {
		$saved = self::get_all();
		return ! empty( $saved[ $feature_key ] );
	}

	/**
	 * Save posted toggle values (and any per-feature config fields) for ONE
	 * tab at a time.
	 *
	 * A given form only ever renders the checkboxes/fields for the tab
	 * currently being viewed, so $posted only ever contains that tab's keys
	 * (an unchecked box, or a field disabled by JS because its toggle is
	 * off, simply isn't present in $_POST — same as a box on a different
	 * tab that was never rendered). Because of that, we start from the
	 * existing saved settings and only recompute the keys that belong to
	 * $tab_key — every other tab's values, and any field that wasn't
	 * actually submitted, are left untouched. Without this, saving Tab B
	 * would look indistinguishable from "everything on every other tab was
	 * just unchecked/cleared" and wipe it.
	 *
	 * @param array       $posted  Flat array of key => value from $_POST for the submitted tab.
	 * @param string|null $tab_key The tab that was actually submitted. If missing/invalid, nothing is changed.
	 */
	public static function save( $posted, $tab_key = null ) {
		if ( ! $tab_key || ! isset( self::$registry[ $tab_key ] ) ) {
			return; // Unknown tab — don't touch any saved settings.
		}

		$clean = self::get_all();

		foreach ( self::$registry[ $tab_key ]['features'] as $key => $feature ) {
			if ( ! empty( $feature['coming_soon'] ) ) {
				continue;
			}

			$clean[ $key ] = isset( $posted[ $key ] ) ? 1 : 0;

			if ( empty( $feature['fields'] ) ) {
				continue;
			}

			foreach ( $feature['fields'] as $field_key => $field ) {
				if ( isset( $posted[ $field_key ] ) && '' !== $posted[ $field_key ] ) {
					$clean[ $field_key ] = self::sanitize_field_value( $posted[ $field_key ], $field );
				} elseif ( ! isset( $clean[ $field_key ] ) ) {
					// First time this field has ever been saved — seed it with its default.
					$clean[ $field_key ] = isset( $field['default'] ) ? $field['default'] : '';
				}
				// Otherwise: field wasn't submitted this time — keep whatever was already saved.
			}
		}

		update_option( self::OPTION_KEY, $clean );
	}

	private static function sanitize_field_value( $raw, $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		switch ( $type ) {
			case 'number':
				return absint( $raw );
			case 'color':
				$color = sanitize_hex_color( $raw );
				return $color ? $color : ( isset( $field['default'] ) ? $field['default'] : '#000000' );
			default:
				return sanitize_text_field( $raw );
		}
	}

	/**
	 * Fetch a saved config field's value (e.g. new_badge_days), falling back
	 * to the given default when nothing has been saved yet.
	 */
	public static function get_field_value( $key, $default = '' ) {
		$saved = self::get_all();
		return isset( $saved[ $key ] ) && '' !== $saved[ $key ] ? $saved[ $key ] : $default;
	}

	/**
	 * Builds a single CSS rule (background, text color, border-radius, font
	 * size) for a "badge-style" module from its saved {$prefix}_* fields,
	 * for use with wp_add_inline_style(). Used by Discount Badge, New
	 * Badge, You Save Label, and OOS Ribbon so the same four appearance
	 * fields behave identically everywhere they appear.
	 *
	 * @param string $selector CSS selector to style, e.g. '.wwcs-discount-badge'.
	 * @param string $prefix   Field key prefix, e.g. 'discount_badge' (reads discount_badge_bg_color etc).
	 * @param array  $defaults Fallback values: bg_color, text_color, border_radius, font_size.
	 */
	public static function build_badge_css( $selector, $prefix, $defaults = array() ) {
		$defaults = wp_parse_args(
			$defaults,
			array(
				'bg_color'      => '#000000',
				'text_color'    => '#ffffff',
				'border_radius' => 4,
				'font_size'     => 12,
			)
		);

		$bg     = sanitize_hex_color( self::get_field_value( $prefix . '_bg_color', $defaults['bg_color'] ) );
		$color  = sanitize_hex_color( self::get_field_value( $prefix . '_text_color', $defaults['text_color'] ) );
		$radius = (int) self::get_field_value( $prefix . '_border_radius', $defaults['border_radius'] );
		$size   = (int) self::get_field_value( $prefix . '_font_size', $defaults['font_size'] );

		return sprintf(
			'%s { background-color: %s; color: %s; border-radius: %dpx; font-size: %dpx; }',
			$selector,
			$bg ? $bg : $defaults['bg_color'],
			$color ? $color : $defaults['text_color'],
			$radius,
			$size
		);
	}

	/**
	 * Whether the admin has opted in to deleting all plugin data on uninstall.
	 * Defaults to false — data is kept by default so reinstalling the plugin
	 * restores the previous configuration.
	 */
	public static function get_delete_on_uninstall() {
		return (bool) get_option( self::UNINSTALL_OPTION_KEY, false );
	}

	public static function save_delete_on_uninstall( $enabled ) {
		update_option( self::UNINSTALL_OPTION_KEY, $enabled ? 1 : 0 );
	}
}
