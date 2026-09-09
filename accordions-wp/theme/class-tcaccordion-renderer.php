<?php
/**
 * Frontend Accordion Renderer Class.
 *
 * @package     TCAccordion
 * @subpackage  TCAccordion/Theme
 * @version     3.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles fetching metadata and rendering HTML for frontend shortcode & live previews.
 */
class TCAccordion_Renderer {

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	private $post_id = 0;

	/**
	 * Whether rendering in live preview mode.
	 *
	 * @var bool
	 */
	private $is_preview = false;

	/**
	 * Unsaved preview data array sent from AJAX.
	 *
	 * @var array
	 */
	private $preview_data = array();

	/**
	 * Constructor.
	 *
	 * @param int $post_id Post ID to render.
	 */
	public function __construct( $post_id = 0 ) {
		$this->post_id = absint( $post_id );
	}

	/**
	 * Inject live unsaved form data for instant preview rendering.
	 *
	 * @param array $data Serialized form values from AJAX.
	 * @return void
	 */
	public function set_preview_data( array $data ) {
		$this->preview_data = $data;
		$this->is_preview   = true;
	}

	/**
	 * Retrieve meta value checking preview data, primary key, and fallback meta keys.
	 *
	 * @param string $key          Primary form input name / meta key.
	 * @param mixed  $default      Default fallback value.
	 * @param array  $fallback_keys Alternative meta keys used in older database schemas.
	 * @return mixed Meta value.
	 */
	private function get_meta_value( $key, $default = '', $fallback_keys = array() ) {
		// 1. Check Live Preview Data (AJAX)
		if ( $this->is_preview ) {
			if ( isset( $this->preview_data[ $key ] ) && '' !== $this->preview_data[ $key ] ) {
				return $this->preview_data[ $key ];
			}
			foreach ( $fallback_keys as $alt_key ) {
				if ( isset( $this->preview_data[ $alt_key ] ) && '' !== $this->preview_data[ $alt_key ] ) {
					return $this->preview_data[ $alt_key ];
				}
			}
		}

		// 2. Check Saved Post Meta on Frontend
		if ( $this->post_id ) {
			$meta = get_post_meta( $this->post_id, $key, true );
			if ( '' !== $meta && false !== $meta ) {
				return $meta;
			}

			// Check alternative keys if primary key returns empty
			foreach ( $fallback_keys as $alt_key ) {
				$alt_meta = get_post_meta( $this->post_id, $alt_key, true );
				if ( '' !== $alt_meta && false !== $alt_meta ) {
					return $alt_meta;
				}
			}
		}

		return $default;
	}

	/**
	 * Main Render Method.
	 *
	 * @return string Rendered HTML output.
	 */
	public function render() {
		if ( ! $this->post_id && ! $this->is_preview ) {
			return '';
		}

		$items = $this->get_accordion_items();

		if ( empty( $items ) ) {
			return '<p>' . esc_html__( 'Nothing Found!!', 'tcaccordion' ) . '</p>';
		}

		$styles = $this->get_style_settings();
		$theme  = $this->get_theme_class();

		return $this->build_html( $items, $styles, $theme );
	}

	/**
	 * Retrieve and normalize accordion repeater items.
	 *
	 * @return array Normalized accordion items.
	 */
	private function get_accordion_items() {
		$raw_features = array();

		if ( $this->is_preview && isset( $this->preview_data['custom_accordion_wordpresspro_columns'] ) ) {
			$raw_features = $this->preview_data['custom_accordion_wordpresspro_columns'];
		} elseif ( $this->post_id ) {
			$raw_features = get_post_meta( $this->post_id, 'custom_accordion_wordpresspro_columns', true );

			if ( empty( $raw_features ) ) {
				$raw_features = get_post_meta( $this->post_id, 'custom_accordion_wordpresspro_columns', false );
			}
		}

		$items = array();

		if ( ! empty( $raw_features ) && is_array( $raw_features ) ) {
			foreach ( $raw_features as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				if ( isset( $row[0] ) && is_array( $row[0] ) ) {
					foreach ( $row as $sub_item ) {
						if ( is_array( $sub_item ) ) {
							$items[] = $this->extract_item_data( $sub_item );
						}
					}
				} else {
					$items[] = $this->extract_item_data( $row );
				}
			}
		}

		return array_filter( $items );
	}

