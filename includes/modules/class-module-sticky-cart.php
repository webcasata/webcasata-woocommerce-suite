<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_Sticky_Cart {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'woocommerce_after_single_product', array( __CLASS__, 'render_bar' ) );
	}

	public static function enqueue() {
		if ( ! is_product() ) {
			return;
		}
		wp_enqueue_style( 'wwcs-sticky-cart', WWCS_URL . 'assets/css/sticky-cart.css', array(), WWCS_VERSION );
		wp_enqueue_script( 'wwcs-sticky-cart', WWCS_URL . 'assets/js/sticky-cart.js', array( 'jquery' ), WWCS_VERSION, true );
	}

	public static function render_bar() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		?>
		<div class="wwcs-sticky-bar" id="wwcs-sticky-bar">
			<div class="wwcs-sticky-inner">
				<div class="wwcs-sticky-info">
					<span class="wwcs-sticky-title"><?php echo esc_html( $product->get_name() ); ?></span>
					<span class="wwcs-sticky-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				</div>
				<div class="wwcs-sticky-action">
					<?php if ( $product->is_type( 'variable' ) ) : ?>
						<a href="#" class="wwcs-sticky-btn wwcs-sticky-scroll">
							<?php esc_html_e( 'Select Options', 'webcasata-woocommerce-suite' ); ?>
						</a>
					<?php else : ?>
						<a href="#" class="wwcs-sticky-btn wwcs-sticky-add-to-cart">
							<?php esc_html_e( 'Add to Cart', 'webcasata-woocommerce-suite' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
