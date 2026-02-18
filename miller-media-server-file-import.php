<?php
/**
Plugin Name: Miller Media Server File Import
Description: Browse files on the server and import them into the WordPress Media Library.
Author: Miller Media
Author URI: https://mattmiller.ai
Version: 1.0.0
Requires PHP: 8.1
Tested up to: 6.9
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: miller-media-server-file-import
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IFSE_PLUGIN_VERSION', '1.0.0' );
define( 'IFSE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'IFSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// REST API + dependencies must load outside is_admin() because REST requests don't pass is_admin().
require_once IFSE_PLUGIN_PATH . 'inc/class-ifse-file-browser.php';
require_once IFSE_PLUGIN_PATH . 'inc/class-ifse-importer.php';
require_once IFSE_PLUGIN_PATH . 'inc/class-ifse-rest-api.php';

new IFSE_REST_API();

register_activation_hook(
	__FILE__,
	function () {
		if ( ! get_option( 'ifse_activated_on' ) ) {
			update_option( 'ifse_activated_on', time() );
		}
		if ( false === get_option( 'ifse_settings' ) ) {
			update_option(
				'ifse_settings',
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

	require_once IFSE_PLUGIN_PATH . 'inc/class-ifse-settings.php';
	require_once IFSE_PLUGIN_PATH . 'inc/class-ifse-plugin.php';
	require_once IFSE_PLUGIN_PATH . 'inc/class-ifse-review-notice.php';

	add_filter(
		'plugin_action_links_' . plugin_basename( __FILE__ ),
		function ( $links ) {
			$import_link   = '<a href="' . esc_url( admin_url( 'upload.php?page=miller-media-server-file-import' ) ) . '">'
				. esc_html__( 'Import Files', 'miller-media-server-file-import' ) . '</a>';
			$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=ifse-settings' ) ) . '">'
				. esc_html__( 'Settings', 'miller-media-server-file-import' ) . '</a>';
			array_unshift( $links, $import_link, $settings_link );
			return $links;
		}
	);

	new IFSE_Plugin();
	new IFSE_Settings();
	new IFSE_ReviewNotice(
		'Miller Media Server File Import',
		'miller-media-server-file-import',
		'ifse_activated_on',
		'miller-media-server-file-import',
		'',
		'dashicons-upload'
	);
}
