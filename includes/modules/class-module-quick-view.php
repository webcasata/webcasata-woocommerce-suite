<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Module_Quick_View {

	/**
	 * - simple products: quantity + AJAX Add to Cart (unchanged).
	 * - variable products: WooCommerce's own native variation form, reused
	 *   as-is (attribute dropdowns, live price/stock, Add to Cart) — see
	 *   render_cart_section() for why this is reused rather than rebuilt.
	 * - external/affiliate products: a direct "Buy" link (cheap to support,
	 *   these have no cart form at all).
	 * - grouped products: left to the "View full details & options" link;
	 *   their multi-child-row UI doesn't fit a compact modal well.
	 */
	public static function init() {
		add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'render_button' ), 6 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_wwcs_quick_view', array( __CLASS__, 'ajax_render' ) );
		add_action( 'wp_ajax_nopriv_wwcs_quick_view', array( __CLASS__, 'ajax_render' ) );
	}

	public static function enqueue() {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return;
		}

		wp_enqueue_style( 'wwcs-quick-view', WWCS_URL . 'assets/css/quick-view.css', array(), WWCS_VERSION );

		// Reuse WooCommerce's own AJAX add-to-cart script + its localized
		// wc_add_to_cart_params, so a simple product added from inside the
		// modal behaves identically to the loop's own Add to Cart button
		// (mini-cart fragments, "added_to_cart" event, etc. all included).
		wp_enqueue_script( 'wc-add-to-cart' );

		// Must be enqueued now, on the original archive page load — enqueuing
		// it later from inside the AJAX response has no effect, since that
		// response never goes through wp_head/wp_footer on the page that's
		// already loaded. This is what lets a variation form injected into
		// the modal actually behave like a real variation form once it's
		// on the page (attribute matching, live price/stock, image swap).
		wp_enqueue_script( 'wc-add-to-cart-variation' );

		wp_enqueue_script( 'wwcs-quick-view', WWCS_URL . 'assets/js/quick-view.js', array( 'jquery', 'wc-add-to-cart', 'wc-add-to-cart-variation' ), WWCS_VERSION, true );

		wp_localize_script(
			'wwcs-quick-view',
			'wwcsQuickView',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wwcs_quick_view' ),
				'i18n'     => array(
					'loading' => __( 'Loading…', 'webcasata-woocommerce-suite' ),
					'error'   => __( 'Something went wrong. Please try again.', 'webcasata-woocommerce-suite' ),
					'added'   => __( 'Added to cart.', 'webcasata-woocommerce-suite' ),
				),
			)
		);
	}

	public static function render_button() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		printf(
			'<button type="button" class="wwcs-quick-view-btn" data-product-id="%d">%s</button>',
			esc_attr( $product->get_id() ),
			esc_html__( 'Quick View', 'webcasata-woocommerce-suite' )
		);
	}

	public static function ajax_render() {
		check_ajax_referer( 'wwcs_quick_view', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product || ! $product->is_visible() ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'webcasata-woocommerce-suite' ) ) );
		}

		ob_start();
		self::render_modal_content( $product );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	private static function render_modal_content( $wc_product ) {
		$type = $wc_product->get_type();
		?>
		<div class="wwcs-qv-layout">
			<div class="wwcs-qv-image">
				<?php echo wp_kses_post( $wc_product->get_image( 'woocommerce_single' ) ); ?>
			</div>
			<div class="wwcs-qv-summary">
				<h2 class="wwcs-qv-title"><?php echo esc_html( $wc_product->get_name() ); ?></h2>
				<div class="wwcs-qv-price"><?php echo wp_kses_post( $wc_product->get_price_html() ); ?></div>

				<?php
				$availability = $wc_product->get_availability();
				if ( ! empty( $availability['availability'] ) ) :
					?>
					<p class="stock <?php echo esc_attr( $availability['class'] ); ?>">
						<?php echo esc_html( $availability['availability'] ); ?>
					</p>
				<?php endif; ?>

				<?php if ( wc_review_ratings_enabled() && $wc_product->get_rating_count() > 0 ) : ?>
					<div class="wwcs-qv-rating">
						<?php echo wp_kses_post( wc_get_rating_html( $wc_product->get_average_rating(), $wc_product->get_review_count() ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $wc_product->get_short_description() ) : ?>
					<div class="wwcs-qv-excerpt">
						<?php echo wp_kses_post( wpautop( $wc_product->get_short_description() ) ); ?>
					</div>
				<?php endif; ?>

				<?php self::render_cart_section( $wc_product, $type ); ?>

				<?php if ( $wc_product->get_sku() ) : ?>
					<p class="wwcs-qv-sku">
						<?php esc_html_e( 'SKU:', 'webcasata-woocommerce-suite' ); ?>
						<span class="sku"><?php echo esc_html( $wc_product->get_sku() ); ?></span>
					</p>
				<?php endif; ?>

				<a href="<?php echo esc_url( get_permalink( $wc_product->get_id() ) ); ?>" class="wwcs-qv-full-link">
					<?php
					echo 'grouped' === $type
						? esc_html__( 'View full details & options', 'webcasata-woocommerce-suite' )
						: esc_html__( 'View full details', 'webcasata-woocommerce-suite' );
					?>
					&rarr;
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the type-appropriate way to buy the product.
	 *
	 * Variable products reuse WooCommerce's own
	 * woocommerce_template_single_add_to_cart(), which for a variable
	 * product outputs the exact same attribute-dropdown form used on the
	 * real single product page (data-product_variations JSON embedded in
	 * the form, hidden variation_id field, etc). That's less code for us to
	 * maintain than a custom selector, and it's the same markup
	 * wc-add-to-cart-variation.js already knows how to drive — quick-view.js
	 * just has to re-run its init on this specific form after it's injected,
	 * since the script's own page-load init already happened before this
	 * HTML existed.
	 */
	private static function render_cart_section( $wc_product, $type ) {
		if ( 'simple' === $type && $wc_product->is_purchasable() && $wc_product->is_in_stock() ) {
			?>
			<form class="wwcs-qv-cart-form cart" data-product_id="<?php echo esc_attr( $wc_product->get_id() ); ?>">
				<?php
				woocommerce_quantity_input(
					array(
						'min_value'   => 1,
						'max_value'   => $wc_product->get_max_purchase_quantity() > 0 ? $wc_product->get_max_purchase_quantity() : '',
						'input_value' => 1,
					),
					$wc_product
				);
				?>
				<button type="submit" class="wwcs-qv-add-to-cart button alt">
					<?php echo esc_html( $wc_product->single_add_to_cart_text() ); ?>
				</button>
			</form>
			<p class="wwcs-qv-message" aria-live="polite"></p>
			<?php
		} elseif ( 'variable' === $type && $wc_product->is_purchasable() ) {
			// woocommerce_template_single_add_to_cart() reads the global
			// $product, so it has to be set to this exact product first.
			global $product;
			$previous_global_product = $product;
			$product                 = $wc_product;

			woocommerce_template_single_add_to_cart();

			$product = $previous_global_product;
			?>
			<p class="wwcs-qv-message" aria-live="polite"></p>
			<?php
		} elseif ( 'external' === $type && $wc_product->is_purchasable() ) {
			?>
			<a
				href="<?php echo esc_url( $wc_product->get_product_url() ); ?>"
				class="button alt wwcs-qv-external-btn"
				target="_blank"
				rel="noopener nofollow"
			>
				<?php echo esc_html( $wc_product->single_add_to_cart_text() ); ?>
			</a>
			<?php
		} elseif ( ! $wc_product->is_in_stock() ) {
			?>
			<p class="wwcs-qv-stock-msg"><?php esc_html_e( 'Currently out of stock.', 'webcasata-woocommerce-suite' ); ?></p>
			<?php
		}
		// Grouped products (and anything else unhandled) fall through with
		// no inline action — the "View full details & options" link covers them.
	}
}
