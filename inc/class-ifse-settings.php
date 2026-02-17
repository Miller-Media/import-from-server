<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Settings page for Import Files from Server.
 */
class IFSE_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'addSettingsPage' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
	}

	/**
	 * Add settings page under Settings menu.
	 */
	public function addSettingsPage() {
		add_options_page(
			__( 'Import Files from Server Settings', 'import-files-from-server' ),
			__( 'Import Files from Server', 'import-files-from-server' ),
			'manage_options',
			'ifse-settings',
			array( $this, 'renderSettingsPage' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function registerSettings() {
		register_setting(
			'ifse_settings_group',
			'ifse_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitizeSettings' ),
				'default'           => array(
					'root_path'       => WP_CONTENT_DIR,
					'import_behavior' => 'copy',
					'allowed_types'   => '',
				),
			)
		);

		add_settings_section(
			'ifse_general_section',
			__( 'General Settings', 'import-files-from-server' ),
			'__return_null',
			'ifse-settings'
		);

		add_settings_field(
			'ifse_root_path',
			__( 'Root Directory', 'import-files-from-server' ),
			array( $this, 'renderRootPathField' ),
			'ifse-settings',
			'ifse_general_section'
		);

		add_settings_field(
			'ifse_import_behavior',
			__( 'Import Behavior', 'import-files-from-server' ),
			array( $this, 'renderImportBehaviorField' ),
			'ifse-settings',
			'ifse_general_section'
		);

		add_settings_field(
			'ifse_allowed_types',
			__( 'Allowed File Types', 'import-files-from-server' ),
			array( $this, 'renderAllowedTypesField' ),
			'ifse-settings',
			'ifse_general_section'
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized output.
	 */
	public function sanitizeSettings( $input ) {
		$sanitized = array();

		$sanitized['root_path'] = isset( $input['root_path'] )
			? sanitize_text_field( untrailingslashit( $input['root_path'] ) )
			: WP_CONTENT_DIR;

		// Validate that root path exists.
		if ( '' !== $sanitized['root_path'] && ! is_dir( $sanitized['root_path'] ) ) {
			add_settings_error(
				'ifse_settings',
				'ifse_invalid_root',
				__( 'The root directory does not exist. Setting reverted to default.', 'import-files-from-server' )
			);
			$sanitized['root_path'] = WP_CONTENT_DIR;
		}

		$sanitized['import_behavior'] = isset( $input['import_behavior'] ) && 'move' === $input['import_behavior']
			? 'move'
			: 'copy';

		$sanitized['allowed_types'] = isset( $input['allowed_types'] )
			? sanitize_text_field( $input['allowed_types'] )
			: '';

		return $sanitized;
	}

	/**
	 * Render the root path field.
	 */
	public function renderRootPathField() {
		$settings = get_option( 'ifse_settings', array() );
		$value    = isset( $settings['root_path'] ) ? $settings['root_path'] : WP_CONTENT_DIR;
		?>
		<input type="text" name="ifse_settings[root_path]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'The top-level directory users can browse. Defaults to wp-content.', 'import-files-from-server' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the import behavior field.
	 */
	public function renderImportBehaviorField() {
		$settings = get_option( 'ifse_settings', array() );
		$value    = isset( $settings['import_behavior'] ) ? $settings['import_behavior'] : 'copy';
		?>
		<select name="ifse_settings[import_behavior]">
			<option value="copy" <?php selected( $value, 'copy' ); ?>>
				<?php esc_html_e( 'Copy (keep original file)', 'import-files-from-server' ); ?>
			</option>
			<option value="move" <?php selected( $value, 'move' ); ?>>
				<?php esc_html_e( 'Move (delete original file)', 'import-files-from-server' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'How to handle files outside the uploads directory during import.', 'import-files-from-server' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the allowed types field.
	 */
	public function renderAllowedTypesField() {
		$settings = get_option( 'ifse_settings', array() );
		$value    = isset( $settings['allowed_types'] ) ? $settings['allowed_types'] : '';
		?>
		<input type="text" name="ifse_settings[allowed_types]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Comma-separated list of allowed file extensions (e.g., jpg,png,pdf). Leave empty to allow all WordPress-permitted types.', 'import-files-from-server' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function renderSettingsPage() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Files from Server Settings', 'import-files-from-server' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ifse_settings_group' );
				do_settings_sections( 'ifse-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
