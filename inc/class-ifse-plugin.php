<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Main plugin controller: admin menu, enqueuing, page rendering.
 */
class IFSE_Plugin {

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
			__( 'Import Files from Server', 'miller-media-server-file-import' ),
			__( 'Import Files from Server', 'miller-media-server-file-import' ),
			'upload_files',
			'miller-media-server-file-import',
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
			'ifse-admin-css',
			IFSE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			IFSE_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ifse-admin-js',
			IFSE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-api-request' ),
			IFSE_PLUGIN_VERSION,
			true
		);

		$settings  = get_option( 'ifse_settings', array() );
		$root_path = isset( $settings['root_path'] ) && '' !== $settings['root_path']
			? $settings['root_path']
			: WP_CONTENT_DIR;

		wp_localize_script(
			'ifse-admin-js',
			'ifsData',
			array(
				'restUrl'  => rest_url( IFSE_REST_API::NAMESPACE ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'rootPath' => $root_path,
				'i18n'     => array(
					'selectAll'       => __( 'Select All', 'miller-media-server-file-import' ),
					'deselectAll'     => __( 'Deselect All', 'miller-media-server-file-import' ),
					'importSelected'  => __( 'Import Selected', 'miller-media-server-file-import' ),
					'importing'       => __( 'Importing...', 'miller-media-server-file-import' ),
					'imported'        => __( 'Imported', 'miller-media-server-file-import' ),
					'failed'          => __( 'Failed', 'miller-media-server-file-import' ),
					'noFiles'         => __( 'No files found in this directory.', 'miller-media-server-file-import' ),
					'loading'         => __( 'Loading...', 'miller-media-server-file-import' ),
					'alreadyImported' => __( 'Already imported', 'miller-media-server-file-import' ),
					'viewInLibrary'   => __( 'View', 'miller-media-server-file-import' ),
					'errorLoading'    => __( 'Error loading directory.', 'miller-media-server-file-import' ),
					'complete'        => __( 'Import complete.', 'miller-media-server-file-import' ),
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
			<h1><?php esc_html_e( 'Import Files from Server', 'miller-media-server-file-import' ); ?></h1>
			<div id="ifs-app">
				<div id="ifs-breadcrumbs" class="ifs-breadcrumbs"></div>
				<div id="ifs-toolbar" class="ifs-toolbar">
					<label class="ifs-select-all-label">
						<input type="checkbox" id="ifs-select-all" />
						<span id="ifs-select-all-text"><?php esc_html_e( 'Select All', 'miller-media-server-file-import' ); ?></span>
					</label>
					<button id="ifs-import-btn" class="button button-primary" disabled>
						<?php esc_html_e( 'Import Selected', 'miller-media-server-file-import' ); ?> (<span id="ifs-selected-count">0</span>)
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
					<p class="ifs-loading"><?php esc_html_e( 'Loading...', 'miller-media-server-file-import' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}
