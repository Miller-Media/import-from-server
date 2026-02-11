<?php
/**
 * File importer: imports server files into the WordPress Media Library.
 */
class IFS_Importer {

	/**
	 * Import a file into the Media Library.
	 *
	 * @param string $filepath Absolute path to the file.
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public static function importFile( $filepath ) {
		// Validate path is within allowed root.
		$validated = IFS_FileBrowser::validatePath( $filepath );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		if ( ! is_file( $validated ) ) {
			return new WP_Error( 'ifs_not_file', __( 'The path is not a file.', 'import-from-server' ) );
		}

		if ( ! is_readable( $validated ) ) {
			return new WP_Error( 'ifs_not_readable', __( 'The file is not readable.', 'import-from-server' ) );
		}

		// Check MIME type.
		$filetype = wp_check_filetype_and_ext( $validated, basename( $validated ) );
		if ( empty( $filetype['type'] ) ) {
			return new WP_Error( 'ifs_invalid_type', __( 'This file type is not allowed.', 'import-from-server' ) );
		}

		// Check if importable (including allowed_types setting).
		if ( ! IFS_FileBrowser::isFileImportable( $validated ) ) {
			return new WP_Error( 'ifs_type_not_allowed', __( 'This file type is not in the allowed types list.', 'import-from-server' ) );
		}

		// Check for duplicates.
		if ( IFS_FileBrowser::isAlreadyImported( $validated ) ) {
			return new WP_Error( 'ifs_already_imported', __( 'This file has already been imported.', 'import-from-server' ) );
		}

		$upload_dir  = wp_upload_dir();
		$upload_base = $upload_dir['basedir'];
		$filename    = basename( $validated );

		// Determine if file is already in uploads directory.
		$in_uploads = ( 0 === strpos( $validated, $upload_base ) );

		if ( $in_uploads ) {
			// File is already in uploads — use it in place.
			$dest_file    = $validated;
			$relative_url = ltrim( str_replace( $upload_base, '', $validated ), '/' );
		} else {
			// File is outside uploads — copy or move based on settings.
			$settings = get_option( 'ifs_settings', array() );
			$behavior = isset( $settings['import_behavior'] ) ? $settings['import_behavior'] : 'copy';

			// Generate unique filename in uploads.
			$dest_file = trailingslashit( $upload_dir['path'] ) . wp_unique_filename( $upload_dir['path'], $filename );

			if ( 'move' === $behavior ) {
				$result = rename( $validated, $dest_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
			} else {
				$result = copy( $validated, $dest_file );
			}

			if ( ! $result ) {
				return new WP_Error( 'ifs_copy_failed', __( 'Failed to copy/move file to uploads directory.', 'import-from-server' ) );
			}

			$relative_url = ltrim( str_replace( $upload_base, '', $dest_file ), '/' );
		}

		// Create the attachment post.
		$attachment = array(
			'guid'           => trailingslashit( $upload_dir['baseurl'] ) . $relative_url,
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $dest_file );

		if ( is_wp_error( $attach_id ) ) {
			return $attach_id;
		}

		// Generate metadata (thumbnails, etc.).
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attach_id, $dest_file );
		wp_update_attachment_metadata( $attach_id, $metadata );

		// Store original path for dedup/traceability.
		update_post_meta( $attach_id, '_ifs_original_path', $validated );

		return $attach_id;
	}
}
