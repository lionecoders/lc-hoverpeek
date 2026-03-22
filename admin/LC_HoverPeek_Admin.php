<?php
/**
 * Admin settings for LC HoverPeek.
 *
 * @package LC_HoverPeek
 */

namespace LCHoverPeek\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LC_HoverPeek_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'lcho_enqueue_admin_scripts' ] );
		add_action( 'wp_ajax_lcho_reset_settings', [ $this, 'lcho_reset_settings' ] );
		add_action( 'admin_menu', [ $this, 'lcho_register_menu' ] );
		add_action( 'admin_init', [ $this, 'lcho_register_settings' ] );
	}

	public function lcho_enqueue_admin_scripts( $hook ) {
		if ( 'toplevel_page_lc-hoverpeek-settings' !== $hook ) {
			return;
		}
		
		wp_enqueue_style(
			'lcho-admin-style',
			LCHO_PLUGIN_URL . 'admin/admin.css',
			[],
			LCHO_VERSION
		);

		wp_enqueue_script(
			'lcho-admin-script',
			LCHO_PLUGIN_URL . 'admin/admin.js',
			[ 'jquery' ],
			LCHO_VERSION,
			true
		);

		wp_localize_script(
			'lcho-admin-script',
			'lcho_admin',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lcho_reset_nonce' ),
				'confirm'  => __( 'Are you sure you want to reset all settings to default?', 'lc-hoverpeek' ),
			]
		);
	}

	public function lcho_reset_settings() {
		check_ajax_referer( 'lcho_reset_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$defaults = [
			'lcho_bg_color'        => '#111111',
			'lcho_title_color'     => '#ffffff',
			'lcho_excerpt_color'   => '#ffffff',
			'lcho_link_color'      => '#4da3ff',
			'lcho_enable_internal' => 1,
			'lcho_enable_external' => 0,
			'lcho_types'           => [ 'post' ],
		];

		foreach ( $defaults as $option => $value ) {
			update_option( $option, $value );
		}

		wp_send_json_success();
	}

	public function lcho_register_menu() {
		add_menu_page(
			__( 'Hover Preview Settings', 'lc-hoverpeek' ),
			__( 'Hover Preview', 'lc-hoverpeek' ),
			'manage_options',
			'lc-hoverpeek-settings',
			[ $this, 'lcho_settings_page' ],
			'dashicons-visibility'
		);
	}

	public function lcho_register_settings() {
		register_setting( 'lcho_settings_group', 'lcho_bg_color', 'sanitize_hex_color' );
		register_setting( 'lcho_settings_group', 'lcho_title_color', 'sanitize_hex_color' );
		register_setting( 'lcho_settings_group', 'lcho_excerpt_color', 'sanitize_hex_color' );
		register_setting( 'lcho_settings_group', 'lcho_link_color', 'sanitize_hex_color' );
		register_setting( 'lcho_settings_group', 'lcho_enable_internal', 'absint' );
		register_setting( 'lcho_settings_group', 'lcho_enable_external', 'absint' );
		register_setting( 'lcho_settings_group', 'lcho_types', [ $this, 'lcho_sanitize_types' ] );
	}

	public function lcho_sanitize_types( $input ) {
		if ( ! is_array( $input ) ) {
			return [];
		}
		return array_map( 'sanitize_text_field', $input );
	}

	public function lcho_settings_page() {
		?>
		<div class="wrap lcho-settings-wrap">
			<header class="lcho-header">
				<div class="lcho-header-title">
					<h1><?php esc_html_e( 'Hover Preview Settings', 'lc-hoverpeek' ); ?></h1>
					<p><?php esc_html_e( 'Configure how link previews appear across your site.', 'lc-hoverpeek' ); ?></p>
				</div>
			</header>

			<div class="lcho-info">
				<div class="lcho-info-header" id="lcho-info-toggle">
					<div class="lcho-info-header-left">
						<span class="dashicons dashicons-info"></span>
						<h2><?php esc_html_e( 'How to Use', 'lc-hoverpeek' ); ?></h2>
					</div>
					<span class="dashicons dashicons-arrow-down-alt2 lcho-toggle-icon"></span>
				</div>
				<div class="lcho-info-content">
					<div class="lcho-info-grid">
						<div class="lcho-info-item">
							<h3><span class="dashicons dashicons-welcome-view-site"></span> <?php esc_html_e( 'Hover to Preview', 'lc-hoverpeek' ); ?></h3>
							<p><?php esc_html_e( 'Once enabled, simply hover over any link in your content. A preview card will automatically appear showing the page/post title, excerpt, and image.', 'lc-hoverpeek' ); ?></p>
						</div>
						<div class="lcho-info-item">
							<h3><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Customization', 'lc-hoverpeek' ); ?></h3>
							<p><?php esc_html_e( 'Use the Appearance Settings below to match the preview card to your site\'s branding. You can change colors for backgrounds, titles, text, and links.', 'lc-hoverpeek' ); ?></p>
						</div>
						<div class="lcho-info-item">
							<h3><span class="dashicons dashicons-admin-site"></span> <?php esc_html_e( 'Internal & External', 'lc-hoverpeek' ); ?></h3>
							<p><?php esc_html_e( 'Internal previews are instant. External previews fetch metadata automatically. You can choose to enable or disable both types independently.', 'lc-hoverpeek' ); ?></p>
						</div>
						<div class="lcho-info-item">
							<h3><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Selective Activation', 'lc-hoverpeek' ); ?></h3>
							<p><?php esc_html_e( 'Choose exactly which post types (Posts, Pages, etc.) should have hover previews enabled using the checkboxes in the Display Settings.', 'lc-hoverpeek' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			<form method="post" action="options.php">
				<?php settings_fields( 'lcho_settings_group' ); ?>

				<!-- Display Settings Card -->
				<div class="lcho-card">
					<div class="lcho-card-header">
						<h2><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Display Settings', 'lc-hoverpeek' ); ?></h2>
					</div>
					<div class="lcho-card-body">
						<div class="lcho-setting-row">
							<div class="lcho-setting-info">
								<h3><?php esc_html_e( 'Enable Internal Links', 'lc-hoverpeek' ); ?></h3>
								<p><?php esc_html_e( 'Show rich hover previews for all links pointing to your own domain.', 'lc-hoverpeek' ); ?></p>
							</div>
							<label class="lcho-switch">
								<input type="checkbox" name="lcho_enable_internal" value="1" <?php checked( 1, get_option( 'lcho_enable_internal', 1 ) ); ?>>
								<span class="lcho-slider"></span>
							</label>
						</div>

						<div class="lcho-setting-row">
							<div class="lcho-setting-info">
								<h3><?php esc_html_e( 'Enable External Links', 'lc-hoverpeek' ); ?></h3>
								<p><?php esc_html_e( 'Fetch metadata and show previews for third-party websites.', 'lc-hoverpeek' ); ?></p>
							</div>
							<label class="lcho-switch">
								<input type="checkbox" name="lcho_enable_external" value="1" <?php checked( 1, get_option( 'lcho_enable_external', 0 ) ); ?>>
								<span class="lcho-slider"></span>
							</label>
						</div>

						<div class="lcho-setting-row is-top">
							<div class="lcho-setting-info">
								<h3><?php esc_html_e( 'Supported Post Types', 'lc-hoverpeek' ); ?></h3>
								<p><?php esc_html_e( 'Select where you want the hover previews to be active.', 'lc-hoverpeek' ); ?></p>
							</div>
							<div class="lcho-post-types-list">
								<?php
								$public_types   = get_post_types( [ 'public' => true ], 'objects' );
								$builtin_types  = array_keys( get_post_types( [ 'public' => true, '_builtin' => true ] ) );
								$selected_types = get_option( 'lcho_types', [ 'post' ] );

								if ( ! is_array( $selected_types ) ) {
									$selected_types = [];
								}

								foreach ( $public_types as $type ) :
									$is_builtin = $type->_builtin;
									?>
									<div class="lcho-post-type-item <?php echo ! $is_builtin ? 'is-pro' : ''; ?>">
										<label>
											<input type="checkbox" name="lcho_types[]" 
												value="<?php echo esc_attr( $type->name ); ?>" 
												<?php checked( in_array( $type->name, $selected_types, true ) ); ?>
												<?php echo ! $is_builtin ? 'disabled' : ''; ?>> 
											<span class="lcho-checkbox-label">
												<?php echo esc_html( $type->label ); ?>
												<?php echo ! $is_builtin ? ' <span class="lcho-pro-badge">(Pro)</span>' : ''; ?>
											</span>
										</label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Appearance Settings Card -->
				<div class="lcho-card">
					<div class="lcho-card-header">
						<h2><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Appearance Settings', 'lc-hoverpeek' ); ?></h2>
					</div>
					<div class="lcho-card-body">
						<div class="lcho-grid">
							<?php
							$colors = [
								'lcho_bg_color'      => [
									'label'   => __( 'Popup Background Color', 'lc-hoverpeek' ),
									'default' => '#111111',
								],
								'lcho_title_color'   => [
									'label'   => __( 'Title Color', 'lc-hoverpeek' ),
									'default' => '#ffffff',
								],
								'lcho_excerpt_color' => [
									'label'   => __( 'Excerpt Color', 'lc-hoverpeek' ),
									'default' => '#ffffff',
								],
								'lcho_link_color'    => [
									'label'   => __( 'Link Color', 'lc-hoverpeek' ),
									'default' => '#4da3ff',
								],
							];

							foreach ( $colors as $name => $data ) :
								$val = get_option( $name, $data['default'] );
								?>
								<div class="lcho-color-field">
									<label><?php echo esc_html( $data['label'] ); ?></label>
									<div class="lcho-color-input-wrap">
										<div class="lcho-color-preview" style="background-color: <?php echo esc_attr( $val ); ?>;">
											<input type="color" value="<?php echo esc_attr( $val ); ?>">
										</div>
										<input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( strtoupper( $val ) ); ?>">
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="lcho-actions">
					<div class="lcho-primary-actions">
						<button type="submit" class="lcho-btn-save">
							<span class="dashicons dashicons-saved"></span>
							<?php esc_html_e( 'Save Changes', 'lc-hoverpeek' ); ?>
						</button>
						<button type="button" class="lcho-btn-undo is-disabled">
							<span class="dashicons dashicons-undo"></span>
						</button>
					</div>
					<div class="lcho-secondary-actions">
						<a href="#" class="lcho-btn-reset"><?php esc_html_e( 'Reset to Defaults', 'lc-hoverpeek' ); ?></a>
					</div>
				</div>
			</form>
		</div>
		<!-- Custom Reset Modal -->
		<div id="lcho-reset-modal" class="lcho-modal">
			<div class="lcho-modal-content">
				<div class="lcho-modal-header">
					<h3><?php esc_html_e( 'Reset Settings', 'lc-hoverpeek' ); ?></h3>
					<span class="lcho-modal-close">&times;</span>
				</div>
				<div class="lcho-modal-body">
					<p><?php esc_html_e( 'Are you sure you want to reset all settings to their default values? This action cannot be undone.', 'lc-hoverpeek' ); ?></p>
				</div>
				<div class="lcho-modal-footer">
					<button type="button" class="lcho-btn-cancel"><?php esc_html_e( 'Cancel', 'lc-hoverpeek' ); ?></button>
					<button type="button" class="lcho-btn-confirm-reset"><?php esc_html_e( 'Yes, Reset Defaults', 'lc-hoverpeek' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}
