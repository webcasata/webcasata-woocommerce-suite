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
						'description' => __( 'Preview a product in a modal without leaving the shop page.', 'webcasata-woocommerce-suite' ),
						'coming_soon' => true,
					),
				),
			),

			'product_card' => array(
				'label'    => __( 'Product Card', 'webcasata-woocommerce-suite' ),
				'features' => array(
					'discount_badge' => array(
						'label'       => __( 'Percentage Discount Badge', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows a "-X%" badge on product cards for products currently on sale.', 'webcasata-woocommerce-suite' ),
					),
					'color_swatch_card' => array(
						'label'       => __( 'Color Attribute on Product Card', 'webcasata-woocommerce-suite' ),
						'description' => __( 'Shows small color swatches on shop/archive product cards for variable products that have a color attribute.', 'webcasata-woocommerce-suite' ),
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
	 * Save posted toggle values. Only saves keys that actually exist in the
	 * registry and are not marked "coming soon", so nothing bogus gets stored.
	 *
	 * @param array $posted Flat array of feature_key => 1 (only checked boxes are present in $_POST).
	 */
	public static function save( $posted ) {
		$clean = array();

		foreach ( self::$registry as $tab ) {
			foreach ( $tab['features'] as $key => $feature ) {
				if ( ! empty( $feature['coming_soon'] ) ) {
					continue;
				}
				$clean[ $key ] = isset( $posted[ $key ] ) ? 1 : 0;
			}
		}

		update_option( self::OPTION_KEY, $clean );
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
