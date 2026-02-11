<?php
/**
 * Uninstall handler for Import from Server.
 *
 * Removes plugin options and user meta when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'ifs_settings' );
delete_option( 'ifs_activated_on' );

// Remove review-dismissed user meta for all users.
delete_metadata( 'user', 0, 'import-from-server_review_dismissed', '', true );
