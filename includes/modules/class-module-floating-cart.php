<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A full-height slide-in cart drawer. Data and cart actions all go through
 * WooCommerce's own APIs and AJAX conventions rather than anything custom:
 *
 * - Line items, subtotal, and total come straight from WC()->cart.
 * - The "remove" link reuses wc_get_cart_remove_url() with the exact
 *   class/data attributes WooCommerce's own mini-cart uses, so its built-in
 *   AJAX remove handler (in wc-cart-fragments.js) picks it up for free.
 * - The quantity +/- stepper is the one piece WooCommerce's default
 *   mini-cart doesn't support inline, so it gets its own small AJAX
 *   endpoint (WC()->cart->set_quantity()) that returns fragments the same
 *   way core's own endpoints do.
 * - Cross-sell products come from WC()->cart->get_cross_sells() (the same
 *   data source the Cart page's own cross-sell block uses), rendered with
 *   woocommerce_template_loop_add_to_cart() so their Add to Cart buttons
 *   are the same AJAX-enabled markup as the shop loop's — no custom JS
 *   needed for that either.
 *
 * IMPORTANT: our wrapper elements use ONLY plugin-prefixed classes
 * (wwcs-floating-cart-*), deliberately avoiding generic WooCommerce
 * conventions like "widget_shopping_cart_content" — a theme's own cart
 * widget commonly uses that exact class too, and since fragment
 * replacement works by CSS selector, a shared generic class means the
 * theme's fragment can silently overwrite ours (or vice versa) whenever
 * both happen to match the same selector.
 *
 * The panel body is split into two independently-refreshed fragments —
 * content (scrollable: items + cross-sell) and footer (pinned to the
 * bottom: totals + buttons) — so the footer can stay visible via flexbox
 * while only the items list scrolls.
 */
class WWCS_Module_Floating_Cart {

	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'render_floating_cart' ) );
		add_filter( 'woocommerce_add_to_cart_fragments', array( __CLASS__, 'cart_fragments' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_wwcs_update_cart_item_qty', array( __CLASS__, 'ajax_update_qty' ) );
		add_action( 'wp_ajax_nopriv_wwcs_update_cart_item_qty', array( __CLASS__, 'ajax_update_qty' ) );
	}

	public static function enqueue() {
		if ( is_cart() || is_checkout() ) {
			return; // Redundant on the pages that already show the full cart.
		}

		wp_enqueue_style( 'wwcs-floating-cart', WWCS_URL . 'assets/css/floating-cart.css', array(), WWCS_VERSION );

		$bg_color    = sanitize_hex_color( WWCS_Settings::get_field_value( 'floating_cart_bg_color', '#7f54b3' ) );
		$badge_color = sanitize_hex_color( WWCS_Settings::get_field_value( 'floating_cart_badge_color', '#e63946' ) );

		wp_add_inline_style(
			'wwcs-floating-cart',
			sprintf(
				'.wwcs-floating-cart-toggle { background-color: %1$s; } .wwcs-floating-cart-count { background-color: %2$s; }',
				$bg_color ? $bg_color : '#7f54b3',
				$badge_color ? $badge_color : '#e63946'
			)
		);

		// wc-cart-fragments powers the fragment refresh on add-to-cart AND
		// the mini-cart's AJAX "remove item" links.
		wp_enqueue_script( 'wc-cart-fragments' );
		// wc-add-to-cart powers the cross-sell strip's Add to Cart buttons.
		wp_enqueue_script( 'wc-add-to-cart' );

		wp_enqueue_script( 'wwcs-floating-cart', WWCS_URL . 'assets/js/floating-cart.js', array( 'jquery', 'wc-cart-fragments', 'wc-add-to-cart' ), WWCS_VERSION, true );

		wp_localize_script(
			'wwcs-floating-cart',
			'wwcsFloatingCart',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wwcs_floating_cart' ),
				'autoOpen' => (bool) WWCS_Settings::get_field_value( 'floating_cart_auto_open', 0 ),
			)
		);
	}

	public static function render_floating_cart() {
		if ( is_cart() || is_checkout() ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$position       = WWCS_Settings::get_field_value( 'floating_cart_position', 'right' );
		$position_class = 'left' === $position ? 'wwcs-fc-left' : 'wwcs-fc-right';
		$count          = WC()->cart->get_cart_contents_count();
		?>
		<div id="wwcs-floating-cart" class="wwcs-floating-cart <?php echo esc_attr( $position_class ); ?>">
			<button type="button" class="wwcs-floating-cart-toggle" aria-label="<?php esc_attr_e( 'View cart', 'webcasata-woocommerce-suite' ); ?>">
				<span class="wwcs-floating-cart-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
				</span>
				<span class="wwcs-floating-cart-count" data-count="<?php echo esc_attr( $count ); ?>"><?php echo esc_html( $count ); ?></span>
			</button>

			<div class="wwcs-floating-cart-overlay"></div>

			<div class="wwcs-floating-cart-panel">
				<div class="wwcs-floating-cart-header">
					<span class="wwcs-fc-title">
						<?php esc_html_e( 'Shopping Cart', 'webcasata-woocommerce-suite' ); ?>
						(<span class="wwcs-fc-header-count"><?php echo esc_html( $count ); ?></span>)
					</span>
					<button type="button" class="wwcs-floating-cart-close" aria-label="<?php esc_attr_e( 'Close', 'webcasata-woocommerce-suite' ); ?>">&times;</button>
				</div>

				<div class="wwcs-floating-cart-content-scroll">
					<div class="wwcs-floating-cart-content">
						<?php self::render_cart_body(); ?>
					</div>
				</div>

				<div class="wwcs-floating-cart-footer">
					<?php self::render_cart_footer(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Scrollable area: the item list + cross-sell strip.
	 */
	private static function render_cart_body() {
		$cart = WC()->cart;

		if ( ! $cart || $cart->is_empty() ) {
			?>
			<p class="wwcs-fc-empty"><?php esc_html_e( 'Your cart is empty.', 'webcasata-woocommerce-suite' ); ?></p>
			<?php
			return;
		}
		?>
		<ul class="wwcs-fc-items">
			<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) :
				$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

				if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
					continue;
				}
				if ( ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					continue;
				}

				$permalink  = apply_filters( 'woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$thumbnail  = apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
				$name       = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key );
				$qty        = (int) $cart_item['quantity'];
				$line_price = $cart->get_product_subtotal( $product, $qty );
				?>
				<li class="wwcs-fc-item">
					<div class="wwcs-fc-item-row">
						<?php if ( $permalink ) : ?>
							<a href="<?php echo esc_url( $permalink ); ?>" class="wwcs-fc-item-thumb"><?php echo wp_kses_post( $thumbnail ); ?></a>
						<?php else : ?>
							<span class="wwcs-fc-item-thumb"><?php echo wp_kses_post( $thumbnail ); ?></span>
						<?php endif; ?>

						<div class="wwcs-fc-item-info">
							<div class="wwcs-fc-item-top">
								<span class="wwcs-fc-item-name">
									<?php if ( $permalink ) : ?>
										<a href="<?php echo esc_url( $permalink ); ?>"><?php echo wp_kses_post( $name ); ?></a>
									<?php else : ?>
										<?php echo wp_kses_post( $name ); ?>
									<?php endif; ?>
								</span>

								<span class="wwcs-fc-qty-stepper" data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>">
									<button type="button" class="wwcs-fc-qty-btn wwcs-fc-qty-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'webcasata-woocommerce-suite' ); ?>">&minus;</button>
									<span class="wwcs-fc-qty-value"><?php echo esc_html( $qty ); ?></span>
									<button type="button" class="wwcs-fc-qty-btn wwcs-fc-qty-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'webcasata-woocommerce-suite' ); ?>">+</button>
								</span>
							</div>

							<div class="wwcs-fc-item-price"><?php echo wp_kses_post( $line_price ); ?></div>
						</div>
					</div>

					<span class="wwcs-fc-item-remove-wrap">
						<?php
						echo wp_kses_post(
							apply_filters(
								'woocommerce_cart_item_remove_link',
								sprintf(
									'<a href="%s" class="wwcs-fc-item-remove remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
									esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
									esc_attr__( 'Remove this item', 'webcasata-woocommerce-suite' ),
									esc_attr( $product->get_id() ),
									esc_attr( $cart_item_key ),
									esc_attr( $product->get_sku() )
								),
								$cart_item_key
							)
						);
						?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php self::render_cross_sells(); ?>
		<?php
	}

	/**
	 * Pinned to the bottom of the drawer via flexbox: totals, Cart/Checkout,
	 * Continue Shopping. Rendered separately from the scrollable body so it
	 * stays visible without scrolling.
	 */
	private static function render_cart_footer() {
		$cart = WC()->cart;

		if ( ! $cart || $cart->is_empty() ) {
			return; // Nothing to total or check out yet.
		}
		?>
		<div class="wwcs-fc-totals">
			<div class="wwcs-fc-totals-row">
				<span><?php esc_html_e( 'Subtotal', 'webcasata-woocommerce-suite' ); ?></span>
				<span><?php echo wp_kses_post( wc_price( $cart->get_subtotal() ) ); ?></span>
			</div>
			<div class="wwcs-fc-totals-row wwcs-fc-total">
				<span><?php esc_html_e( 'Total', 'webcasata-woocommerce-suite' ); ?></span>
				<span><?php echo wp_kses_post( $cart->get_total() ); ?></span>
			</div>
		</div>

		<div class="wwcs-fc-buttons">
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wwcs-fc-btn"><?php esc_html_e( 'Cart', 'webcasata-woocommerce-suite' ); ?></a>
			<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="wwcs-fc-btn"><?php esc_html_e( 'Checkout', 'webcasata-woocommerce-suite' ); ?></a>
		</div>

		<button type="button" class="wwcs-fc-continue"><?php esc_html_e( 'Continue Shopping', 'webcasata-woocommerce-suite' ); ?></button>
		<?php
	}

	/**
	 * Uses WC()->cart->get_cross_sells() — the same product IDs the Cart
	 * page's own cross-sell block uses (products configured under a cart
	 * item's Linked Products tab, already excludes anything already in cart).
	 * Rendered with woocommerce_template_loop_add_to_cart() so each card's
	 * button is the exact same AJAX-enabled markup as the shop loop's.
	 */
	private static function render_cross_sells() {
		$cross_sell_ids = WC()->cart->get_cross_sells();
		if ( empty( $cross_sell_ids ) ) {
			return;
		}
		$cross_sell_ids = array_slice( $cross_sell_ids, 0, 8 );

		global $product;
		$original_global_product = $product;

		$rendered = 0;
		ob_start();
		foreach ( $cross_sell_ids as $cs_id ) {
			$cs_product = wc_get_product( $cs_id );
			if ( ! $cs_product || ! $cs_product->is_visible() || ! $cs_product->is_purchasable() ) {
				continue;
			}
			$product = $cs_product; // woocommerce_template_loop_add_to_cart() reads this global.
			?>
			<div class="wwcs-fc-crosssell-card">
				<a href="<?php echo esc_url( get_permalink( $cs_product->get_id() ) ); ?>">
					<?php echo wp_kses_post( $cs_product->get_image( 'woocommerce_thumbnail' ) ); ?>
					<span class="wwcs-fc-cs-name"><?php echo esc_html( $cs_product->get_name() ); ?></span>
				</a>
				<span class="wwcs-fc-cs-price"><?php echo wp_kses_post( $cs_product->get_price_html() ); ?></span>
				<?php woocommerce_template_loop_add_to_cart(); ?>
			</div>
			<?php
			$rendered++;
		}
		$cards_html = ob_get_clean();
		$product    = $original_global_product;

		if ( ! $rendered ) {
			return;
		}
		?>
		<div class="wwcs-fc-divider"><span><?php esc_html_e( 'You may be interested in…', 'webcasata-woocommerce-suite' ); ?></span></div>
		<div class="wwcs-fc-crosssell-track"><?php echo wp_kses_post( $cards_html ); ?></div>
		<?php if ( $rendered > 1 ) : ?>
			<div class="wwcs-fc-crosssell-dots">
				<?php for ( $i = 0; $i < $rendered; $i++ ) : ?>
					<button type="button" class="wwcs-fc-dot<?php echo 0 === $i ? ' wwcs-fc-dot-active' : ''; ?>" data-index="<?php echo esc_attr( $i ); ?>"></button>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
		<?php
	}

	public static function cart_fragments( $fragments ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return $fragments;
		}

		ob_start();
		self::render_cart_body();
		$body_html = ob_get_clean();

		ob_start();
		self::render_cart_footer();
		$footer_html = ob_get_clean();

		$count = WC()->cart->get_cart_contents_count();

		$fragments['div.wwcs-floating-cart-content'] = '<div class="wwcs-floating-cart-content">' . $body_html . '</div>';
		$fragments['div.wwcs-floating-cart-footer']  = '<div class="wwcs-floating-cart-footer">' . $footer_html . '</div>';
		$fragments['span.wwcs-floating-cart-count']  = sprintf( '<span class="wwcs-floating-cart-count" data-count="%1$d">%1$d</span>', (int) $count );
		$fragments['span.wwcs-fc-header-count']      = sprintf( '<span class="wwcs-fc-header-count">%d</span>', (int) $count );

		return $fragments;
	}

	/**
	 * The one piece of cart editing WooCommerce's default mini-cart doesn't
	 * support inline: changing an item's quantity without a full page visit.
	 * Returns fragments the same way core's own AJAX endpoints do, so the
	 * same fragment-replacement handling in floating-cart.js covers both.
	 */
	public static function ajax_update_qty() {
		check_ajax_referer( 'wwcs_floating_cart', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error();
		}

		$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		$quantity      = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 0;

		if ( ! $cart_item_key || ! isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Cart item not found.', 'webcasata-woocommerce-suite' ) ) );
		}

		if ( $quantity <= 0 ) {
			WC()->cart->remove_cart_item( $cart_item_key );
		} else {
			WC()->cart->set_quantity( $cart_item_key, $quantity, true );
		}

		wp_send_json_success(
			array(
				'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
			)
		);
	}
}
