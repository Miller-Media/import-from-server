<?php
/**
Plugin Name: Miller Media Server File Import
Description: Browse files on the server and import them into the WordPress Media Library.
Author: Miller Media
Author URI: https://mattmiller.ai
Version: 1.0.0
Requires PHP: 8.1
Tested up to: 7.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: miller-media-server-file-import
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MMSFI_PLUGIN_VERSION', '1.0.0' );
define( 'MMSFI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMSFI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// REST API + dependencies must load outside is_admin() because REST requests don't pass is_admin().
require_once MMSFI_PLUGIN_PATH . 'inc/class-mmsfi-file-browser.php';
require_once MMSFI_PLUGIN_PATH . 'inc/class-mmsfi-importer.php';
require_once MMSFI_PLUGIN_PATH . 'inc/class-mmsfi-rest-api.php';

new MMSFI_REST_API();

register_activation_hook(
	__FILE__,
	function () {
		if ( ! get_option( 'mmsfi_activated_on' ) ) {
			update_option( 'mmsfi_activated_on', time() );
		}
		if ( false === get_option( 'mmsfi_settings' ) ) {
			update_option(
				'mmsfi_settings',
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

	require_once MMSFI_PLUGIN_PATH . 'inc/class-mmsfi-settings.php';
	require_once MMSFI_PLUGIN_PATH . 'inc/class-mmsfi-plugin.php';
	require_once MMSFI_PLUGIN_PATH . 'inc/class-mmsfi-review-notice.php';

	add_filter(
		'plugin_action_links_' . plugin_basename( __FILE__ ),
		function ( $links ) {
			$import_link   = '<a href="' . esc_url( admin_url( 'upload.php?page=miller-media-server-file-import' ) ) . '">'
				. esc_html__( 'Import Files', 'miller-media-server-file-import' ) . '</a>';
			$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=mmsfi-settings' ) ) . '">'
				. esc_html__( 'Settings', 'miller-media-server-file-import' ) . '</a>';
			array_unshift( $links, $import_link, $settings_link );
			return $links;
		}
	);

	new MMSFI_Plugin();
	new MMSFI_Settings();
	new MMSFI_ReviewNotice(
		'Miller Media Server File Import',
		'miller-media-server-file-import',
		'mmsfi_activated_on',
		'miller-media-server-file-import',
		'',
		'dashicons-upload'
	);
}
