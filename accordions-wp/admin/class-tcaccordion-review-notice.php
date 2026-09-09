<?php
/**
 * Admin Review Notice Class with AJAX Dismissal & Live Preview option.
 *
 * @package     TCAccordion
 * @subpackage  TCAccordion/Admin
 * @version     3.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles review prompt display after installation threshold.
 */
class TCAccordion_Review_Notice {

	/**
	 * Days delay before showing review prompt.
	 */
	const DISPLAY_DELAY_DAYS = 7;

	/**
	 * Option keys.
	 */
	const DISMISSED_OPTION = 'tcaccordion_review_notice_dismissed';

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_review_notice' ) );
		add_action( 'wp_ajax_tcaccordion_dismiss_review_notice', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Check eligibility and render admin notice banner.
	 *
	 * @return void
	 */
	public function display_review_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if notice dismissed or preview requested
		$is_dismissed = get_option( self::DISMISSED_OPTION, false );
		$is_preview   = isset( $_GET['tcaccordion_preview_review_notice'] ) && '1' === $_GET['tcaccordion_preview_review_notice']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $is_dismissed && ! $is_preview ) {
			return;
		}

		// Check installation delay threshold
		$installed_time = TCAccordion::get_installation_time();
		$delay_seconds  = self::DISPLAY_DELAY_DAYS * DAY_IN_SECONDS;

		if ( ( time() - $installed_time < $delay_seconds ) && ! $is_preview ) {
			return;
		}

		$review_url = 'https://wordpress.org/support/plugin/accordions-wp/reviews/#new-post';
		?>
		<div id="tcaccordion-review-notice" class="notice notice-info is-dismissible tcaccordion-review-wrapper" style="padding: 15px; position: relative;">
			<?php if ( $is_preview ) : ?>
				<p><span class="dashicons dashicons-visibility" style="color:#d63638;"></span> <strong><?php esc_html_e( '[ADMIN PREVIEW MODE]', 'tcaccordion' ); ?></strong></p>
			<?php endif; ?>
			
			<h3 style="margin:0 0 8px 0;"><?php esc_html_e( 'Are you enjoying Accordion-WP?', 'tcaccordion' ); ?></h3>
			<p style="margin:0 0 12px 0;">
				<?php esc_html_e( 'Hope you find this plugin helpful! If you like Accordion-WP, would you mind taking a minute to rate it 5 stars on WordPress.org? Your support means a lot!', 'tcaccordion' ); ?>
			</p>
			
			<div class="tcaccordion-notice-actions">
				<a href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary tcaccordion-dismiss-btn" data-type="reviewed">
					<span class="dashicons dashicons-external" style="vertical-align: middle; line-height: 1.3;"></span>
					<?php esc_html_e( 'Leave a Review', 'tcaccordion' ); ?>
				</a>

				<button type="button" class="button button-secondary tcaccordion-dismiss-btn" data-type="already" style="margin-left: 6px;">
					<?php esc_html_e( 'I Already Did', 'tcaccordion' ); ?>
				</button>

				<button type="button" class="button button-link tcaccordion-dismiss-btn" data-type="later" style="margin-left: 6px; text-decoration: none;">
					<?php esc_html_e( 'Maybe Later', 'tcaccordion' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle AJAX dismiss submission.
	 *
	 * @return void
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'tcaccordion_review_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tcaccordion' ) ) );
		}

		$dismiss_type = isset( $_POST['dismiss_type'] ) ? sanitize_key( $_POST['dismiss_type'] ) : 'dismissed';

		if ( 'later' === $dismiss_type ) {
			// Reset installation time to 7 days from now to remind later
			update_option( TCAccordion::INSTALL_KEY, time() );
		} else {
			// Permanent dismissal
			update_option( self::DISMISSED_OPTION, true );
		}

		wp_send_json_success();
	}

	/**
	 * Enqueue inline AJAX JS handler.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$script = "
		jQuery(document).ready(function($) {
			$(document).on('click', '.tcaccordion-dismiss-btn, #tcaccordion-review-notice .notice-dismiss', function(e) {
				var btn = $(this);
				var dismissType = btn.data('type') || 'dismissed';

				$.post(ajaxurl, {
					action: 'tcaccordion_dismiss_review_notice',
					security: '" . wp_create_nonce( 'tcaccordion_review_nonce' ) . "',
					dismiss_type: dismissType
				}, function(response) {
					if (response.success) {
						$('#tcaccordion-review-notice').fadeTo(100, 0, function() {
							$(this).slideUp(100, function() {
								$(this).remove();
							});
						});
					}
				});
			});
		});
		";

		wp_add_inline_script( 'jquery', $script );
	}
}

// Instantiate
new TCAccordion_Review_Notice();