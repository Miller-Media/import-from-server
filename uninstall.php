<?php
/**
 * Uninstall handler for Import Files from Server.
 *
 * Removes plugin options and user meta when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'ifse_settings' );
delete_option( 'ifse_activated_on' );

// Remove review-dismissed user meta for all users.
delete_metadata( 'user', 0, 'import-files-from-server_review_dismissed', '', true );
