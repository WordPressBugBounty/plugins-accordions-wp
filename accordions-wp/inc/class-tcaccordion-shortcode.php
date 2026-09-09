<?php
/**
 * Shortcode Handler Class.
 *
 * @package     TCAccordion
 * @subpackage  TCAccordion/includes
 * @version     3.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles shortcode registration and rendering for [tcpaccordion].
 */
class TCAccordion_Shortcode {

	/**
	 * Shortcode tag name.
	 *
	 * @var string
	 */
	const SHORTCODE_TAG = 'tcpaccordion';

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_shortcode( self::SHORTCODE_TAG, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the [tcpaccordion id="123"] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML output.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			self::SHORTCODE_TAG
		);

		$post_id = absint( $atts['id'] );

		// Validate post ID and verify it is a published accordion post.
		if ( ! $post_id || 'accordion_tp' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return '';
		}

		// Enqueue frontend assets on-demand.
		$this->enqueue_assets();

		// Render accordion content securely using output buffering.
		ob_start();
		if ( function_exists( 'TCP_accordions_wordpress_table_body' ) ) {
			echo TCP_accordions_wordpress_table_body( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		return ob_get_clean();
	}

	/**
	 * Enqueue required scripts and styles for shortcode rendering.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		wp_enqueue_style( 'tcaccordion-responsive' );
		wp_enqueue_style( 'tcaccordion-style' );
		wp_enqueue_script( 'tcaccordion-script' );
	}
}

// Instantiate class.
new TCAccordion_Shortcode();