	/**
	 * Normalize array keys across legacy and updated formats.
	 *
	 * @param array $item Raw meta item array.
	 * @return array Standardized array with 'title' and 'description'.
	 */
	private function extract_item_data( $item ) {
		$title = '';
		if ( isset( $item['title'] ) ) {
			$title = $item['title'];
		} elseif ( isset( $item['custom_accordions_pro_title'] ) ) {
			$title = $item['custom_accordions_pro_title'];
		}

		$description = '';
		if ( isset( $item['description'] ) ) {
			$description = $item['description'];
		} elseif ( isset( $item['custom_accordions_pro_details'] ) ) {
			$description = $item['custom_accordions_pro_details'];
		}

		if ( empty( $title ) && empty( $description ) ) {
			return array();
		}

		return array(
			'title'       => $title,
			'description' => $description,
		);
	}

	/**
	 * Fetch custom styling options with fallback database keys.
	 *
	 * @return array Style values map.
	 */
	private function get_style_settings() {
		return array(
			'title_bg'             => $this->get_meta_value( 'custom_accordion_title_bg_color', '', array( '_custom_accordion_title_bg_color' ) ),
			'title_color'          => $this->get_meta_value( 'custom_accordion_title_font_color', '', array( '_custom_accordion_title_font_color' ) ),
			'title_size'           => $this->get_meta_value( 'custom_accordion_title_font_size', '18', array( '_custom_accordion_title_font_size' ) ),
			'title_line_height'    => $this->get_meta_value( 'custom_accordion_title_line_height', '1.4', array( '_custom_accordion_title_line_height' ) ),
			'title_position'       => $this->get_meta_value( '_tpaccpro_wiki_acc_themes_title_position', 'left', array( 'custom_accordion_title_position' ) ),
			'icon_show'            => $this->get_meta_value( '_tpaccpro_wiki_acc_themes_show_hide_icons', '1', array( 'custom_accordion_show_hide_icons' ) ),
			'icon_position'        => $this->get_meta_value( '_tpaccpro_wiki_acc_themes_icon_position', '2', array( 'custom_accordion_icon_position' ) ),
			'content_bg'           => $this->get_meta_value( 'custom_accordion_content_bg_color', '', array( '_custom_accordion_content_bg_color' ) ),
			'content_color'        => $this->get_meta_value( 'custom_accordion_content_font_color', '', array( '_custom_accordion_content_font_color' ) ),
			'content_size'         => $this->get_meta_value( 'custom_accordion_content_font_size', '16', array( '_custom_accordion_content_font_size' ) ),
			'content_line_height'  => $this->get_meta_value( 'custom_accordion_content_line_height', '1.6', array( '_custom_accordion_content_line_height' ) ),
			'content_letter_space' => $this->get_meta_value( 'custom_accordion_content_letter_spacing', '0', array( '_custom_accordion_content_letter_spacing' ) ),
			'item_margin'          => $this->get_meta_value( '_tpaccpro_wiki_acc_theme_content_margin', '5', array( 'custom_accordion_item_margin' ) ),
			'padding'              => $this->get_meta_value( 'custom_accordion_content_padding', '15', array( '_custom_accordion_content_padding' ) ),
		);
	}

	/**
	 * Get theme option class name.
	 *
	 * @return string Theme CSS class name.
	 */
	private function get_theme_class() {
		$theme = $this->get_meta_value( 'custom_accordion_columns_post_themes', 'theme1', array( '_custom_accordion_columns_post_themes' ) );
		return ! empty( $theme ) ? sanitize_html_class( $theme ) : 'theme1';
	}

