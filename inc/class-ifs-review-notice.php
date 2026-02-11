<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Review Notice class
 *
 * Displays a dismissible admin notice after 14 days of usage requesting a review.
 */
class IFS_ReviewNotice {

	/**
	 * Plugin display name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Option name storing activation timestamp.
	 *
	 * @var string
	 */
	private $activation_option;

	/**
	 * User meta key for dismissed state.
	 *
	 * @var string
	 */
	private $dismissed_meta_key;

	/**
	 * URL for leaving a review.
	 *
	 * @var string
	 */
	private $review_url;

	/**
	 * Text domain for translations.
	 *
	 * @var string
	 */
	private $text_domain;

	/**
	 * Icon image URL.
	 *
	 * @var string
	 */
	private $icon_url;

	/**
	 * Dashicon class name.
	 *
	 * @var string
	 */
	private $icon_dashicon;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name       Plugin display name.
	 * @param string $plugin_slug       Plugin slug.
	 * @param string $activation_option Option name storing activation timestamp.
	 * @param string $text_domain       Text domain.
	 * @param string $icon_url          Icon image URL.
	 * @param string $icon_dashicon     Dashicon class name.
	 */
	public function __construct( $plugin_name, $plugin_slug, $activation_option, $text_domain, $icon_url = '', $icon_dashicon = '' ) {
		$this->plugin_name        = $plugin_name;
		$this->plugin_slug        = $plugin_slug;
		$this->activation_option  = $activation_option;
		$this->dismissed_meta_key = $plugin_slug . '_review_dismissed';
		$this->review_url         = 'https://wordpress.org/support/plugin/' . $plugin_slug . '/reviews/#new-post';
		$this->text_domain        = $text_domain;
		$this->icon_url           = $icon_url;
		$this->icon_dashicon      = $icon_dashicon;

		add_action( 'admin_notices', array( $this, 'showReviewNotice' ) );
		add_action( 'admin_init', array( $this, 'handleDismiss' ) );
	}

	/**
	 * Display the review notice if conditions are met.
	 */
	public function showReviewNotice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), $this->dismissed_meta_key, true ) ) {
			return;
		}

		$activated = get_option( $this->activation_option );
		if ( ! $activated ) {
			return;
		}

		$days_active = ( time() - $activated ) / DAY_IN_SECONDS;
		if ( $days_active < 14 ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible" id="<?php echo esc_attr( $this->plugin_slug ); ?>-review-notice" style="display: flex; align-items: center; padding: 12px;">
			<?php if ( $this->icon_url ) : ?>
				<img src="<?php echo esc_url( $this->icon_url ); ?>" alt="" style="width: 48px; height: 48px; margin-right: 16px; flex-shrink: 0;">
			<?php elseif ( $this->icon_dashicon ) : ?>
				<span class="dashicons <?php echo esc_attr( $this->icon_dashicon ); ?>" style="font-size: 48px; width: 48px; height: 48px; margin-right: 16px; flex-shrink: 0; color: #2271b1;"></span>
			<?php endif; ?>
			<div style="flex: 1;">
			<p style="margin: 0.5em 0;">
				<?php
				printf(
					/* translators: 1: plugin name, 2: opening link tag, 3: closing link tag */
					esc_html__( 'Hey! You\'ve been using %1$s for a while now. If you\'re enjoying it, would you mind %2$sleaving a 5-star review%3$s? It helps us keep improving!', 'import-from-server' ),
					'<strong>' . esc_html( $this->plugin_name ) . '</strong>',
					'<a href="' . esc_url( $this->review_url ) . '" target="_blank">',
					'</a>'
				);
				?>
			</p>
			<p style="margin: 0.5em 0;">
				<a href="<?php echo esc_url( $this->review_url ); ?>" class="button button-primary" target="_blank">
					<?php esc_html_e( 'Leave a Review', 'import-from-server' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( $this->plugin_slug . '_dismiss_review', 'true' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Maybe Later', 'import-from-server' ); ?>
				</a>
			</p>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#<?php echo esc_js( $this->plugin_slug ); ?>-review-notice').on('click', '.notice-dismiss, .button', function() {
					$.post(ajaxurl, {
						action: '<?php echo esc_js( $this->plugin_slug ); ?>_dismiss_review_notice'
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Handle dismiss actions via URL parameter or AJAX.
	 */
	public function handleDismiss() {
		if ( isset( $_GET[ $this->plugin_slug . '_dismiss_review' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			update_user_meta( get_current_user_id(), $this->dismissed_meta_key, true );
			wp_safe_redirect( remove_query_arg( $this->plugin_slug . '_dismiss_review' ) );
			exit;
		}

		add_action( 'wp_ajax_' . $this->plugin_slug . '_dismiss_review_notice', array( $this, 'ajaxDismiss' ) );
	}

	/**
	 * AJAX handler for dismissing the review notice.
	 */
	public function ajaxDismiss() {
		update_user_meta( get_current_user_id(), $this->dismissed_meta_key, true );
		wp_die();
	}
}
