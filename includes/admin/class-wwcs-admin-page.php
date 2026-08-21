<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWCS_Admin_Page {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Webcasata WooCommerce Suite', 'webcasata-woocommerce-suite' ),
			__( 'Webcasata Suite', 'webcasata-woocommerce-suite' ),
			'manage_woocommerce',
			'wwcs-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'wwcs-settings' ) ) {
			return;
		}
		wp_enqueue_style( 'wwcs-admin', WWCS_URL . 'assets/css/admin.css', array(), WWCS_VERSION );
		wp_enqueue_script( 'wwcs-admin', WWCS_URL . 'assets/js/admin.js', array( 'jquery' ), WWCS_VERSION, true );
	}

	public static function maybe_save() {
		if ( empty( $_POST['wwcs_save'] ) ) {
			return;
		}
		if ( ! isset( $_POST['wwcs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wwcs_nonce'] ) ), 'wwcs_save_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$posted = array();
		if ( isset( $_POST['wwcs'] ) && is_array( $_POST['wwcs'] ) ) {
			$posted = array_map( 'sanitize_text_field', wp_unslash( $_POST['wwcs'] ) );
		}

		$submitted_tab = isset( $_POST['wwcs_current_tab'] ) ? sanitize_key( wp_unslash( $_POST['wwcs_current_tab'] ) ) : null;

		WWCS_Settings::save( $posted, $submitted_tab );
		WWCS_Settings::save_delete_on_uninstall( ! empty( $_POST['wwcs_delete_on_uninstall'] ) );

		add_action( 'admin_notices', array( __CLASS__, 'saved_notice' ) );
	}

	public static function saved_notice() {
		echo '<div class="notice notice-success is-dismissible"><p>';
		esc_html_e( 'Webcasata WooCommerce Suite settings saved.', 'webcasata-woocommerce-suite' );
		echo '</p></div>';
	}

	public static function render_page() {
		$tabs = WWCS_Settings::$registry;

		$current_tab = ( isset( $_GET['tab'] ) && isset( $tabs[ sanitize_key( wp_unslash( $_GET['tab'] ) ) ] ) )
			? sanitize_key( wp_unslash( $_GET['tab'] ) )
			: array_key_first( $tabs );
		?>
		<div class="wrap wwcs-wrap">
			<h1><?php esc_html_e( 'Webcasata WooCommerce Suite', 'webcasata-woocommerce-suite' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Turn on only the features you need. Anything left off adds no CSS, JS, or database queries to your site.', 'webcasata-woocommerce-suite' ); ?>
			</p>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wwcs-settings', 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>"
						class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<form method="post">
				<?php wp_nonce_field( 'wwcs_save_settings', 'wwcs_nonce' ); ?>
				<input type="hidden" name="wwcs_current_tab" value="<?php echo esc_attr( $current_tab ); ?>" />

				<div class="wwcs-features">
					<?php foreach ( $tabs[ $current_tab ]['features'] as $key => $feature ) :
						$enabled     = WWCS_Settings::is_enabled( $key );
						$coming_soon = ! empty( $feature['coming_soon'] );
						?>
						<div class="wwcs-feature-group">
							<div class="wwcs-feature-row <?php echo $coming_soon ? 'wwcs-coming-soon' : ''; ?>">
								<label class="wwcs-switch">
									<input
										type="checkbox"
										name="wwcs[<?php echo esc_attr( $key ); ?>]"
										value="1"
										<?php checked( $enabled ); ?>
										<?php disabled( $coming_soon ); ?>
									/>
									<span class="wwcs-slider"></span>
								</label>
								<div class="wwcs-feature-text">
									<strong><?php echo esc_html( $feature['label'] ); ?></strong>
									<?php if ( $coming_soon ) : ?>
										<span class="wwcs-badge-soon"><?php esc_html_e( 'Coming soon', 'webcasata-woocommerce-suite' ); ?></span>
									<?php endif; ?>
									<p><?php echo esc_html( $feature['description'] ); ?></p>
								</div>
							</div>

							<?php if ( ! empty( $feature['fields'] ) ) : ?>
								<div class="wwcs-feature-fields" style="display: <?php echo $enabled ? 'block' : 'none'; ?>;">
									<div class="wwcs-field-grid">
										<?php foreach ( $feature['fields'] as $field_key => $field ) :
											$field_value = WWCS_Settings::get_field_value( $field_key, isset( $field['default'] ) ? $field['default'] : '' );
											$type        = isset( $field['type'] ) ? $field['type'] : 'text';
											$input_type  = 'color' === $type ? 'color' : ( 'number' === $type ? 'number' : 'text' );
											?>
											<label class="wwcs-field-label">
												<?php echo esc_html( $field['label'] ); ?>
												<span class="wwcs-field-input-row">
													<input
														type="<?php echo esc_attr( $input_type ); ?>"
														name="wwcs[<?php echo esc_attr( $field_key ); ?>]"
														value="<?php echo esc_attr( $field_value ); ?>"
														class="wwcs-field-input"
														<?php echo 'number' === $type ? 'min="0" step="1"' : ''; ?>
													/>
													<?php if ( ! empty( $field['suffix'] ) ) : ?>
														<span class="wwcs-field-suffix"><?php echo esc_html( $field['suffix'] ); ?></span>
													<?php endif; ?>
												</span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="wwcs-uninstall-box">
					<label>
						<input
							type="checkbox"
							name="wwcs_delete_on_uninstall"
							value="1"
							<?php checked( WWCS_Settings::get_delete_on_uninstall() ); ?>
						/>
						<?php esc_html_e( 'Delete all Webcasata WooCommerce Suite settings when this plugin is deleted', 'webcasata-woocommerce-suite' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Left unchecked (default), your feature settings are kept in the database so reinstalling the plugin later restores your configuration. Only checked settings are removed on uninstall — this never touches your WooCommerce products, orders, or customer data.', 'webcasata-woocommerce-suite' ); ?>
					</p>
				</div>

				<p class="submit">
					<button type="submit" name="wwcs_save" value="1" class="button button-primary">
						<?php esc_html_e( 'Save Changes', 'webcasata-woocommerce-suite' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
