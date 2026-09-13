<?php
/**
 * Gutenberg Block Integration.
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TCAccordion_Gutenberg_Block {

	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register the block and enqueue editor scripts.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Register the block editor script
		wp_register_script(
			'tcaccordion-gutenberg-block',
			plugin_dir_url( __FILE__ ) . '../assets/js/gutenberg-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
			'3.0.7',
			true
		);

		// Fetch published 'accordion_tp' posts
		$accordions = get_posts(
			array(
				'post_type'      => 'accordion_tp',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		$accordion_list = array();
		if ( ! empty( $accordions ) ) {
			foreach ( $accordions as $accordion ) {
				$accordion_list[] = array(
					'label' => esc_html( $accordion->post_title ),
					'value' => absint( $accordion->ID ),
				);
			}
		}

		// Localize script data for the Gutenberg dropdown
		wp_localize_script(
			'tcaccordion-gutenberg-block',
			'tcaccGutenbergData',
			array(
				'accordions' => $accordion_list,
				'title'      => esc_html__( 'TCP Accordion', 'tcaccordion' ),
			)
		);

		// Register server-side rendered block
		register_block_type(
			'tcaccordion/select-accordion',
			array(
				'editor_script'   => 'tcaccordion-gutenberg-block',
				'render_callback' => array( $this, 'render_block_callback' ),
				'attributes'      => array(
					'accordionId' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	public function enqueue_block_editor_assets() {
	    wp_register_script(
	        'tcaccordion-gutenberg-block',
	        plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/gutenberg-block.js',
	        // Added 'wp-server-side-render' dependency
	        array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
	        '3.0.7',
	        true
	    );

	    // Enqueue frontend CSS inside Gutenberg Editor Canvas so preview is styled
	    wp_enqueue_style( 'tcaccordion-responsive' );
	    wp_enqueue_style( 'tcaccordion-style' );

	    // Query posts & localize logic...
	    wp_enqueue_script( 'tcaccordion-gutenberg-block' );
	}

	/**
	 * Render callback for the frontend and editor preview.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block_callback( $attributes ) {
		$accordion_id = isset( $attributes['accordionId'] ) ? absint( $attributes['accordionId'] ) : 0;

		if ( ! $accordion_id ) {
			return '';
		}

		// Execute your existing shortcode handler
		return do_shortcode( sprintf( '[tcpaccordion id="%d"]', $accordion_id ) );
	}
}

new TCAccordion_Gutenberg_Block();