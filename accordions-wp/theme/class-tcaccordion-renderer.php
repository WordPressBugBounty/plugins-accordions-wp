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
			return '<p>' . esc_html__( 'Nothing Found!!', 'accordions-wp' ) . '</p>';
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
		// Default values for Free plan
		$auto_open    = 1;
		$closeable    = 'yes';
		$close_others = 'yes';
		$open_event   = 'click';
		$anim_speed   = 300;

		// Retrieve Pro options if licensed
		if ( function_exists( 'tc_acc_is_pro' ) && tc_acc_is_pro() ) {
			$auto_open_val = $this->get_meta_value( '_tcacc_auto_open_item', '1', array( 'custom_accordion_auto_open' ) );
			$closeable_val = $this->get_meta_value( '_tcacc_is_closeable', 'yes', array( 'custom_accordion_is_closeable' ) );

			$auto_open = ( '' !== $auto_open_val ) ? absint( $auto_open_val ) : 1;
			$closeable = ( 'no' === $closeable_val ) ? 'no' : 'yes';

			$close_others_val = $this->get_meta_value( '_tcacc_close_others', 'yes', array( 'custom_accordion_close_others' ) );
			$close_others     = ( 'no' === $close_others_val ) ? 'no' : 'yes';

			// New Pro features
			$open_event_val = $this->get_meta_value( '_tcacc_open_event', 'click' );
			$open_event     = in_array( $open_event_val, array( 'click', 'hover' ), true ) ? $open_event_val : 'click';

			$anim_speed_val = $this->get_meta_value( '_tcacc_anim_speed', '300' );
			$anim_speed     = ! empty( $anim_speed_val ) ? absint( $anim_speed_val ) : 300;
		}

		// Use $this->post_id instead of $post_id
		$icon_style = get_post_meta( $this->post_id, '_tcacc_icon_style', true );
		$icon_style = ! empty( $icon_style ) ? $icon_style : 'chevron';

		return array(
			'icon_color'           => $this->get_meta_value( 'custom_accordion_icon_color', '', array( 'custom_accordion_icon_color' ) ),
			'icon_bg_color'        => $this->get_meta_value( 'custom_accordion_icon_bg_color', '', array( 'custom_accordion_icon_bg_color' ) ),
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
			'auto_open'            => $auto_open,
			'closeable'            => $closeable,
			'close_others'         => $close_others,
			'icon_style'           => $icon_style,
			'open_event'           => $open_event,
			'anim_speed'           => $anim_speed,
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

		// Retrieve active theme class directly using helper method
		$current_theme = $this->get_theme_class();
		$is_theme_1_or_2 = in_array( $current_theme, array( 'theme1', 'theme2', 'theme-dark', 'theme-flat' ), true );

		// 1. Item Margin
		if ( isset( $styles['item_margin'] ) && '' !== (string) $styles['item_margin'] ) {
			$css .= $selector . ' .responsive-accordion > li { margin-bottom: ' . absint( $styles['item_margin'] ) . 'px !important; }';
		}

		// 2. Title Line Height
		if ( ! empty( $styles['title_line_height'] ) ) {
			$line_height = floatval( $styles['title_line_height'] );
			$css .= $selector . ' .responsive-accordion-head, ';
			$css .= $selector . ' .responsive-accordion-title { line-height: ' . $line_height . ' !important; }';
		}

		// 3. Base Header Layout & Universal Reset
		$css .= $selector . ' .responsive-accordion-head { display: flex !important; align-items: center !important; position: relative !important; width: 100% !important; box-sizing: border-box !important; }';
		$css .= $selector . ' .responsive-accordion-title { display: block !important; flex: 1 1 auto !important; width: 100% !important; box-sizing: border-box !important; padding: 0 !important; }';
		$css .= $selector . ' .responsive-accordion-icon { display: inline-flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; box-sizing: border-box !important; }';

		// Universal Icon Reset: Neutralize float, absolute positioning, height/line-height across all themes
		$css .= $selector . ' .responsive-accordion-icon, ';
		$css .= $selector . ' .responsive-accordion-head i, ';
		$css .= $selector . ' .responsive-accordion-head svg { float: none !important; position: static !important; inset: auto !important; transform: none !important; margin: 0 !important; }';
		$css .= $selector . ' .responsive-accordion-head i, ';
		$css .= $selector . ' .responsive-accordion-head svg { width: auto !important; height: auto !important; line-height: 1 !important; display: inline-block !important; }';

		// 4. Horizontal Padding Logic (Explicitly Scoped)
		if ( $is_theme_1_or_2 ) {
			// Zero out padding completely for theme1 and theme2
			$css .= $selector . ' .responsive-accordion-icon { padding: 0 !important; }';
			$css .= $selector . '.theme1 .responsive-accordion-icon, ' . $selector . '.theme2 .responsive-accordion-icon { padding: 0 !important; }';
		} else {
			// Apply width & horizontal padding for theme3, theme4, theme5, theme-flat, etc.
			$css .= $selector . ' .responsive-accordion-icon { width: auto !important; min-width: 0 !important; }';

			if ( isset( $styles['icon_padding'] ) && '' !== (string) $styles['icon_padding'] ) {
				$padding_val = is_numeric( $styles['icon_padding'] ) ? absint( $styles['icon_padding'] ) . 'px' : sanitize_text_field( $styles['icon_padding'] );
				$css .= $selector . ' .responsive-accordion-icon { padding: ' . $padding_val . ' !important; }';
			} else {
				$css .= $selector . ' .responsive-accordion-icon { padding-left: 12px !important; padding-right: 12px !important; }';
			}
		}

		// Icon Box Width Override
		if ( isset( $styles['icon_bg_width'] ) && '' !== (string) $styles['icon_bg_width'] ) {
			$icon_width = absint( $styles['icon_bg_width'] );
			$css .= $selector . ' .responsive-accordion-icon { width: ' . $icon_width . 'px !important; min-width: ' . $icon_width . 'px !important; }';
		}

		// 5. Title Text Alignment
		$align = in_array( $styles['title_position'], array( 'left', 'center', 'right' ), true ) ? $styles['title_position'] : 'left';
		$css  .= $selector . ' .responsive-accordion-title { text-align: ' . esc_attr( $align ) . ' !important; }';

		// 6. Hide / Show Icon & Position Logic
		if ( '2' === (string) $styles['icon_show'] ) {
			$css .= $selector . ' .responsive-accordion-icon { display: none !important; }';
		} else {
			$icon_pos = (string) $styles['icon_position'];

			if ( 'center' === $align ) {
				// CENTER TEXT: Absolute position icon container so title remains centered
				$css .= $selector . ' .responsive-accordion-icon { position: absolute !important; top: 50% !important; transform: translateY(-50%) !important; z-index: 2 !important; }';

				if ( '1' === $icon_pos ) {
					// Icon LEFT
					$css .= $selector . ' .responsive-accordion-icon { left: 15px !important; right: auto !important; }';
				} else {
					// Icon RIGHT
					$css .= $selector . ' .responsive-accordion-icon { right: 15px !important; left: auto !important; }';
				}
			} else {
				// LEFT/RIGHT TEXT: Flex ordering
				if ( '1' === $icon_pos ) {
					// Icon LEFT
					$css .= $selector . ' .responsive-accordion-icon { order: 1 !important; margin-right: 12px !important; margin-left: 0 !important; }';
					$css .= $selector . ' .responsive-accordion-title { order: 2 !important; }';
				} else {
					// Icon RIGHT
					$css .= $selector . ' .responsive-accordion-icon { order: 2 !important; margin-left: 12px !important; margin-right: 0 !important; }';
					$css .= $selector . ' .responsive-accordion-title { order: 1 !important; }';
				}
			}
		}

		// 7. Content Typography, Color, Line Height, and Letter Spacing
		if ( ! empty( $styles['content_size'] ) || ! empty( $styles['content_color'] ) || ! empty( $styles['content_line_height'] ) || ( isset( $styles['content_letter_space'] ) && '' !== (string) $styles['content_letter_space'] ) ) {
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
			if ( isset( $styles['content_letter_space'] ) && '' !== (string) $styles['content_letter_space'] ) {
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

		if ( 'icon' === $type ) {
			if ( ! empty( $styles['icon_color'] ) ) {
				$css[] = 'color:' . sanitize_hex_color( $styles['icon_color'] );
			}

			// Exclude background-color specifically for theme1 and theme2
			$current_theme = $this->get_theme_class();
			$is_theme_1_or_2 = in_array( $current_theme, array( 'theme1', 'theme2', 'theme-dark', 'theme-flat' ), true );

			if ( ! $is_theme_1_or_2 && ! empty( $styles['icon_bg_color'] ) ) {
				$css[] = 'background-color:' . sanitize_hex_color( $styles['icon_bg_color'] );
			}
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

	private function build_html( $items, $styles, $theme ) {
		$scope_id 		 = 'tc-accordion-' . ( $this->post_id ? $this->post_id : wp_rand( 100, 999 ) );
		// $scope_id        = 'tc-accordion-' . ( $this->post_id ? $this->post_id : rand( 100, 999 ) );
		$dynamic_css     = $this->build_dynamic_css( $styles, $scope_id );
		$head_style      = $this->build_inline_css( $styles, 'head' );
		$icon_color      = $this->build_inline_css( $styles, 'icon' );
		$head_text_style = $this->build_inline_css( $styles, 'head_text' );
		$panel_style     = $this->build_inline_css( $styles, 'panel' );

		// Resolve dynamic icons based on $styles['icon_style']
		$icon_style  = ! empty( $styles['icon_style'] ) ? $styles['icon_style'] : 'chevron';
		$icon_pair   = $this->get_icon_classes( $icon_style, $styles );
		$closed_icon = $icon_pair['closed'];
		$open_icon   = $icon_pair['open'];

		$open_event = ! empty( $styles['open_event'] ) ? $styles['open_event'] : 'click';
		$anim_speed = ! empty( $styles['anim_speed'] ) ? intval( $styles['anim_speed'] ) : 300;

		$output  = $dynamic_css;
		$output .= '<div id="' . esc_attr( $scope_id ) . '" ';
		$output .= 'class="tc-accordion-wrapper container ' . esc_attr( $theme ) . '" ';
		$output .= 'data-auto-open="' . esc_attr( $styles['auto_open'] ) . '" ';
		$output .= 'data-closeable="' . esc_attr( $styles['closeable'] ) . '" ';
		$output .= 'data-close-others="' . esc_attr( $styles['close_others'] ) . '" ';
		$output .= 'data-closed-icon="' . esc_attr( $closed_icon ) . '" ';
		$output .= 'data-open-icon="' . esc_attr( $open_icon ) . '" ';
		$output .= 'data-open-event="' . esc_attr( $open_event ) . '" ';
		$output .= 'data-anim-speed="' . esc_attr( $anim_speed ) . '" ';
		$output .= 'style="width:100%; height:auto">';
		
		$output .= '<ul class="responsive-accordion responsive-accordion-default bm-larger">';

		$auto_open_index = (int) $styles['auto_open']; // 1-based index

		foreach ( $items as $index => $item ) {
			$item_num = $index + 1; // Convert 0-based foreach index to 1-based
			$is_open  = ( $item_num === $auto_open_index );

			$title   = ! empty( $item['title'] ) ? $item['title'] : '';
			$details = $this->format_content( ! empty( $item['description'] ) ? $item['description'] : '' );

			$head_active_class = $is_open ? ' active' : '';
			$panel_display     = $is_open ? 'display:block;' : 'display:none;';
			$active_icon       = $is_open ? $open_icon : $closed_icon;


			// Generate unique IDs for header and panel accessibility linking
			$header_id = esc_attr( $scope_id . '-header-' . $item_num );
			$panel_id  = esc_attr( $scope_id . '-panel-' . $item_num );

			$output .= '<li>';


			$output .= '<div id="' . $header_id . '" ';
			$output .= 'class="responsive-accordion-head' . esc_attr( $head_active_class ) . '" ';
			$output .= 'role="button" ';
			$output .= 'tabindex="0" ';
			$output .= 'aria-expanded="' . ( $is_open ? 'true' : 'false' ) . '" ';
			$output .= 'aria-controls="' . $panel_id . '"' . $head_style . '>';

			$output .= '<span class="responsive-accordion-title"' . $head_text_style . '>' . esc_html( $title ) . '</span>';
			
			// ACCESSIBILITY FIX: Added aria-hidden="true" so screen readers don't read out decorative font icons
			if ( 'none' !== $icon_style && ! empty( $active_icon ) ) {
				$output .= '<span class="responsive-accordion-icon" aria-hidden="true"' . $icon_color . '>';
				$output .= '<i class="fa-solid ' . esc_attr( $active_icon ) . ' fa-fw"></i>';
				$output .= '</span>';
			}

			$output .= '</div>';

			// ACCESSIBILITY FIX: Added id, role="region", and aria-labelledby linking back to header
			$output .= '<div id="' . $panel_id . '" ';
			$output .= 'role="region" ';
			$output .= 'aria-labelledby="' . $header_id . '" ';
			$output .= 'class="responsive-accordion-panel" style="' . esc_attr( $panel_display ) . '"' . $panel_style . '>';
			$output .= $details;
			$output .= '</div>';
			$output .= '</li>';
		}

		$output .= '</ul>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Helper function to map selected style to Font Awesome classes.
	 */
	private function get_icon_classes( $style, $styles = array() ) {
		$icons = array(
			'plus_minus' => array( 'closed' => 'fa-plus', 'open' => 'fa-minus' ),
			'chevron'    => array( 'closed' => 'fa-chevron-down', 'open' => 'fa-chevron-up' ),
			'angle'      => array( 'closed' => 'fa-angle-right', 'open' => 'fa-angle-down' ),
			'caret'      => array( 'closed' => 'fa-caret-right', 'open' => 'fa-caret-down' ),
			'folder'     => array( 'closed' => 'fa-folder', 'open' => 'fa-folder-open' ),
			'custom'     => array(
				'closed' => ! empty( $styles['custom_closed_icon'] ) ? $styles['custom_closed_icon'] : 'fa-plus',
				'open'   => ! empty( $styles['custom_open_icon'] ) ? $styles['custom_open_icon'] : 'fa-minus',
			),
			'none'       => array( 'closed' => '', 'open' => '' ),
		);

		return isset( $icons[ $style ] ) ? $icons[ $style ] : $icons['chevron'];
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