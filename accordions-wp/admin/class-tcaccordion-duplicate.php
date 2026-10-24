<?php
/**
 * Duplicate an accordion from the Accordions list screen.
 *
 * Adds a "Duplicate" row action that copies the accordion (all items, settings and
 * design options) into a new draft.
 *
 * @package     TCAccordion
 * @subpackage  TCAccordion/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the "Duplicate" action for accordions.
 */
class TCAccordion_Duplicate {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'accordion_tp';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'post_row_actions', array( $this, 'add_row_action' ), 10, 2 );
		add_action( 'admin_action_tcaccordion_duplicate', array( $this, 'handle_duplicate' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
	}

	/**
	 * Check if the current user can duplicate the given accordion.
	 *
	 * @param int $post_id Source accordion ID.
	 * @return bool
	 */
	private function user_can_duplicate( $post_id ) {
		$post_type = get_post_type_object( self::POST_TYPE );

		if ( ! $post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return current_user_can( $post_type->cap->create_posts );
	}

	/**
	 * Add the Duplicate link to the list table row actions.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post    Current post.
	 * @return array
	 */
	public function add_row_action( $actions, $post ) {
		if ( ! $post || self::POST_TYPE !== $post->post_type || ! $this->user_can_duplicate( $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=tcaccordion_duplicate&post=' . absint( $post->ID ) ),
			'tcaccordion_duplicate_' . $post->ID
		);

		$actions['tcaccordion_duplicate'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $url ),
			/* translators: %s: accordion title */
			esc_attr( sprintf( __( 'Duplicate &#8220;%s&#8221;', 'accordions-wp' ), $post->post_title ) ),
			esc_html__( 'Duplicate', 'accordions-wp' )
		);

		return $actions;
	}

	/**
	 * Create the copy and redirect back to the list.
	 *
	 * @return void
	 */
	public function handle_duplicate() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		check_admin_referer( 'tcaccordion_duplicate_' . $post_id );

		$source = $post_id ? get_post( $post_id ) : null;

		if ( ! $source || self::POST_TYPE !== $source->post_type ) {
			wp_die( esc_html__( 'The accordion to duplicate could not be found.', 'accordions-wp' ) );
		}

		if ( ! $this->user_can_duplicate( $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate this accordion.', 'accordions-wp' ), '', array( 'response' => 403 ) );
		}

		$title = '' !== $source->post_title ? $source->post_title : __( '(no title)', 'accordions-wp' );

		$new_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'draft',
				/* translators: %s: original accordion title */
				'post_title'   => sprintf( __( '%s (Copy)', 'accordions-wp' ), $title ),
				'post_content' => $source->post_content,
				'post_excerpt' => $source->post_excerpt,
				'post_author'  => get_current_user_id(),
				'menu_order'   => $source->menu_order,
			),
			true
		);

		if ( is_wp_error( $new_id ) || ! $new_id ) {
			wp_die( esc_html__( 'The accordion could not be duplicated.', 'accordions-wp' ) );
		}

		$this->copy_meta( $post_id, $new_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'            => self::POST_TYPE,
					'tcaccordion_duplicated' => 1,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Copy every custom field (items, settings, design) to the new accordion.
	 *
	 * @param int $from Source ID.
	 * @param int $to   New accordion ID.
	 * @return void
	 */
	private function copy_meta( $from, $to ) {
		$skip = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date' );

		foreach ( get_post_meta( $from ) as $key => $values ) {
			if ( in_array( $key, $skip, true ) || 0 === strpos( $key, '_wp_trash_meta_' ) ) {
				continue;
			}

			foreach ( $values as $value ) {
				// add_post_meta() expects slashed data and re-serializes arrays.
				add_post_meta( $to, $key, wp_slash( maybe_unserialize( $value ) ) );
			}
		}
	}

	/**
	 * Confirmation notice on the list screen after duplicating.
	 *
	 * @return void
	 */
	public function maybe_show_notice() {
		if ( ! isset( $_GET['tcaccordion_duplicated'] ) || ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . self::POST_TYPE !== $screen->id ) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Accordion duplicated. The copy is saved as a draft.', 'accordions-wp' ) . '</p></div>';
	}
}

new TCAccordion_Duplicate();
