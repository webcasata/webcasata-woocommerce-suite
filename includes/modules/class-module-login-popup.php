<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reuses WooCommerce's own My Account login/register template
 * (myaccount/form-login.php) as-is — same form fields, same nonces, same
 * WC_Form_Handler processing on wp_loaded. This module adds a tabbed modal
 * shell around it, plus a couple of CSS class renames on WooCommerce's own
 * two-column output so it can show as tabs instead.
 *
 * Two ways to open the modal, both active at once:
 *  - Clicking whatever link the theme already uses for "My Account".
 *  - Clicking ANY element carrying one of the classes in the
 *    wwcs_login_trigger_classes filter (default: wwcs-login-trigger,
 *    wcloginmodal) — works on links, buttons, images, plain text, anything.
 *    Add data-tab="register" on the trigger element to open straight to
 *    the Register tab instead of Login.
 *
 * Submission is a normal (non-AJAX) form post, because WooCommerce doesn't
 * expose an AJAX endpoint for login/registration the way it does for cart
 * actions — reimplementing that validation/auth logic ourselves would mean
 * duplicating WC_Form_Handler rather than reusing it. On success the page
 * reloads with the user logged in; on error it reloads with WooCommerce's
 * own notice shown, and the modal reopens itself to the right tab (see the
 * $_POST check in enqueue()).
 */
class WWCS_Module_Login_Popup {

	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'render_modal' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( is_user_logged_in() ) {
			return; // Nothing to log into or register for.
		}

		wp_enqueue_style( 'wwcs-login-popup', WWCS_URL . 'assets/css/login-popup.css', array(), WWCS_VERSION );
		wp_enqueue_script( 'wwcs-login-popup', WWCS_URL . 'assets/js/login-popup.js', array( 'jquery' ), WWCS_VERSION, true );

		// If this page load is the result of a failed login/register
		// attempt (WC doesn't redirect on error, only on success), reopen
		// the modal to the right tab so the error notice is actually seen
		// instead of being added to a closed modal.
		$auto_open_tab = null;
		if ( isset( $_POST['login'] ) ) {
			$auto_open_tab = 'login';
		} elseif ( isset( $_POST['register'] ) ) {
			$auto_open_tab = 'register';
		}

		// Any element (link, button, span, image...) carrying one of these
		// classes opens the popup on click — lets the modal be triggered
		// from anywhere, not just the theme's My Account link. Filterable
		// so a site can register its own class name without editing this file.
		$trigger_classes = apply_filters( 'wwcs_login_trigger_classes', array( 'wwcs-login-trigger', 'wcloginmodal' ) );
		$trigger_classes = array_map( 'sanitize_html_class', array_filter( (array) $trigger_classes ) );

		wp_localize_script(
			'wwcs-login-popup',
			'wwcsLoginPopup',
			array(
				'myAccountUrl'   => wc_get_page_permalink( 'myaccount' ),
				'autoOpenTab'    => $auto_open_tab,
				'triggerClasses' => $trigger_classes,
			)
		);
	}

	public static function render_modal() {
		if ( is_user_logged_in() ) {
			return;
		}

		$registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
		?>
		<div id="wwcs-login-popup" class="wwcs-login-popup">
			<div class="wwcs-lp-overlay"></div>
			<div class="wwcs-lp-modal" role="dialog" aria-modal="true">
				<button type="button" class="wwcs-lp-close" aria-label="<?php esc_attr_e( 'Close', 'webcasata-woocommerce-suite' ); ?>">&times;</button>

				<div class="wwcs-lp-tabs">
					<button type="button" class="wwcs-lp-tab wwcs-lp-tab-active" data-tab="login">
						<?php esc_html_e( 'Login', 'webcasata-woocommerce-suite' ); ?>
					</button>
					<?php if ( $registration_enabled ) : ?>
						<button type="button" class="wwcs-lp-tab" data-tab="register">
							<?php esc_html_e( 'Register', 'webcasata-woocommerce-suite' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<div class="wwcs-lp-notices">
					<?php wc_print_notices(); ?>
				</div>

				<div class="wwcs-lp-forms">
					<?php self::render_forms(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_forms() {
		ob_start();
		wc_get_template( 'myaccount/form-login.php' );
		$html = ob_get_clean();

		if ( ! $html ) {
			return; // e.g. a theme override changed the template unexpectedly — fail quietly rather than output nothing useful.
		}

		// WooCommerce's own template wraps the login form in a div with
		// class "u-column1 col-1" and (if registration is enabled) the
		// register form in "u-column2 col-2" — both normally shown side by
		// side. We only rename those two wrapper classes to add our own
		// tab-pane classes; the forms, fields, nonces, and hooks inside are
		// untouched, so WC_Form_Handler processes submissions exactly as it
		// would on the real My Account page.
		$html = str_replace( 'u-column1 col-1', 'u-column1 col-1 wwcs-lp-pane wwcs-lp-pane-active', $html );
		$html = str_replace( 'u-column2 col-2', 'u-column2 col-2 wwcs-lp-pane', $html );

		echo $html; // phpcs: verbatim output of a WooCommerce core template, only class attributes were touched above.
	}
}