	/**
	 * Generate dynamic CSS block output for shortcode and live preview.
	 *
	 * @param array  $styles Styles map.
	 * @param string $scope_id Unique wrapper element ID.
	 * @return string Dynamic <style> block.
	 */
	private function build_dynamic_css( $styles, $scope_id ) {
		$css      = '';
		$selector = '#' . esc_attr( $scope_id );

		// 1. Item Margin
		if ( '' !== $styles['item_margin'] && null !== $styles['item_margin'] ) {
			$css .= $selector . ' .responsive-accordion > li { margin-bottom: ' . absint( $styles['item_margin'] ) . 'px !important; }';
		}

		// 2. Title Line Height
		if ( ! empty( $styles['title_line_height'] ) ) {
			$line_height = floatval( $styles['title_line_height'] );
			$css .= $selector . ' .responsive-accordion-head, ';
			$css .= $selector . ' .responsive-accordion-head span { line-height: ' . $line_height . ' !important; }';
		}

		// 3. Flex Container & Span Setup for Alignments
		$css .= $selector . ' .responsive-accordion-head { display: flex !important; align-items: center !important; position: relative !important; }';
		$css .= $selector . ' .responsive-accordion-head span { flex: 1 1 auto !important; width: 100% !important; }';

		// 4. Title Text Alignment
		$align = in_array( $styles['title_position'], array( 'left', 'center', 'right' ), true ) ? $styles['title_position'] : 'left';
		$css  .= $selector . ' .responsive-accordion-head span { text-align: ' . esc_attr( $align ) . ' !important; }';

		// 5. Hide / Show Icon
		if ( '2' === (string) $styles['icon_show'] ) {
			$css .= $selector . ' .responsive-accordion-head i { display: none !important; }';
		} else {
			// Icon Position
			if ( '1' === (string) $styles['icon_position'] ) {
				$css .= $selector . ' .responsive-accordion-head i { order: -1 !important; margin-right: 12px !important; margin-left: 0 !important; float: none !important; }';
				$css .= $selector . ' .responsive-accordion-head span { order: 1 !important; }';
			} else {
				$css .= $selector . ' .responsive-accordion-head i { order: 2 !important; margin-left: 12px !important; margin-right: 0 !important; float: none !important; }';
				$css .= $selector . ' .responsive-accordion-head span { order: 1 !important; }';
			}

			// Center Alignment Icon Position Fix
			if ( 'center' === $align ) {
				$css .= $selector . ' .responsive-accordion-head i { position: absolute !important; right: 15px !important; top: 50% !important; transform: translateY(-50%) !important; }';
				if ( '1' === (string) $styles['icon_position'] ) {
					$css .= $selector . ' .responsive-accordion-head i { right: auto !important; left: 15px !important; }';
				}
			}
		}

		// 6. Content Typography, Color, Line Height, and Letter Spacing
		if ( ! empty( $styles['content_size'] ) || ! empty( $styles['content_color'] ) || ! empty( $styles['content_line_height'] ) || '' !== $styles['content_letter_space'] ) {
			$css .= $selector . ' .responsive-accordion-panel, ';
			$css .= $selector . ' .responsive-accordion-panel p, ';
			$css .= $selector . ' .responsive-accordion-panel span, ';
			$css .= $selector . ' .responsive-accordion-panel li {';

			if ( ! empty( $styles['content_size'] ) ) {
				$css .= 'font-size:' . absint( $styles['content_size'] ) . 'px !important;';
			}
			if ( ! empty( $styles['content_color'] ) ) {
				$css .= 'color:' . sanitize_hex_color( $styles['content_color'] ) . ' !important;';
			}
			if ( ! empty( $styles['content_line_height'] ) ) {
				$css .= 'line-height:' . floatval( $styles['content_line_height'] ) . ' !important;';
			}
			if ( '' !== $styles['content_letter_space'] && null !== $styles['content_letter_space'] ) {
				$css .= 'letter-spacing:' . floatval( $styles['content_letter_space'] ) . 'px !important;';
			}

			$css .= '}';
		}

		return ! empty( $css ) ? '<style>' . $css . '</style>' : '';
	}

