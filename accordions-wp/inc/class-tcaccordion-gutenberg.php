<?php
/**
 * Gutenberg Dual Block Integration.
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TCAccordion_Gutenberg_Block {

	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Layout choices for the block sidebars: the available layouts plus disabled "Pro" previews.
	 *
	 * @return array List of array( 'label' => string, 'value' => string, 'disabled' => bool ).
	 */
	private function get_layout_choices() {
		$available = TCAccordion_Renderer::get_available_layouts();
		$choices   = array();

		foreach ( $available as $value => $label ) {
			$choices[] = array(
				'label'    => $label,
				'value'    => $value,
				'disabled' => false,
			);
		}

		$previews = array(
			'tabs'       => __( 'Tabs on desktop', 'accordions-wp' ),
			'horizontal' => __( 'Horizontal accordion', 'accordions-wp' ),
		);

		foreach ( $previews as $value => $label ) {
			if ( ! isset( $available[ $value ] ) ) {
				$choices[] = array(
					/* translators: %s: layout name */
					'label'    => sprintf( __( '%s (Pro)', 'accordions-wp' ), $label ),
					'value'    => $value,
					'disabled' => true,
				);
			}
		}

		return $choices;
	}

	/**
	 * Settings the Inline block may change, with the allowed values.
	 * Keys are the same setting keys the accordion editor saves, so the renderer treats them identically.
	 *
	 * @return array
	 */
	private function get_inline_option_schema() {
		$yes_no = array( 'yes', 'no' );

		$schema = array(
			'custom_accordion_columns_post_themes'      => array( 'enum', array( 'theme1', 'theme2', 'theme3', 'theme4', 'theme5', 'theme-flat', 'theme-shadow', 'theme-dark' ) ),
			'_tcacc_layout'                             => array( 'enum', array_keys( TCAccordion_Renderer::get_available_layouts() ) ),
			'_tcacc_auto_open_item'                     => array( 'int', 0, 999 ),
			'_tcacc_close_others'                       => array( 'enum', $yes_no ),
			'_tcacc_is_closeable'                       => array( 'enum', $yes_no ),
			'_tcacc_show_search'                        => array( 'enum', $yes_no ),
			'_tcacc_search_placeholder'                 => array( 'text' ),
			'_tcacc_show_toggle_all'                    => array( 'enum', $yes_no ),
			'_tcacc_deep_link'                          => array( 'enum', $yes_no ),
			'_tcacc_enable_faq_schema'                  => array( 'enum', $yes_no ),
			'_tcacc_icon_style'                         => array( 'enum', array( 'plus_minus', 'chevron', 'angle', 'caret', 'folder', 'none' ) ),
			'_tpaccpro_wiki_acc_themes_show_hide_icons' => array( 'enum', array( '1', '2' ) ),
			'_tpaccpro_wiki_acc_themes_icon_position'   => array( 'enum', array( '1', '2' ) ),
			'_tpaccpro_wiki_acc_themes_title_position'  => array( 'enum', array( 'left', 'center', 'right' ) ),
			'custom_accordion_title_font_size'          => array( 'int', 8, 80 ),
			'custom_accordion_title_line_height'        => array( 'float', 1, 3 ),
			'custom_accordion_content_font_size'        => array( 'int', 8, 60 ),
			'custom_accordion_content_line_height'      => array( 'float', 1, 3 ),
			'custom_accordion_content_letter_spacing'   => array( 'float', -2, 10 ),
			'custom_accordion_content_padding'          => array( 'int', 0, 80 ),
			'_tpaccpro_wiki_acc_theme_content_margin'   => array( 'int', 0, 60 ),
			'custom_accordion_border_style'             => array( 'enum', array( 'none', 'solid', 'dashed', 'dotted', 'double' ) ),
			'custom_accordion_border_width'             => array( 'int', 0, 20 ),
			'custom_accordion_border_color'             => array( 'color' ),
			'custom_accordion_title_bg_color'           => array( 'color' ),
			'custom_accordion_title_font_color'         => array( 'color' ),
			'custom_accordion_title_bg_color_hover'     => array( 'color' ),
			'custom_accordion_title_font_color_hover'   => array( 'color' ),
			'custom_accordion_icon_color_hover'         => array( 'color' ),
			'custom_accordion_title_bg_color_active'    => array( 'color' ),
			'custom_accordion_title_font_color_active'  => array( 'color' ),
			'custom_accordion_icon_color_active'        => array( 'color' ),
			'custom_accordion_icon_color'               => array( 'color' ),
			'custom_accordion_icon_bg_color'            => array( 'color' ),
			'custom_accordion_content_bg_color'         => array( 'color' ),
			'custom_accordion_content_font_color'       => array( 'color' ),
		);

		/**
		 * Lets add-ons accept more Inline block settings (same format: key => array( type, ... )).
		 *
		 * @param array $schema Allowed settings.
		 */
		return apply_filters( 'tcaccordion_inline_option_schema', $schema );
	}

	/**
	 * Keep only known settings with valid values.
	 *
	 * @param mixed $options Raw "options" block attribute.
	 * @return array Clean overrides keyed by setting key.
	 */
	private function sanitize_inline_options( $options ) {
		$clean = array();

		if ( ! is_array( $options ) ) {
			return $clean;
		}

		foreach ( $this->get_inline_option_schema() as $key => $rule ) {
			if ( ! isset( $options[ $key ] ) || '' === $options[ $key ] || is_array( $options[ $key ] ) || is_object( $options[ $key ] ) ) {
				continue;
			}

			$value = $options[ $key ];

			switch ( $rule[0] ) {
				case 'enum':
					if ( in_array( (string) $value, $rule[1], true ) ) {
						$clean[ $key ] = (string) $value;
					}
					break;

				case 'int':
					if ( is_numeric( $value ) ) {
						$clean[ $key ] = (string) max( $rule[1], min( $rule[2], (int) $value ) );
					}
					break;

				case 'float':
					if ( is_numeric( $value ) ) {
						$clean[ $key ] = (string) max( $rule[1], min( $rule[2], (float) $value ) );
					}
					break;

				case 'color':
					$color = sanitize_hex_color( (string) $value );
					if ( $color ) {
						$clean[ $key ] = $color;
					}
					break;

				case 'text':
					$clean[ $key ] = sanitize_text_field( (string) $value );
					break;
			}
		}

		return $clean;
	}

	/**
	 * Render the Inline block on the front end through the same renderer as saved accordions,
	 * so it supports every setting (layouts, search, colors, hover/open states...).
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Saved inner content (not used).
	 * @param WP_Block $block      Block instance with its inner blocks.
	 * @return string
	 */
	public function render_inline_wrapper( $attributes, $content, $block ) {
		$items = array();

		if ( isset( $block->inner_blocks ) ) {
			foreach ( $block->inner_blocks as $item_block ) {
				if ( 'tcaccordion/inline-item' !== $item_block->name ) {
					continue;
				}

				$title = isset( $item_block->attributes['title'] ) ? wp_strip_all_tags( (string) $item_block->attributes['title'] ) : '';
				$body  = '';

				foreach ( $item_block->inner_blocks as $child_block ) {
					$body .= $child_block->render();
				}

				$items[] = array(
					'title'       => $title,
					'description' => $body,
				);
			}
		}

		if ( empty( $items ) ) {
			return '';
		}

		wp_enqueue_style( 'tcaccordion-responsive' );
		wp_enqueue_script( 'tcaccordion-script' );
		do_action( 'tcaccordion_enqueue_assets' );

		$options = isset( $attributes['options'] ) ? $this->sanitize_inline_options( $attributes['options'] ) : array();

		$renderer = new TCAccordion_Renderer( 0 );
		$renderer->set_items( $items );
		$renderer->set_overrides( $options );
		$renderer->set_scope_id( 'tc-accordion-inline-' . substr( md5( wp_json_encode( array( $items, $options ) ) ), 0, 8 ) );

		return $renderer->render();
	}

	/**
	 * Register both block types and scripts.
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// 1. Register Reusable CPT Block Script
		wp_register_script(
			'tcaccordion-gutenberg-block',
			plugin_dir_url( __FILE__ ) . '../assets/js/gutenberg-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			defined( 'TCACCORDION_VERSION' ) ? TCACCORDION_VERSION : '4.0.0',
			true
		);

		// 2. Register Native Inline Block Script
		wp_register_script(
			'tcaccordion-inline-block',
			plugin_dir_url( __FILE__ ) . '../assets/js/gutenberg-inline-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ),
			defined( 'TCACCORDION_VERSION' ) ? TCACCORDION_VERSION : '4.0.0',
			true
		);

		// 3. Register Server-Side Rendered CPT Block
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
					'layout'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'showSearch'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'expandAll'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'deepLink'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'autoOpen'    => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		// 4. Register Native Inline Wrapper & Item Blocks
		register_block_type(
			'tcaccordion/inline-wrapper',
			array(
				'editor_script'   => 'tcaccordion-inline-block',
				'render_callback' => array( $this, 'render_inline_wrapper' ),
				'attributes'      => array(
					'options' => array(
						'type'    => 'object',
						'default' => array(),
					),
				),
			)
		);

		register_block_type(
			'tcaccordion/inline-item',
			array(
				'editor_script' => 'tcaccordion-inline-block',
			)
		);
	}

	/**
	 * Enqueue assets, styles, and localized data in editor context.
	 */
	public function enqueue_block_editor_assets() {
		// Fetch published 'accordion_tp' posts for CPT dropdown
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
				'adminUrl'   => admin_url(),
				'title'      => esc_html__( 'TCP Accordion (Reusable)', 'accordions-wp' ),
				'layouts'    => $this->get_layout_choices(),
			)
		);

		// Data for the Inline block sidebar (which options are available).
		wp_localize_script(
			'tcaccordion-inline-block',
			'tcaccInlineData',
			array(
				'layouts'     => $this->get_layout_choices(),
				// True when an add-on provides the open event / animation speed settings.
				'proBehavior' => (bool) has_filter( 'tcaccordion_behavior_settings' ),
			)
		);

		// Enqueue frontend CSS inside Gutenberg Editor Canvas so preview is styled
		if ( wp_style_is( 'tcaccordion-responsive', 'registered' ) ) {
			wp_enqueue_style( 'tcaccordion-responsive' );
		}
		if ( wp_style_is( 'tcaccordion-style', 'registered' ) ) {
			wp_enqueue_style( 'tcaccordion-style' );
		}
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

		// Optional per-block overrides are passed to the shortcode, which validates them.
		$map  = array(
			'layout'     => 'layout',
			'showSearch' => 'search',
			'expandAll'  => 'expand_all',
			'deepLink'   => 'deep_link',
			'autoOpen'   => 'open',
		);
		$atts = sprintf( 'id="%d"', $accordion_id );

		foreach ( $map as $attribute => $shortcode_att ) {
			if ( isset( $attributes[ $attribute ] ) && '' !== $attributes[ $attribute ] ) {
				$value = preg_replace( '/[^a-z0-9\-]/i', '', (string) $attributes[ $attribute ] );
				if ( '' !== $value ) {
					$atts .= sprintf( ' %s="%s"', $shortcode_att, $value );
				}
			}
		}

		return do_shortcode( '[tcpaccordion ' . $atts . ']' );
	}
}

new TCAccordion_Gutenberg_Block();