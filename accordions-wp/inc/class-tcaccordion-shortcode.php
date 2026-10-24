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
				'id'         => 0,
				'layout'     => '',
				'search'     => '',
				'expand_all' => '',
				'deep_link'  => '',
				'open'       => '',
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
			echo TCP_accordions_wordpress_table_body( $post_id, $this->build_overrides( $atts ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		return ob_get_clean();
	}

	/**
	 * Turn shortcode attributes into validated setting overrides (keyed by meta key).
	 *
	 * Example: [tcpaccordion id="12" layout="tabs" search="yes" expand_all="yes" deep_link="yes" open="2"]
	 * "open" is the item number that starts open (0 = all closed).
	 *
	 * @param array $atts Parsed shortcode attributes.
	 * @return array
	 */
	private function build_overrides( $atts ) {
		$overrides = array();

		$layout = sanitize_key( (string) $atts['layout'] );
		if ( array_key_exists( $layout, TCAccordion_Renderer::get_available_layouts() ) ) {
			$overrides['_tcacc_layout'] = $layout;
		}

		$flags = array(
			'search'     => '_tcacc_show_search',
			'expand_all' => '_tcacc_show_toggle_all',
			'deep_link'  => '_tcacc_deep_link',
		);

		foreach ( $flags as $attribute => $meta_key ) {
			$value = strtolower( trim( (string) $atts[ $attribute ] ) );

			if ( in_array( $value, array( 'yes', 'true', '1', 'on' ), true ) ) {
				$overrides[ $meta_key ] = 'yes';
			} elseif ( in_array( $value, array( 'no', 'false', '0', 'off' ), true ) ) {
				$overrides[ $meta_key ] = 'no';
			}
		}

		if ( '' !== trim( (string) $atts['open'] ) && is_numeric( $atts['open'] ) ) {
			$overrides['_tcacc_auto_open_item'] = (string) absint( $atts['open'] );
		}

		return $overrides;
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

		/**
		 * Lets add-ons load their own front-end assets together with the accordion script.
		 */
		do_action( 'tcaccordion_enqueue_assets' );
	}
}

// Instantiate class.
new TCAccordion_Shortcode();