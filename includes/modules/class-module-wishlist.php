<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A lightweight wishlist: no dedicated database table, no admin management
 * screen — just a list of product IDs, stored as user meta for logged-in
 * shoppers or a cookie for guests, merged into the account automatically
 * on login. The wishlist PAGE itself is a [wwcs_wishlist] shortcode, the
 * same convention WooCommerce uses for its own Cart/Checkout pages, rather
 * than an auto-created page — the admin places it wherever they want and
 * picks that page in the settings so the "View Wishlist" toast link knows
 * where to point.
 */
class WWCS_Module_Wishlist {

	const COOKIE_NAME = 'wwcs_wishlist';
	const META_KEY     = '_wwcs_wishlist';

	public static function init() {
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_card_icon' ), 7 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_single_button' ), 35 );
		add_shortcode( 'wwcs_wishlist', array( __CLASS__, 'render_wishlist_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_wwcs_toggle_wishlist', array( __CLASS__, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nopriv_wwcs_toggle_wishlist', array( __CLASS__, 'ajax_toggle' ) );

		// Fold a guest's cookie-based wishlist into their account wishlist
		// the moment they log in, then clear the cookie — mirrors how
		// WooCommerce itself merges a guest cart on login.
		add_action( 'wp_login', array( __CLASS__, 'merge_guest_wishlist_on_login' ), 10, 2 );
	}

	public static function enqueue() {
		if ( ! self::should_enqueue() ) {
			return;
		}

		wp_enqueue_style( 'wwcs-wishlist', WWCS_URL . 'assets/css/wishlist.css', array(), WWCS_VERSION );

		$active_color = sanitize_hex_color( WWCS_Settings::get_field_value( 'wishlist_active_color', '#e63946' ) );
		wp_add_inline_style(
			'wwcs-wishlist',
			sprintf(
				'.wwcs-wishlist-btn.wwcs-wishlist-active svg { fill: %1$s; stroke: %1$s; }',
				$active_color ? $active_color : '#e63946'
			)
		);

		wp_enqueue_script( 'wwcs-wishlist', WWCS_URL . 'assets/js/wishlist.js', array( 'jquery' ), WWCS_VERSION, true );

		$page_id = (int) WWCS_Settings::get_field_value( 'wishlist_page_id', '' );

		wp_localize_script(
			'wwcs-wishlist',
			'wwcsWishlist',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'wwcs_wishlist' ),
				'wishlistUrl' => $page_id ? get_permalink( $page_id ) : '',
				'i18n'        => array(
					'added'         => __( 'Added to wishlist.', 'webcasata-woocommerce-suite' ),
					'removed'       => __( 'Removed from wishlist.', 'webcasata-woocommerce-suite' ),
					'viewWishlist'  => __( 'View Wishlist', 'webcasata-woocommerce-suite' ),
					'addToWishlist' => __( 'Add to Wishlist', 'webcasata-woocommerce-suite' ),
					'inWishlist'    => __( 'In Wishlist', 'webcasata-woocommerce-suite' ),
					'error'         => __( 'Something went wrong. Please try again.', 'webcasata-woocommerce-suite' ),
				),
			)
		);
	}

	/**
	 * True on WooCommerce's own archive/product pages, AND on any page that
	 * contains the [wwcs_wishlist] shortcode (typically the wishlist page
	 * itself, which usually isn't a WooCommerce-recognized page type).
	 *
	 * This check has to happen here, at the normal wp_enqueue_scripts
	 * timing — NOT lazily from inside the shortcode's own render callback.
	 * Shortcodes render during the_content(), which runs after wp_head()
	 * has already printed enqueued styles for the request; a style enqueued
	 * that late would simply never be output.
	 */
	private static function should_enqueue() {
		if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() || is_product() ) {
			return true;
		}

		if ( is_singular( 'page' ) ) {
			$post = get_post();
			if ( $post && has_shortcode( $post->post_content, 'wwcs_wishlist' ) ) {
				return true;
			}
		}

		return false;
	}

	public static function render_card_icon() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$in_wishlist = in_array( $product->get_id(), self::get_wishlist_ids(), true );

		printf(
			'<button type="button" class="wwcs-wishlist-btn%1$s" data-product_id="%2$d" aria-label="%3$s" aria-pressed="%4$s">%5$s</button>',
			$in_wishlist ? ' wwcs-wishlist-active' : '',
			esc_attr( $product->get_id() ),
			esc_attr__( 'Add to wishlist', 'webcasata-woocommerce-suite' ),
			$in_wishlist ? 'true' : 'false',
			self::heart_svg()
		);
	}

	public static function render_single_button() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$in_wishlist = in_array( $product->get_id(), self::get_wishlist_ids(), true );

		printf(
			'<button type="button" class="wwcs-wishlist-btn wwcs-wishlist-btn-single%1$s" data-product_id="%2$d" aria-pressed="%3$s">%4$s <span class="wwcs-wishlist-label">%5$s</span></button>',
			$in_wishlist ? ' wwcs-wishlist-active' : '',
			esc_attr( $product->get_id() ),
			$in_wishlist ? 'true' : 'false',
			self::heart_svg(),
			$in_wishlist ? esc_html__( 'In Wishlist', 'webcasata-woocommerce-suite' ) : esc_html__( 'Add to Wishlist', 'webcasata-woocommerce-suite' )
		);
	}

	private static function heart_svg() {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
	}

	public static function render_wishlist_shortcode() {
		$ids = self::get_wishlist_ids();

		ob_start();

		if ( empty( $ids ) ) {
			?>
			<div class="wwcs-wishlist-empty">
				<p><?php esc_html_e( 'Your wishlist is empty.', 'webcasata-woocommerce-suite' ); ?></p>
				<?php if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ) : ?>
					<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="button">
						<?php esc_html_e( 'Browse Products', 'webcasata-woocommerce-suite' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php
			return ob_get_clean();
		}
		?>
		<div class="wwcs-wishlist-grid">
			<?php
			global $product;
			$original_global_product = $product;

			foreach ( $ids as $id ) :
				$wl_product = wc_get_product( $id );
				if ( ! $wl_product || ! $wl_product->is_visible() ) {
					continue;
				}
				$product = $wl_product; // woocommerce_template_loop_add_to_cart() reads this global.
				?>
				<div class="wwcs-wishlist-item" data-product_id="<?php echo esc_attr( $id ); ?>">
					<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" class="wwcs-wishlist-item-image">
						<?php echo wp_kses_post( $wl_product->get_image( 'woocommerce_thumbnail' ) ); ?>
					</a>
					<div class="wwcs-wishlist-item-info">
						<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" class="wwcs-wishlist-item-name">
							<?php echo esc_html( $wl_product->get_name() ); ?>
						</a>
						<div class="wwcs-wishlist-item-price"><?php echo wp_kses_post( $wl_product->get_price_html() ); ?></div>
						<div class="wwcs-wishlist-item-actions">
							<?php woocommerce_template_loop_add_to_cart(); ?>
							<button type="button" class="wwcs-wishlist-remove" data-product_id="<?php echo esc_attr( $id ); ?>">
								<?php esc_html_e( 'Remove', 'webcasata-woocommerce-suite' ); ?>
							</button>
						</div>
					</div>
				</div>
				<?php
			endforeach;

			$product = $original_global_product;
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function ajax_toggle() {
		check_ajax_referer( 'wwcs_wishlist', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! $product_id || ! wc_get_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'webcasata-woocommerce-suite' ) ) );
		}

		$ids         = self::get_wishlist_ids();
		$in_wishlist = in_array( $product_id, $ids, true );

		if ( $in_wishlist ) {
			$ids = array_values( array_diff( $ids, array( $product_id ) ) );
		} else {
			$ids[] = $product_id;
		}

		self::save_wishlist_ids( $ids );

		wp_send_json_success(
			array(
				'in_wishlist' => ! $in_wishlist,
				'count'       => count( $ids ),
			)
		);
	}

	private static function get_wishlist_ids() {
		if ( is_user_logged_in() ) {
			$ids = get_user_meta( get_current_user_id(), self::META_KEY, true );
			return is_array( $ids ) ? array_values( array_unique( array_map( 'absint', $ids ) ) ) : array();
		}

		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return array();
		}

		$raw = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

		return array_values( array_unique( $ids ) );
	}

	private static function save_wishlist_ids( $ids ) {
		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::META_KEY, $ids );
			return;
		}

		if ( empty( $ids ) ) {
			setcookie( self::COOKIE_NAME, '', time() - DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
			unset( $_COOKIE[ self::COOKIE_NAME ] );
			return;
		}

		$value = implode( ',', $ids );
		setcookie( self::COOKIE_NAME, $value, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		$_COOKIE[ self::COOKIE_NAME ] = $value; // So a same-request read (rare, but cheap to guard) sees the fresh value.
	}

	public static function merge_guest_wishlist_on_login( $user_login, $user ) {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return;
		}

		$raw       = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		$guest_ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

		if ( empty( $guest_ids ) ) {
			return;
		}

		$existing = get_user_meta( $user->ID, self::META_KEY, true );
		$existing = is_array( $existing ) ? $existing : array();

		$merged = array_values( array_unique( array_merge( $existing, $guest_ids ) ) );
		update_user_meta( $user->ID, self::META_KEY, $merged );

		setcookie( self::COOKIE_NAME, '', time() - DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	}
}
