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
	 * IDs of accordions currently being rendered (guards against infinite nesting).
	 *
	 * @var int[]
	 */
	private static $render_stack = array();

	/**
	 * Current nesting depth of this renderer.
	 *
	 * @var int
	 */
	private $depth = 1;

	/**
	 * Wrapper element ID forced by a parent accordion (nested accordions only).
	 *
	 * @var string
	 */
	private $scope_override = '';

	/**
	 * Theme class forced by a parent accordion (nested accordions only).
	 *
	 * @var string
	 */
	private $theme_override = '';

	/**
	 * Per-placement setting overrides (shortcode attributes / Gutenberg block), keyed by meta key.
	 *
	 * @var array
	 */
	private $overrides = array();

	/**
	 * Items supplied directly by a caller (the Gutenberg Inline block) instead of a saved accordion.
	 *
	 * @var array
	 */
	private $injected_items = array();

	/**
	 * Layouts that can be chosen. The free plugin provides the stacked accordion and the two-column
	 * layout; add-ons can add more with the 'tcaccordion_layouts' filter.
	 *
	 * @return array Layout slug => label.
	 */
	public static function get_available_layouts() {
		$layouts = apply_filters(
			'tcaccordion_layouts',
			array(
				'accordion'  => __( 'Accordion (default, stacked)', 'accordions-wp' ),
				'two-column' => __( 'Two-column FAQ', 'accordions-wp' ),
			)
		);

		return is_array( $layouts ) && isset( $layouts['accordion'] ) ? $layouts : array( 'accordion' => __( 'Accordion (default, stacked)', 'accordions-wp' ) );
	}

	/**
	 * Whether the given accordion is being rendered right now (used by add-ons to avoid loops).
	 *
	 * @param int $post_id Accordion ID.
	 * @return bool
	 */
	public static function is_rendering( $post_id ) {
		return in_array( absint( $post_id ), self::$render_stack, true );
	}

	/**
	 * Read one setting the same way the renderer does (saved value, placement override or default).
	 *
	 * @param string $key     Setting key.
	 * @param string $default Default value.
	 * @return mixed
	 */
	public function get_setting( $key, $default = '' ) {
		return $this->get_meta_value( $key, $default );
	}

	/**
	 * Render these items instead of a saved accordion (used by the Gutenberg Inline block).
	 *
	 * @param array $items List of array( 'title' => string, 'description' => HTML ).
	 * @return void
	 */
	public function set_items( $items ) {
		$this->injected_items = is_array( $items ) ? $items : array();
	}

	/**
	 * Force the wrapper element ID (needed when there is no accordion post ID).
	 *
	 * @param string $scope_id Unique element ID.
	 * @return void
	 */
	public function set_scope_id( $scope_id ) {
		$this->scope_override = sanitize_html_class( $scope_id );
	}

	/**
	 * Override saved settings for this one placement (shortcode attributes, Gutenberg block).
	 *
	 * @param array $overrides Values keyed by meta key, already validated by the caller.
	 * @return void
	 */
	public function set_overrides( $overrides ) {
		$this->overrides = is_array( $overrides ) ? $overrides : array();
	}

	/**
	 * Mark this renderer as a nested accordion rendered inside a parent item.
	 *
	 * @param string $scope_id Unique wrapper ID.
	 * @param string $theme    Theme class inherited from the parent.
	 * @param int    $depth    Nesting depth.
	 * @return void
	 */
	public function set_nested_context( $scope_id, $theme, $depth ) {
		$this->scope_override = sanitize_html_class( $scope_id );
		$this->theme_override = sanitize_html_class( $theme );
		$this->depth          = max( 1, absint( $depth ) );
	}

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
		// 0. Per-placement overrides (shortcode attributes / Gutenberg block).
		if ( isset( $this->overrides[ $key ] ) && '' !== $this->overrides[ $key ] ) {
			return $this->overrides[ $key ];
		}

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
		if ( ! $this->post_id && ! $this->is_preview && empty( $this->injected_items ) ) {
			return '';
		}

		$items = $this->get_accordion_items();

		if ( empty( $items ) ) {
			return '<p>' . esc_html__( 'Nothing Found!!', 'accordions-wp' ) . '</p>';
		}

		$styles = $this->get_style_settings();
		$theme  = $this->get_theme_class();

		self::$render_stack[] = $this->post_id;
		$html                 = $this->build_html( $items, $styles, $theme );
		array_pop( self::$render_stack );

		return $html;
	}

	/**
	 * Retrieve and normalize accordion repeater items.
	 *
	 * @return array Normalized accordion items.
	 */
	private function get_accordion_items() {
		// Items handed in directly (Gutenberg Inline block).
		if ( ! empty( $this->injected_items ) ) {
			$injected = array();
			foreach ( $this->injected_items as $row ) {
				if ( is_array( $row ) ) {
					$injected[] = $this->extract_item_data( $row );
				}
			}
			return array_filter( $injected );
		}

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

		/**
		 * Lets add-ons replace the items (for example to build them from posts).
		 *
		 * @param array                $items    Items read from the accordion.
		 * @param TCAccordion_Renderer $renderer This renderer.
		 */
		$items = apply_filters( 'tcaccordion_items', array_filter( $items ), $this );

		return is_array( $items ) ? array_values( array_filter( $items ) ) : array();
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

		/**
		 * Lets add-ons keep extra per-item data (for example a nested accordion ID).
		 *
		 * @param array $data Normalized item with 'title' and 'description'.
		 * @param array $item Raw item as saved.
		 */
		return apply_filters(
			'tcaccordion_item_data',
			array(
				'title'       => $title,
				'description' => $description,
			),
			$item
		);
	}

	/**
	 * Fetch custom styling options with fallback database keys.
	 *
	 * @return array Style values map.
	 */
	private function get_style_settings() {
		// Behavior settings
		$auto_open_val = $this->get_meta_value( '_tcacc_auto_open_item', '1', array( 'custom_accordion_auto_open' ) );
		$closeable_val = $this->get_meta_value( '_tcacc_is_closeable', 'yes', array( 'custom_accordion_is_closeable' ) );

		$auto_open = ( '' !== $auto_open_val ) ? absint( $auto_open_val ) : 1;
		$closeable = ( 'no' === $closeable_val ) ? 'no' : 'yes';

		$close_others_val = $this->get_meta_value( '_tcacc_close_others', 'yes', array( 'custom_accordion_close_others' ) );
		$close_others     = ( 'no' === $close_others_val ) ? 'no' : 'yes';

		// Open event and animation speed can be changed by an add-on.
		$behavior = apply_filters(
			'tcaccordion_behavior_settings',
			array(
				'open_event' => 'click',
				'anim_speed' => 300,
			),
			$this
		);

		$open_event = ( is_array( $behavior ) && isset( $behavior['open_event'] ) && in_array( $behavior['open_event'], array( 'click', 'hover' ), true ) ) ? $behavior['open_event'] : 'click';
		$anim_speed = ( is_array( $behavior ) && ! empty( $behavior['anim_speed'] ) ) ? absint( $behavior['anim_speed'] ) : 300;

		// Use $this->post_id instead of $post_id
		$icon_style = $this->get_meta_value( '_tcacc_icon_style', 'chevron' );
		$icon_style = ! empty( $icon_style ) ? $icon_style : 'chevron';

		// Retrieve FAQ Schema Setting (defaults to 'no')
		$schema_val        = $this->get_meta_value( '_tcacc_enable_faq_schema', 'no', array( 'enable_faq_schema' ) );
		$enable_faq_schema = ( 'yes' === $schema_val ) ? 'yes' : 'no';

		// Nested accordions never output their own FAQ schema (avoids duplicate FAQPage blocks).
		if ( $this->depth > 1 ) {
			$enable_faq_schema = 'no';
		}

		// Layout (the free plugin has accordion and two-column; add-ons can add more).
		$layout = $this->get_meta_value( '_tcacc_layout', 'accordion' );
		$layout = array_key_exists( $layout, self::get_available_layouts() ) ? $layout : 'accordion';

		// Search box, Expand/Collapse All and Deep Link settings (all default to 'no').
		$show_search     = ( 'yes' === $this->get_meta_value( '_tcacc_show_search', 'no' ) ) ? 'yes' : 'no';
		$show_toggle_all = ( 'yes' === $this->get_meta_value( '_tcacc_show_toggle_all', 'no' ) ) ? 'yes' : 'no';
		$deep_link       = ( 'yes' === $this->get_meta_value( '_tcacc_deep_link', 'no' ) ) ? 'yes' : 'no';

		$search_placeholder = sanitize_text_field( (string) $this->get_meta_value( '_tcacc_search_placeholder', '' ) );
		if ( '' === $search_placeholder ) {
			$search_placeholder = __( 'Search questions...', 'accordions-wp' );
		}

		return array(
			'icon_color'           => $this->get_meta_value( 'custom_accordion_icon_color', '', array( 'custom_accordion_icon_color' ) ),
			'icon_bg_color'        => $this->get_meta_value( 'custom_accordion_icon_bg_color', '', array( 'custom_accordion_icon_bg_color' ) ),
			'title_bg'             => $this->get_meta_value( 'custom_accordion_title_bg_color', '', array( '_custom_accordion_title_bg_color' ) ),
			'title_color'          => $this->get_meta_value( 'custom_accordion_title_font_color', '', array( '_custom_accordion_title_font_color' ) ),
			'title_bg_hover'       => $this->get_meta_value( 'custom_accordion_title_bg_color_hover', '' ),
			'title_color_hover'    => $this->get_meta_value( 'custom_accordion_title_font_color_hover', '' ),
			'icon_color_hover'     => $this->get_meta_value( 'custom_accordion_icon_color_hover', '' ),
			'title_bg_active'      => $this->get_meta_value( 'custom_accordion_title_bg_color_active', '' ),
			'title_color_active'   => $this->get_meta_value( 'custom_accordion_title_font_color_active', '' ),
			'icon_color_active'    => $this->get_meta_value( 'custom_accordion_icon_color_active', '' ),
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
			'border_style'         => $this->get_meta_value( 'custom_accordion_border_style', 'none' ),
			'border_width'         => $this->get_meta_value( 'custom_accordion_border_width', '1' ),
			'border_color'         => $this->get_meta_value( 'custom_accordion_border_color', '' ),
			'enable_faq_schema'    => $enable_faq_schema, // Mapped to $styles['enable_faq_schema']
			'layout'               => $layout,
			'show_search'          => $show_search,
			'search_placeholder'   => $search_placeholder,
			'show_toggle_all'      => $show_toggle_all,
			'deep_link'            => $deep_link,
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
		if ( '' !== $this->theme_override ) {
			return $this->theme_override;
		}

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

		// Border Style Generation
		$border_style = ! empty( $styles['border_style'] ) ? $styles['border_style'] : 'none';
		if ( 'none' !== $border_style ) {
			$border_width = isset( $styles['border_width'] ) ? absint( $styles['border_width'] ) : 1;
			$border_color = ! empty( $styles['border_color'] ) ? sanitize_hex_color( $styles['border_color'] ) : '#e2e8f0';

			$css .= $selector . ' .responsive-accordion > li {';
			$css .= 'border-style: ' . esc_attr( $border_style ) . ' !important;';
			$css .= 'border-width: ' . $border_width . 'px !important;';
			$css .= 'border-color: ' . $border_color . ' !important;';
			$css .= '}';
		} else {
			$css .= $selector . ' .responsive-accordion > li { border: none !important; }';
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

		$css .= $this->build_state_css( $styles, $selector );
		$css .= $this->build_layout_css( $styles, $selector );

		return ! empty( $css ) ? '<style>' . $css . '</style>' : '';
	}

	/**
	 * Build the CSS for the hover and open (active) states of the item headers.
	 *
	 * Title and icon colors are printed inline on the elements, so these rules use
	 * !important. They target only this accordion's own items, never a nested accordion.
	 *
	 * @param array  $styles   Styles map.
	 * @param string $selector Wrapper selector (#id).
	 * @return string CSS rules.
	 */
	private function build_state_css( $styles, $selector ) {
		$head = $selector . ' > .responsive-accordion > li > .responsive-accordion-head';

		$hover  = $this->state_rule( array( $head . ':hover' ), $styles, 'hover' );
		$focus  = $this->state_rule( array( $head . ':focus-visible' ), $styles, 'hover' );
		$active = $this->state_rule( array( $head . '.active' ), $styles, 'active' );

		$css = '';

		if ( '' !== $hover ) {
			// Hover colors only on devices that really hover, so touch screens don't keep a stuck hover color.
			$css .= '@media (hover: hover) {' . $hover . '}';
			$css .= $focus;
		}

		// Printed last so the open state wins over the hover state.
		$css .= $active;

		return $css;
	}

	/**
	 * Build the declarations for one header state.
	 *
	 * @param string[] $selectors Header selectors for the state.
	 * @param array    $styles    Styles map.
	 * @param string   $state     'hover' or 'active'.
	 * @return string CSS rules or an empty string.
	 */
	private function state_rule( $selectors, $styles, $state ) {
		$bg   = ! empty( $styles[ 'title_bg_' . $state ] ) ? sanitize_hex_color( $styles[ 'title_bg_' . $state ] ) : '';
		$text = ! empty( $styles[ 'title_color_' . $state ] ) ? sanitize_hex_color( $styles[ 'title_color_' . $state ] ) : '';
		$icon = ! empty( $styles[ 'icon_color_' . $state ] ) ? sanitize_hex_color( $styles[ 'icon_color_' . $state ] ) : '';

		$targets = array(
			array( '', 'background-color', $bg ),
			array( ' .responsive-accordion-title', 'color', $text ),
			array( ' .responsive-accordion-icon', 'color', $icon ),
		);

		$out = '';

		foreach ( $targets as $target ) {
			if ( empty( $target[2] ) ) {
				continue;
			}

			$list = array();
			foreach ( $selectors as $selector ) {
				$list[] = $selector . $target[0];
			}

			$out .= implode( ',', $list ) . '{' . $target[1] . ':' . $target[2] . ' !important;}';
		}

		return $out;
	}

	/**
	 * Build the CSS for the non-default layouts (desktop only, 768px and up).
	 * On smaller screens every layout falls back to the normal stacked accordion.
	 *
	 * @param array  $styles   Styles map.
	 * @param string $selector Wrapper selector (#id).
	 * @return string CSS rules.
	 */
	private function build_layout_css( $styles, $selector ) {
		$layout = isset( $styles['layout'] ) ? $styles['layout'] : 'accordion';
		$css    = '';
		$ul     = $selector . ' > .responsive-accordion';
		$mq     = '@media (min-width: 768px) {';

		if ( 'two-column' === $layout ) {
			$css .= $mq;
			$css .= $ul . ' { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 20px; align-items: start; }';
			$css .= '}';
		}

		/**
		 * Lets add-ons print the CSS of their own layouts.
		 *
		 * @param string            $css      CSS built so far for the layout.
		 * @param string            $layout   Layout slug.
		 * @param array             $styles   Styles map.
		 * @param string            $selector Wrapper selector (#id).
		 * @param TCAccordion_Renderer $renderer This renderer.
		 */
		return apply_filters( 'tcaccordion_layout_css', $css, $layout, $styles, $selector, $this );
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
		$scope_id        = '' !== $this->scope_override ? $this->scope_override : 'tc-accordion-' . ( $this->post_id ? $this->post_id : wp_rand( 100, 999 ) );
		$dynamic_css     = $this->build_dynamic_css( $styles, $scope_id );
		$head_style      = $this->build_inline_css( $styles, 'head' );
		$icon_color      = $this->build_inline_css( $styles, 'icon' );
		$head_text_style = $this->build_inline_css( $styles, 'head_text' );
		$panel_style     = $this->build_inline_css( $styles, 'panel' );

		$icon_style  = ! empty( $styles['icon_style'] ) ? $styles['icon_style'] : 'chevron';
		$icon_pair   = $this->get_icon_classes( $icon_style, $styles );
		$closed_icon = $icon_pair['closed'];
		$open_icon   = $icon_pair['open'];

		$open_event = ! empty( $styles['open_event'] ) ? $styles['open_event'] : 'click';
		$anim_speed = ! empty( $styles['anim_speed'] ) ? intval( $styles['anim_speed'] ) : 300;

		$output  = $dynamic_css;
		$output .= '<div id="' . esc_attr( $scope_id ) . '" ';
		$output .= 'class="tc-accordion-wrapper container ' . esc_attr( $theme ) . ' tc-layout-' . esc_attr( $styles['layout'] ) . '" ';
		$output .= 'data-layout="' . esc_attr( $styles['layout'] ) . '" ';
		$output .= 'data-auto-open="' . esc_attr( $styles['auto_open'] ) . '" ';
		$output .= 'data-closeable="' . esc_attr( $styles['closeable'] ) . '" ';
		$output .= 'data-close-others="' . esc_attr( $styles['close_others'] ) . '" ';
		$output .= 'data-closed-icon="' . esc_attr( $closed_icon ) . '" ';
		$output .= 'data-open-icon="' . esc_attr( $open_icon ) . '" ';
		$output .= 'data-open-event="' . esc_attr( $open_event ) . '" ';
		$output .= 'data-anim-speed="' . esc_attr( $anim_speed ) . '" ';
		$output .= 'data-deep-link="' . esc_attr( $styles['deep_link'] ) . '" ';

		// Context handed to add-on hooks.
		$base_context = array(
			'scope_id' => $scope_id,
			'post_id'  => $this->post_id,
			'depth'    => $this->depth,
			'theme'    => $this->get_theme_class(),
		);

		/**
		 * Lets add-ons add attributes to the wrapper element. Returned values must already be escaped.
		 *
		 * @param string               $attributes Extra attributes (empty by default).
		 * @param array                $styles     Styles map.
		 * @param array                $context    scope_id, post_id, depth and theme.
		 * @param TCAccordion_Renderer $renderer   This renderer.
		 */
		$extra_attributes = apply_filters( 'tcaccordion_wrapper_attributes', '', $styles, $base_context, $this );
		if ( is_string( $extra_attributes ) && '' !== trim( $extra_attributes ) ) {
			$output .= trim( $extra_attributes ) . ' ';
		}

		$output .= 'style="width:100%; height:auto">';

		// Search box and Expand / Collapse All controls.
		$has_search     = ( 'yes' === $styles['show_search'] );
		$has_toggle_all = ( 'yes' === $styles['show_toggle_all'] );

		if ( $has_search || $has_toggle_all ) {
			$output .= '<div class="tc-accordion-tools">';

			if ( $has_search ) {
				$output .= '<input type="search" class="tc-accordion-search" autocomplete="off" ';
				$output .= 'placeholder="' . esc_attr( $styles['search_placeholder'] ) . '" ';
				$output .= 'aria-label="' . esc_attr__( 'Search accordion items', 'accordions-wp' ) . '" />';
			}

			if ( $has_toggle_all ) {
				$output .= '<div class="tc-accordion-toggle-all">';
				$output .= '<button type="button" class="tc-accordion-expand-all">' . esc_html__( 'Expand all', 'accordions-wp' ) . '</button>';
				$output .= '<button type="button" class="tc-accordion-collapse-all">' . esc_html__( 'Collapse all', 'accordions-wp' ) . '</button>';
				$output .= '</div>';
			}

			$output .= '</div>';
		}

		/**
		 * Lets add-ons print content between the search / tools bar and the list (for example filter buttons).
		 *
		 * @param string $html    Extra HTML (empty by default). Must already be escaped.
		 * @param array  $items   Items being rendered.
		 * @param array  $styles  Styles map.
		 * @param array  $context scope_id, post_id, depth and theme.
		 * @param TCAccordion_Renderer $renderer This renderer.
		 */
		$output .= apply_filters( 'tcaccordion_before_list', '', $items, $styles, $base_context, $this );

		$output .= '<ul class="responsive-accordion responsive-accordion-default bm-larger">';

		// Used to keep deep-link anchors unique inside this accordion.
		$used_anchors = array();

		$auto_open_index = (int) $styles['auto_open'];

		// Array to collect JSON-LD FAQ Schema
		$schema_faq_items = array();

		foreach ( $items as $index => $item ) {
			$item_num = $index + 1;
			$is_open  = ( $item_num === $auto_open_index );

			$title   = ! empty( $item['title'] ) ? $item['title'] : '';
			$details = $this->format_content( ! empty( $item['description'] ) ? $item['description'] : '' );

			$head_active_class = $is_open ? ' active' : '';
			$panel_display     = $is_open ? 'display:block;' : 'display:none;';
			$active_icon       = $is_open ? $open_icon : $closed_icon;

			$header_id = esc_attr( $scope_id . '-header-' . $item_num );
			$panel_id  = esc_attr( $scope_id . '-panel-' . $item_num );

			// Build Schema Array Item
			if ( ! empty( $title ) && ! empty( $details ) ) {
				$schema_faq_items[] = array(
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $title ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $details ),
					),
				);
			}

			$li_attr = '';
			if ( 'yes' === $styles['deep_link'] ) {
				$anchor_base = urldecode( sanitize_title( wp_strip_all_tags( $title ) ) );
				if ( '' === $anchor_base ) {
					$anchor_base = 'item-' . $item_num;
				}
				$anchor = $anchor_base;
				$suffix = 2;
				while ( isset( $used_anchors[ $anchor ] ) ) {
					$anchor = $anchor_base . '-' . $suffix;
					$suffix++;
				}
				$used_anchors[ $anchor ] = true;
				$li_attr                 = ' data-anchor="' . esc_attr( $anchor ) . '"';
			}

			$item_context             = $base_context;
			$item_context['item_num'] = $item_num;

			/**
			 * Lets add-ons add attributes to an item's <li> element. Returned values must already be escaped.
			 *
			 * @param string $attributes Attributes so far (for example the deep-link anchor).
			 * @param array  $item       Normalized item.
			 * @param array  $context    Context with item_num.
			 */
			$li_attr = apply_filters( 'tcaccordion_item_li_attributes', $li_attr, $item, $item_context );

			$li_class = $is_open ? ' class="tc-acc-open"' : '';
			$output  .= '<li' . $li_class . $li_attr . '>';

			// Header Element (WCAG 2.1 AA Compliant)
			$output .= '<div id="' . $header_id . '" ';
			$output .= 'class="responsive-accordion-head' . esc_attr( $head_active_class ) . '" ';
			$output .= 'role="button" ';
			$output .= 'tabindex="0" ';
			$output .= 'aria-expanded="' . ( $is_open ? 'true' : 'false' ) . '" ';
			$output .= 'aria-controls="' . $panel_id . '"' . $head_style . '>';

			$output .= '<span class="responsive-accordion-title"' . $head_text_style . '>' . apply_filters( 'tcaccordion_item_title_html', esc_html( $title ), $item, $item_context ) . '</span>';
			
			if ( 'none' !== $icon_style && ! empty( $active_icon ) ) {
				$output .= '<span class="responsive-accordion-icon" aria-hidden="true"' . $icon_color . '>';
				$output .= '<i class="fa-solid ' . esc_attr( $active_icon ) . ' fa-fw"></i>';
				$output .= '</span>';
			}

			$output .= '</div>';

			// Panel Element
			$output .= '<div id="' . $panel_id . '" ';
			$output .= 'role="region" ';
			$output .= 'aria-labelledby="' . $header_id . '" ';
			$output .= 'class="responsive-accordion-panel" style="' . esc_attr( $panel_display ) . '"' . $panel_style . '>';
			$output .= $details;
			/**
			 * Lets add-ons print extra content after the item description (for example a nested accordion).
			 *
			 * @param string $html    Extra HTML (empty by default).
			 * @param array  $item    Normalized item.
			 * @param array  $context scope_id, item_num, post_id, depth and theme of this accordion.
			 */
			$output .= apply_filters( 'tcaccordion_item_after_content', '', $item, $item_context );
			$output .= '</div>';
			$output .= '</li>';
		}

		$output .= '</ul>';

		if ( $has_search ) {
			$output .= '<p class="tc-accordion-no-results" role="status" hidden>' . esc_html__( 'No matching items found.', 'accordions-wp' ) . '</p>';
		}

		$output .= '</div>';

		// Injects JSON-LD Schema if enabled in metabox settings
		$enable_schema = isset( $styles['enable_faq_schema'] ) ? $styles['enable_faq_schema'] : 'yes';

		if ( 'yes' === $enable_schema && ! empty( $schema_faq_items ) ) {
			$schema_data = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $schema_faq_items,
			);

			$output .= '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
		}

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
function TCP_accordions_wordpress_table_body( $atts_or_id, $overrides = array() ) {
	$post_id = 0;

	if ( is_array( $atts_or_id ) ) {
		$atts    = shortcode_atts( array( 'id' => 0 ), $atts_or_id );
		$post_id = absint( $atts['id'] );
	} else {
		$post_id = absint( $atts_or_id );
	}

	$renderer = new TCAccordion_Renderer( $post_id );
	if ( ! empty( $overrides ) ) {
		$renderer->set_overrides( $overrides );
	}
	return $renderer->render();
}