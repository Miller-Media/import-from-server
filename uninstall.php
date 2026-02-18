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
delete_option( 'mmsfi_settings' );
delete_option( 'mmsfi_activated_on' );

// Remove review-dismissed user meta for all users.
delete_metadata( 'user', 0, 'miller-media-server-file-import_review_dismissed', '', true );
