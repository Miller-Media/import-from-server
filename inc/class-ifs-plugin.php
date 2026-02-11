<?php
/**
 * Main plugin controller: admin menu, enqueuing, page rendering.
 */
class IFS_Plugin {

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private $hook_suffix;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'addAdminPage' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	/**
	 * Register the admin page under Media.
	 */
	public function addAdminPage() {
		$this->hook_suffix = add_media_page(
			__( 'Import from Server', 'import-from-server' ),
			__( 'Import from Server', 'import-from-server' ),
			'upload_files',
			'import-from-server',
			array( $this, 'renderAdminPage' )
		);
	}

	/**
	 * Enqueue CSS and JS only on the plugin's admin page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueueAssets( $hook ) {
		if ( $this->hook_suffix !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ifs-admin-css',
			IFS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			IFS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ifs-admin-js',
			IFS_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-api-request' ),
			IFS_PLUGIN_VERSION,
			true
		);

		$settings  = get_option( 'ifs_settings', array() );
		$root_path = isset( $settings['root_path'] ) && '' !== $settings['root_path']
			? $settings['root_path']
			: WP_CONTENT_DIR;

		wp_localize_script(
			'ifs-admin-js',
			'ifsData',
			array(
				'restUrl'  => rest_url( IFS_REST_API::NAMESPACE ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'rootPath' => $root_path,
				'i18n'     => array(
					'selectAll'       => __( 'Select All', 'import-from-server' ),
					'deselectAll'     => __( 'Deselect All', 'import-from-server' ),
					'importSelected'  => __( 'Import Selected', 'import-from-server' ),
					'importing'       => __( 'Importing...', 'import-from-server' ),
					'imported'        => __( 'Imported', 'import-from-server' ),
					'failed'          => __( 'Failed', 'import-from-server' ),
					'noFiles'         => __( 'No files found in this directory.', 'import-from-server' ),
					'loading'         => __( 'Loading...', 'import-from-server' ),
					'alreadyImported' => __( 'Already imported', 'import-from-server' ),
					'errorLoading'    => __( 'Error loading directory.', 'import-from-server' ),
					'complete'        => __( 'Import complete.', 'import-from-server' ),
				),
			)
		);
	}

	/**
	 * Render the admin page shell.
	 */
	public function renderAdminPage() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import from Server', 'import-from-server' ); ?></h1>
			<div id="ifs-app">
				<div id="ifs-breadcrumbs" class="ifs-breadcrumbs"></div>
				<div id="ifs-toolbar" class="ifs-toolbar">
					<label class="ifs-select-all-label">
						<input type="checkbox" id="ifs-select-all" />
						<span id="ifs-select-all-text"><?php esc_html_e( 'Select All', 'import-from-server' ); ?></span>
					</label>
					<button id="ifs-import-btn" class="button button-primary" disabled>
						<?php esc_html_e( 'Import Selected', 'import-from-server' ); ?> (<span id="ifs-selected-count">0</span>)
					</button>
				</div>
				<div id="ifs-progress" class="ifs-progress" style="display:none;">
					<div class="ifs-progress-bar-wrap">
						<div id="ifs-progress-bar" class="ifs-progress-bar" style="width:0%"></div>
					</div>
					<div id="ifs-progress-text" class="ifs-progress-text"></div>
					<div id="ifs-import-log" class="ifs-import-log"></div>
				</div>
				<div id="ifs-file-list" class="ifs-file-list">
					<p class="ifs-loading"><?php esc_html_e( 'Loading...', 'import-from-server' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}
