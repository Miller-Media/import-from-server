<?php
/**
 * REST API endpoints for browsing and importing files.
 */
class IFS_REST_API {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'import-from-server/v1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function registerRoutes() {
		register_rest_route(
			self::NAMESPACE,
			'/browse',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handleBrowse' ),
				'permission_callback' => array( $this, 'checkUploadPermission' ),
				'args'                => array(
					'path' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleImport' ),
				'permission_callback' => array( $this, 'checkUploadPermission' ),
				'args'                => array(
					'files' => array(
						'type'     => 'array',
						'required' => true,
						'items'    => array(
							'type' => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Check that the current user can upload files.
	 *
	 * @return bool
	 */
	public function checkUploadPermission() {
		return current_user_can( 'upload_files' );
	}

	/**
	 * Handle browse request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleBrowse( $request ) {
		$path = $request->get_param( 'path' );

		if ( empty( $path ) ) {
			$settings = get_option( 'ifs_settings', array() );
			$path     = isset( $settings['root_path'] ) && '' !== $settings['root_path']
				? $settings['root_path']
				: WP_CONTENT_DIR;
		}

		$validated = IFS_FileBrowser::validatePath( $path );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$listing = IFS_FileBrowser::listDirectory( $validated );
		if ( is_wp_error( $listing ) ) {
			return $listing;
		}

		// Build breadcrumb data.
		$settings  = get_option( 'ifs_settings', array() );
		$root_path = isset( $settings['root_path'] ) && '' !== $settings['root_path']
			? realpath( $settings['root_path'] )
			: realpath( WP_CONTENT_DIR );

		$breadcrumbs = self::buildBreadcrumbs( $validated, $root_path );

		return rest_ensure_response(
			array(
				'current_path' => $validated,
				'breadcrumbs'  => $breadcrumbs,
				'directories'  => $listing['directories'],
				'files'        => $listing['files'],
			)
		);
	}

	/**
	 * Handle import request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleImport( $request ) {
		$files   = $request->get_param( 'files' );
		$results = array();

		foreach ( $files as $filepath ) {
			$filepath  = sanitize_text_field( $filepath );
			$attach_id = IFS_Importer::importFile( $filepath );

			if ( is_wp_error( $attach_id ) ) {
				$results[] = array(
					'file'    => basename( $filepath ),
					'success' => false,
					'error'   => $attach_id->get_error_message(),
				);
			} else {
				$results[] = array(
					'file'          => basename( $filepath ),
					'success'       => true,
					'attachment_id' => $attach_id,
					'url'           => wp_get_attachment_url( $attach_id ),
				);
			}
		}

		return rest_ensure_response(
			array(
				'results' => $results,
			)
		);
	}

	/**
	 * Build breadcrumb segments from a path relative to the root.
	 *
	 * @param string $current_path Resolved current path.
	 * @param string $root_path    Resolved root path.
	 * @return array
	 */
	private static function buildBreadcrumbs( $current_path, $root_path ) {
		$breadcrumbs = array(
			array(
				'label' => __( 'Root', 'import-from-server' ),
				'path'  => $root_path,
			),
		);

		if ( $current_path === $root_path ) {
			return $breadcrumbs;
		}

		$relative = substr( $current_path, strlen( $root_path ) + 1 );
		$segments = explode( '/', $relative );
		$built    = $root_path;

		foreach ( $segments as $segment ) {
			$built        .= '/' . $segment;
			$breadcrumbs[] = array(
				'label' => $segment,
				'path'  => $built,
			);
		}

		return $breadcrumbs;
	}
}
