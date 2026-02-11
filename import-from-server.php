<?php
/**
Plugin Name: Import from Server
Description: Browse files on the server and import them into the WordPress Media Library.
Author: Miller Media
Author URI: https://www.millermedia.io
Version: 1.0.0
Requires PHP: 8.1
Tested up to: 6.9
License: GPLv2
Text Domain: import-from-server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IFS_PLUGIN_VERSION', '1.0.0' );
define( 'IFS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'IFS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'import-from-server', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

// REST API + dependencies must load outside is_admin() because REST requests don't pass is_admin().
require_once IFS_PLUGIN_PATH . 'inc/class-ifs-file-browser.php';
require_once IFS_PLUGIN_PATH . 'inc/class-ifs-importer.php';
require_once IFS_PLUGIN_PATH . 'inc/class-ifs-rest-api.php';

new IFS_REST_API();

register_activation_hook(
	__FILE__,
	function () {
		if ( ! get_option( 'ifs_activated_on' ) ) {
			update_option( 'ifs_activated_on', time() );
		}
		if ( false === get_option( 'ifs_settings' ) ) {
			update_option(
				'ifs_settings',
				array(
					'root_path'       => WP_CONTENT_DIR,
					'import_behavior' => 'copy',
					'allowed_types'   => '',
				)
			);
		}
	}
);

if ( is_admin() ) {

	require_once IFS_PLUGIN_PATH . 'inc/class-ifs-settings.php';
	require_once IFS_PLUGIN_PATH . 'inc/class-ifs-plugin.php';
	require_once IFS_PLUGIN_PATH . 'inc/class-ifs-review-notice.php';

	new IFS_Plugin();
	new IFS_Settings();
	new IFS_ReviewNotice(
		'Import from Server',
		'import-from-server',
		'ifs_activated_on',
		'import-from-server',
		'',
		'dashicons-upload'
	);
}
