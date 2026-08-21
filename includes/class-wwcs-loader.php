<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps each feature key to its module file + class, and loads ONLY the
 * modules that are toggled on. A disabled module's file is never required,
 * its class never instantiated, its hooks never added, its assets never
 * enqueued — this is what keeps the "one plugin instead of eight" pitch
 * honest from a performance standpoint.
 */
class WWCS_Loader {

	/**
	 * feature_key => [ 'file' => relative path under includes/, 'class' => class name with an init() method ]
	 */
	public static $module_map = array(
		'sticky_cart' => array(
			'file'  => 'modules/class-module-sticky-cart.php',
			'class' => 'WWCS_Module_Sticky_Cart',
		),
		'plus_minus' => array(
			'file'  => 'modules/class-module-plus-minus.php',
			'class' => 'WWCS_Module_Plus_Minus',
		),
		'discount_badge' => array(
			'file'  => 'modules/class-module-discount-badge.php',
			'class' => 'WWCS_Module_Discount_Badge',
		),
		'color_swatch_card' => array(
			'file'  => 'modules/class-module-color-swatch-card.php',
			'class' => 'WWCS_Module_Color_Swatch_Card',
		),
		'hover_image_swap' => array(
			'file'  => 'modules/class-module-hover-image-swap.php',
			'class' => 'WWCS_Module_Hover_Image_Swap',
		),
		'auto_new_badge' => array(
			'file'  => 'modules/class-module-new-badge.php',
			'class' => 'WWCS_Module_New_Badge',
		),
		'you_save_label' => array(
			'file'  => 'modules/class-module-you-save.php',
			'class' => 'WWCS_Module_You_Save',
		),
		'rating_review_count' => array(
			'file'  => 'modules/class-module-rating-review.php',
			'class' => 'WWCS_Module_Rating_Review',
		),
		'oos_ribbon' => array(
			'file'  => 'modules/class-module-oos-ribbon.php',
			'class' => 'WWCS_Module_OOS_Ribbon',
		),
		'emi_price_hint' => array(
			'file'  => 'modules/class-module-emi-hint.php',
			'class' => 'WWCS_Module_EMI_Hint',
		),
	);

	public static function init() {
		// woocommerce_init fires after WooCommerce is fully loaded, so modules
		// can safely reference WC_Product etc. Also lets other plugins/mu-plugins
		// filter the module map before we load anything, via wwcs_module_map.
		add_action( 'woocommerce_init', array( __CLASS__, 'load_active_modules' ) );
	}

	public static function load_active_modules() {
		$map = apply_filters( 'wwcs_module_map', self::$module_map );

		foreach ( $map as $feature_key => $info ) {

			if ( ! WWCS_Settings::is_enabled( $feature_key ) ) {
				continue; // Toggle is off — nothing is loaded for this feature.
			}

			$file = WWCS_PATH . 'includes/' . $info['file'];
			if ( ! file_exists( $file ) ) {
				continue;
			}

			require_once $file;

			if ( class_exists( $info['class'] ) && method_exists( $info['class'], 'init' ) ) {
				call_user_func( array( $info['class'], 'init' ) );
			}
		}
	}
}
