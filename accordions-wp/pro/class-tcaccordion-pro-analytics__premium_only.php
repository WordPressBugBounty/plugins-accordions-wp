<?php
/**
 * Pro: analytics. Sends Google Analytics events and/or counts which items visitors open most.
 *
 * Only counts are stored (item title and number of opens). No visitor data is saved.
 * The "__premium_only" part of the file name makes Freemius remove it from the free version.
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Click tracking for accordion items.
 */
class TCAccordion_Pro_Analytics {

	/**
	 * Post meta key that holds the counters.
	 */
	const META_STATS = '_tcacc_stats';

	/**
	 * Most different items counted per accordion (protects the database from abuse).
	 */
	const MAX_ITEMS = 500;

	/**
	 * Allowed tracking modes.
	 *
	 * @var string[]
	 */
	private $modes = array( 'off', 'ga', 'count', 'both' );

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'tcaccordion_wrapper_attributes', array( $this, 'wrapper_attributes' ), 10, 4 );
		add_action( 'tcaccordion_pro_settings', array( $this, 'render_settings' ), 30 );
		add_action( 'tcaccordion_save_settings', array( $this, 'save_settings' ) );
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
		add_action( 'admin_post_tcaccordion_reset_stats', array( $this, 'handle_reset' ) );
	}

	/**
	 * Tell the front-end script to track this accordion.
	 *
	 * @param string               $attributes Attributes so far.
	 * @param array                $styles     Styles map.
	 * @param array                $context    Accordion context.
	 * @param TCAccordion_Renderer $renderer   Renderer.
	 * @return string
	 */
	public function wrapper_attributes( $attributes, $styles, $context, $renderer ) {
		unset( $styles );

		$mode = (string) $renderer->get_setting( '_tcacc_analytics', 'off' );

		if ( ! in_array( $mode, array( 'ga', 'count', 'both' ), true ) || empty( $context['post_id'] ) ) {
			return $attributes;
		}

		return $attributes . ' data-tcacc-analytics="' . esc_attr( $mode ) . '" data-tcacc-id="' . absint( $context['post_id'] ) . '"';
	}

	/**
	 * Register the endpoint that receives open events.
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			'tcaccordion/v1',
			'/track',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_track' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'    => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'title' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Count one opened item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_track( $request ) {
		$id    = absint( $request->get_param( 'id' ) );
		$title = mb_substr( trim( (string) $request->get_param( 'title' ) ), 0, 120 );

		if ( ! $id || '' === $title ) {
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}

		$post = get_post( $id );
		if ( ! $post || 'accordion_tp' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_REST_Response( array( 'ok' => false ), 404 );
		}

		if ( ! in_array( get_post_meta( $id, '_tcacc_analytics', true ), array( 'count', 'both' ), true ) ) {
			return new WP_REST_Response( array( 'ok' => false ), 403 );
		}

		// Simple rate limit per visitor address (not stored, only hashed for the counter key).
		$address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key     = 'tcacc_rl_' . md5( $address );
		$hits    = (int) get_transient( $key );

		if ( $hits >= 60 ) {
			return new WP_REST_Response( array( 'ok' => false ), 429 );
		}
		set_transient( $key, $hits + 1, MINUTE_IN_SECONDS );

		$stats = get_post_meta( $id, self::META_STATS, true );
		if ( ! is_array( $stats ) || ! isset( $stats['items'] ) || ! is_array( $stats['items'] ) ) {
			$stats = array(
				'total' => 0,
				'items' => array(),
			);
		}

		$hash = substr( md5( mb_strtolower( $title ) ), 0, 12 );

		if ( ! isset( $stats['items'][ $hash ] ) ) {
			if ( count( $stats['items'] ) >= self::MAX_ITEMS ) {
				return new WP_REST_Response( array( 'ok' => true ), 200 );
			}
			$stats['items'][ $hash ] = array(
				't' => $title,
				'n' => 0,
			);
		}

		$stats['items'][ $hash ]['n']++;
		$stats['total'] = isset( $stats['total'] ) ? (int) $stats['total'] + 1 : 1;

		update_post_meta( $id, self::META_STATS, $stats );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Settings rows and the statistics table.
	 *
	 * @param WP_Post $post Accordion post.
	 * @return void
	 */
	public function render_settings( $post ) {
		$mode = get_post_meta( $post->ID, '_tcacc_analytics', true );
		$mode = in_array( $mode, $this->modes, true ) ? $mode : 'off';

		$stats = get_post_meta( $post->ID, self::META_STATS, true );
		$stats = ( is_array( $stats ) && ! empty( $stats['items'] ) ) ? $stats : array();
		?>
		<tr valign="top">
			<th scope="row" colspan="2">
				<h3 style="margin:18px 0 0;"><?php esc_html_e( 'Analytics (Pro)', 'accordions-wp' ); ?></h3>
				<p class="description"><?php esc_html_e( 'See which questions visitors open most. Only the item title and an open counter are stored. No visitor data is saved.', 'accordions-wp' ); ?></p>
			</th>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="_tcacc_analytics"><?php esc_html_e( 'Track item opens', 'accordions-wp' ); ?></label></th>
			<td>
				<select name="_tcacc_analytics" id="_tcacc_analytics">
					<option value="off" <?php selected( $mode, 'off' ); ?>><?php esc_html_e( 'Off', 'accordions-wp' ); ?></option>
					<option value="count" <?php selected( $mode, 'count' ); ?>><?php esc_html_e( 'Count opens in the dashboard', 'accordions-wp' ); ?></option>
					<option value="ga" <?php selected( $mode, 'ga' ); ?>><?php esc_html_e( 'Send Google Analytics events', 'accordions-wp' ); ?></option>
					<option value="both" <?php selected( $mode, 'both' ); ?>><?php esc_html_e( 'Both', 'accordions-wp' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Google Analytics events are sent as "accordion_open" through gtag or the dataLayer when Google Analytics / Tag Manager is already on your site. Each item is counted once per page view.', 'accordions-wp' ); ?></p>
			</td>
		</tr>
		<?php if ( ! empty( $stats ) ) : ?>
			<?php
			$items = $stats['items'];
			uasort(
				$items,
				static function ( $a, $b ) {
					return (int) $b['n'] - (int) $a['n'];
				}
			);
			$items     = array_slice( $items, 0, 10, true );
			$reset_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'tcaccordion_reset_stats',
						'post'   => $post->ID,
					),
					admin_url( 'admin-post.php' )
				),
				'tcaccordion_reset_stats_' . $post->ID
			);
			?>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Most opened items', 'accordions-wp' ); ?></th>
				<td>
					<table class="widefat striped" style="max-width:560px;">
						<thead><tr><th><?php esc_html_e( 'Item', 'accordions-wp' ); ?></th><th style="width:90px;"><?php esc_html_e( 'Opens', 'accordions-wp' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $items as $row ) : ?>
								<tr><td><?php echo esc_html( $row['t'] ); ?></td><td><?php echo esc_html( number_format_i18n( (int) $row['n'] ) ); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p class="description">
						<?php
						/* translators: %s: total number of opens */
						printf( esc_html__( 'Total opens: %s', 'accordions-wp' ), esc_html( number_format_i18n( (int) $stats['total'] ) ) );
						?>
						&nbsp;|&nbsp;
						<a href="<?php echo esc_url( $reset_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Reset all statistics for this accordion?', 'accordions-wp' ) ); ?>');"><?php esc_html_e( 'Reset statistics', 'accordions-wp' ); ?></a>
					</p>
				</td>
			</tr>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save the tracking mode. The nonce and permissions are checked by the free plugin first.
	 *
	 * @param int $post_id Accordion ID.
	 * @return void
	 */
	public function save_settings( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['_tcacc_analytics'] ) ) {
			$mode = sanitize_text_field( wp_unslash( $_POST['_tcacc_analytics'] ) );
			update_post_meta( $post_id, '_tcacc_analytics', in_array( $mode, $this->modes, true ) ? $mode : 'off' );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Delete the counters of one accordion.
	 *
	 * @return void
	 */
	public function handle_reset() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		check_admin_referer( 'tcaccordion_reset_stats_' . $post_id );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || 'accordion_tp' !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to reset these statistics.', 'accordions-wp' ), '', array( 'response' => 403 ) );
		}

		delete_post_meta( $post_id, self::META_STATS );

		wp_safe_redirect( get_edit_post_link( $post_id, 'raw' ) );
		exit;
	}
}

new TCAccordion_Pro_Analytics();
