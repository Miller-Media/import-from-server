<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * File browser: directory listing and path validation.
 */
class IFS_FileBrowser {

	/**
	 * Validate that a path is real, exists, and is within the allowed root.
	 *
	 * @param string $path Requested path.
	 * @return string|WP_Error Resolved real path or error.
	 */
	public static function validatePath( $path ) {
		$settings  = get_option( 'ifs_settings', array() );
		$root_path = isset( $settings['root_path'] ) && '' !== $settings['root_path']
			? $settings['root_path']
			: WP_CONTENT_DIR;

		$real_root = realpath( $root_path );
		if ( false === $real_root ) {
			return new WP_Error( 'ifs_invalid_root', __( 'The configured root path does not exist.', 'import-from-server' ) );
		}

		$real_path = realpath( $path );
		if ( false === $real_path ) {
			return new WP_Error( 'ifs_path_not_found', __( 'The requested path does not exist.', 'import-from-server' ) );
		}

		// Boundary check: the resolved path must start with the root.
		if ( 0 !== strpos( $real_path, $real_root ) ) {
			return new WP_Error( 'ifs_path_outside_root', __( 'Access denied: path is outside the allowed root directory.', 'import-from-server' ) );
		}

		return $real_path;
	}

	/**
	 * List the contents of a directory.
	 *
	 * @param string $path Directory path (already validated).
	 * @return array{directories: array, files: array}|WP_Error
	 */
	public static function listDirectory( $path ) {
		if ( ! is_dir( $path ) ) {
			return new WP_Error( 'ifs_not_directory', __( 'The requested path is not a directory.', 'import-from-server' ) );
		}

		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'ifs_not_readable', __( 'The directory is not readable.', 'import-from-server' ) );
		}

		$directories = array();
		$files       = array();
		$entries     = scandir( $path );

		if ( false === $entries ) {
			return new WP_Error( 'ifs_scan_failed', __( 'Failed to read directory contents.', 'import-from-server' ) );
		}

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$full_path = trailingslashit( $path ) . $entry;

			if ( is_dir( $full_path ) ) {
				$directories[] = array(
					'name' => $entry,
					'path' => $full_path,
				);
			} else {
				$importable    = self::isFileImportable( $full_path );
				$attachment_id = self::isAlreadyImported( $full_path );

				$file_data = array(
					'name'       => $entry,
					'path'       => $full_path,
					'size'       => filesize( $full_path ),
					'modified'   => filemtime( $full_path ),
					'mime_type'  => self::getMimeType( $full_path ),
					'importable' => $importable,
					'imported'   => false !== $attachment_id,
				);

				if ( false !== $attachment_id ) {
					$file_data['edit_url'] = get_edit_post_link( $attachment_id, 'raw' );
				}

				$files[] = $file_data;
			}
		}

		// Sort alphabetically.
		usort(
			$directories,
			function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);
		usort(
			$files,
			function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		return array(
			'directories' => $directories,
			'files'       => $files,
		);
	}

	/**
	 * Check if a file has an importable MIME type.
	 *
	 * @param string $filepath Full path to the file.
	 * @return bool
	 */
	public static function isFileImportable( $filepath ) {
		$filetype = wp_check_filetype( basename( $filepath ) );

		if ( empty( $filetype['type'] ) ) {
			return false;
		}

		// Check against allowed types setting.
		$settings      = get_option( 'ifs_settings', array() );
		$allowed_types = isset( $settings['allowed_types'] ) ? trim( $settings['allowed_types'] ) : '';

		if ( '' !== $allowed_types ) {
			$allowed = array_map( 'trim', explode( ',', $allowed_types ) );
			$allowed = array_map( 'strtolower', $allowed );
			$ext     = strtolower( pathinfo( $filepath, PATHINFO_EXTENSION ) );

			if ( ! in_array( $ext, $allowed, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if a file has already been imported into the Media Library.
	 *
	 * @param string $filepath Full path to the file.
	 * @return int|false Attachment ID if imported, false otherwise.
	 */
	public static function isAlreadyImported( $filepath ) {
		global $wpdb;

		// Check by our custom meta key first.
		$post_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ifs_original_path' AND meta_value = %s LIMIT 1",
				$filepath
			)
		);

		if ( $post_id ) {
			return (int) $post_id;
		}

		// Also check _wp_attached_file for files already in uploads dir.
		$upload_dir  = wp_upload_dir();
		$upload_base = $upload_dir['basedir'];

		if ( 0 === strpos( $filepath, $upload_base ) ) {
			$relative = ltrim( str_replace( $upload_base, '', $filepath ), '/' );
			$post_id  = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
					$relative
				)
			);

			if ( $post_id ) {
				return (int) $post_id;
			}
		}

		return false;
	}

	/**
	 * Get the MIME type for a file.
	 *
	 * @param string $filepath Full path to the file.
	 * @return string MIME type or empty string.
	 */
	public static function getMimeType( $filepath ) {
		$filetype = wp_check_filetype( basename( $filepath ) );
		return $filetype['type'] ? $filetype['type'] : '';
	}
}