	/**
	 * Build inline CSS styles for static element properties.
	 *
	 * @param array  $styles Style options map.
	 * @param string $type   Target element ('head', 'head_text', or 'panel').
	 * @return string Safe inline style string.
	 */
	private function build_inline_css( $styles, $type ) {
		$css = array();

		if ( 'head' === $type && ! empty( $styles['title_bg'] ) ) {
			$css[] = 'background-color:' . sanitize_hex_color( $styles['title_bg'] );
		}

		if ( 'head_text' === $type ) {
			if ( ! empty( $styles['title_color'] ) ) {
				$css[] = 'color:' . sanitize_hex_color( $styles['title_color'] );
			}
			if ( ! empty( $styles['title_size'] ) ) {
				$css[] = 'font-size:' . absint( $styles['title_size'] ) . 'px';
			}
		}

		if ( 'panel' === $type ) {
			if ( ! empty( $styles['content_bg'] ) ) {
				$css[] = 'background-color:' . sanitize_hex_color( $styles['content_bg'] );
			}
			if ( '' !== $styles['padding'] && null !== $styles['padding'] ) {
				$css[] = 'padding:' . absint( $styles['padding'] ) . 'px';
			}
		}

		return ! empty( $css ) ? ' style="' . esc_attr( implode( ';', $css ) ) . '"' : '';
	}

	/**
	 * Sanitize and filter body details content (OEmbed, Shortcodes, HTML).
	 *
	 * @param string $content Raw content.
	 * @return string Processed content.
	 */
	private function format_content( $content ) {
		global $wp_embed;

		$allowed_html           = wp_kses_allowed_html( 'post' );
		$allowed_html['iframe'] = array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'frameborder'     => true,
			'allow'           => true,
			'allowfullscreen' => true,
			'title'           => true,
		);

		if ( is_object( $wp_embed ) ) {
			$content = $wp_embed->autoembed( $content );
			$content = $wp_embed->run_shortcode( $content );
		}

		$content = do_shortcode( $content );
		$content = wp_kses( $content, $allowed_html );

		return wpautop( $content );
	}

	/**
	 * Build final HTML string for output.
	 *
	 * @param array  $items  Normalized items.
	 * @param array  $styles Styles map.
	 * @param string $theme  Theme CSS class.
	 * @return string HTML output.
	 */
	private function build_html( $items, $styles, $theme ) {
		$scope_id        = 'tc-accordion-' . ( $this->post_id ? $this->post_id : rand( 100, 999 ) );
		$dynamic_css     = $this->build_dynamic_css( $styles, $scope_id );
		$head_style      = $this->build_inline_css( $styles, 'head' );
		$head_text_style = $this->build_inline_css( $styles, 'head_text' );
		$panel_style     = $this->build_inline_css( $styles, 'panel' );

		$output  = $dynamic_css;
		$output .= '<div id="' . esc_attr( $scope_id ) . '" class="container ' . esc_attr( $theme ) . '" style="width:100%; height:auto">';
		$output .= '<ul class="responsive-accordion responsive-accordion-default bm-larger">';

		foreach ( $items as $item ) {
			$title   = ! empty( $item['title'] ) ? $item['title'] : '';
			$details = $this->format_content( ! empty( $item['description'] ) ? $item['description'] : '' );

			$output .= '<li>';
			$output .= '<div class="responsive-accordion-head"' . $head_style . '>';
			$output .= '<span' . $head_text_style . '>' . esc_html( $title ) . '</span>';
			$output .= '<i class="fa fa-chevron-down responsive-accordion-plus fa-fw"></i><i class="fa fa-chevron-up responsive-accordion-minus fa-fw"></i>';
			$output .= '</div>';

			$output .= '<div class="responsive-accordion-panel"' . $panel_style . '>';
			$output .= $details;
			$output .= '</div>';
			$output .= '</li>';
		}

		$output .= '</ul>';
		$output .= '</div>';

		return $output;
	}
}

/**
 * Backward compatibility shortcode callback wrapper.
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered accordion HTML.
 */
function TCP_accordions_wordpress_table_body( $atts_or_id ) {
	$post_id = 0;

	if ( is_array( $atts_or_id ) ) {
		$atts    = shortcode_atts( array( 'id' => 0 ), $atts_or_id );
		$post_id = absint( $atts['id'] );
	} else {
		$post_id = absint( $atts_or_id );
	}

	$renderer = new TCAccordion_Renderer( $post_id );
	return $renderer->render();
}