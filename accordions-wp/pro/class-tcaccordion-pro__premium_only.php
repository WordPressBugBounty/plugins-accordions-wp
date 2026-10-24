<?php
/**
 * Pro features for Accordion-WP.
 *
 * The "__premium_only" part of this file name makes Freemius remove the file from the free
 * (WordPress.org) version. It is loaded from custom-accordion-wp.php inside a block that is
 * also removed from the free version, so nothing in the free plugin can run these features.
 *
 * Pro features in this file:
 *  - Tabs and Horizontal layouts
 *  - Nested accordions
 *  - Open event (hover) and animation speed
 *  - Per-item icons and badges, groups / category filter tabs (class-tcaccordion-pro-items)
 *  - Dynamic content from posts or ACF (class-tcaccordion-pro-dynamic)
 *  - Analytics (class-tcaccordion-pro-analytics)
 *  - WooCommerce category rules (class-tcaccordion-pro-woo)
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers every Pro feature through the hooks the free plugin provides.
 */
class TCAccordion_Pro {

	/**
	 * Maximum nesting depth (1 = top level only, 3 = two levels of nesting).
	 *
	 * @var int
	 */
	const MAX_NESTING_DEPTH = 3;

	/**
	 * Cached list of accordions that can be nested.
	 *
	 * @var array|null
	 */
	private $nested_choices = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		// Layouts.
		add_filter( 'tcaccordion_layouts', array( $this, 'add_layouts' ) );
		add_filter( 'tcaccordion_layout_css', array( $this, 'layout_css' ), 10, 5 );
		add_action( 'tcaccordion_layout_settings', array( $this, 'render_height_setting' ) );
		add_action( 'tcaccordion_enqueue_assets', array( $this, 'enqueue_assets' ) );

		// Behavior (open event, animation speed) and settings saving.
		add_filter( 'tcaccordion_behavior_settings', array( $this, 'behavior_settings' ), 10, 2 );
		add_action( 'tcaccordion_save_settings', array( $this, 'save_settings' ) );

		// Nested accordions.
		add_filter( 'tcaccordion_item_data', array( $this, 'keep_item_data' ), 10, 2 );
		add_filter( 'tcaccordion_admin_item', array( $this, 'keep_item_data' ), 10, 2 );
		add_filter( 'tcaccordion_sanitize_item', array( $this, 'sanitize_item' ), 10, 3 );
		add_action( 'tcaccordion_item_extra_fields', array( $this, 'render_nested_field' ), 10, 2 );
		add_filter( 'tcaccordion_item_after_content', array( $this, 'render_nested' ), 10, 3 );

		// Gutenberg Inline block settings.
		add_filter( 'tcaccordion_inline_option_schema', array( $this, 'inline_option_schema' ) );
	}

	// -------------------------------------------------------------------------
	// Layouts
	// -------------------------------------------------------------------------

	/**
	 * Add the Pro layouts to the layout list.
	 *
	 * @param array $layouts Layout slug => label.
	 * @return array
	 */
	public function add_layouts( $layouts ) {
		$layouts['tabs']       = __( 'Tabs on desktop, accordion on mobile', 'accordions-wp' );
		$layouts['horizontal'] = __( 'Horizontal accordion', 'accordions-wp' );

		return $layouts;
	}

	/**
	 * CSS for the Tabs and Horizontal layouts (screens 768px and wider).
	 *
	 * @param string               $css      CSS built so far.
	 * @param string               $layout   Layout slug.
	 * @param array                $styles   Styles map.
	 * @param string               $selector Wrapper selector (#id).
	 * @param TCAccordion_Renderer $renderer Renderer.
	 * @return string
	 */
	public function layout_css( $css, $layout, $styles, $selector, $renderer ) {
		$ul    = $selector . ' > .responsive-accordion';
		$li    = $ul . ' > li';
		$head  = $li . ' > .responsive-accordion-head';
		$panel = $li . ' > .responsive-accordion-panel';
		$mq    = '@media (min-width: 768px) {';

		if ( 'horizontal' === $layout ) {
			$height = absint( $renderer->get_setting( '_tcacc_horizontal_height', '380' ) );
			$height = max( 200, min( 900, $height ? $height : 380 ) );
			$speed  = ! empty( $styles['anim_speed'] ) ? absint( $styles['anim_speed'] ) : 300;

			$css .= $mq;
			$css .= $ul . ' { display: flex !important; flex-direction: row; align-items: stretch; gap: 8px; height: ' . $height . 'px; }';
			$css .= $li . ' { display: flex !important; flex-direction: row; flex: 0 0 64px; min-width: 0; margin-bottom: 0 !important; overflow: hidden; transition: flex-grow ' . $speed . 'ms ease; }';
			$css .= $li . '.tc-acc-open { flex-grow: 1; }';
			$css .= $li . '.tc-acc-filtered-out, ' . $li . '.tc-acc-hidden { display: none !important; }';
			$css .= $head . ' { flex: 0 0 64px; width: 64px !important; height: auto !important; flex-direction: column !important; justify-content: flex-start !important; align-items: center !important; padding: 16px 0 !important; }';
			$css .= $head . ' > .responsive-accordion-icon { order: 0 !important; margin-bottom: 12px !important; }';
			$css .= $head . ' > .responsive-accordion-title { order: 1 !important; flex: 0 1 auto !important; width: auto !important; writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: start !important; }';
			$css .= $panel . ' { flex: 1 1 0; min-width: 0; width: auto !important; overflow: auto; box-sizing: border-box; }';
			$css .= '}';
		}

		if ( 'tabs' === $layout ) {
			$border       = '';
			$border_style = ! empty( $styles['border_style'] ) ? $styles['border_style'] : 'none';

			if ( 'none' !== $border_style ) {
				$border_width = isset( $styles['border_width'] ) ? absint( $styles['border_width'] ) : 1;
				$border_color = ! empty( $styles['border_color'] ) ? sanitize_hex_color( $styles['border_color'] ) : '';
				$border_color = $border_color ? $border_color : '#e2e8f0';
				$border       = 'border: ' . $border_width . 'px ' . esc_attr( $border_style ) . ' ' . $border_color . ' !important;';
			}

			$css .= $mq;
			$css .= $ul . ' { display: flex !important; flex-wrap: wrap; align-items: flex-end; column-gap: 6px; }';
			$css .= $li . ' { display: contents !important; }';
			$css .= $li . '.tc-acc-filtered-out, ' . $li . '.tc-acc-hidden { display: none !important; }';
			$css .= $head . ' { order: 1; flex: 0 0 auto; width: auto !important; margin: 0 !important; }';
			$css .= $head . ' > .responsive-accordion-title { width: auto !important; flex: 0 0 auto !important; }';
			$css .= $head . ' > .responsive-accordion-icon { display: none !important; }';
			$css .= $panel . ' { order: 2; flex: 0 0 100%; width: 100% !important; box-sizing: border-box; ' . $border . ' }';
			$css .= '}';
		}

		return $css;
	}

	/**
	 * Height field for the Horizontal layout (Settings tab).
	 *
	 * @param WP_Post $post Accordion post.
	 * @return void
	 */
	public function render_height_setting( $post ) {
		$height = absint( get_post_meta( $post->ID, '_tcacc_horizontal_height', true ) );
		$height = $height ? $height : 380;
		?>
		<tr valign="top">
			<th scope="row">
				<label for="_tcacc_horizontal_height"><?php esc_html_e( 'Horizontal Height (px)', 'accordions-wp' ); ?></label>
			</th>
			<td>
				<input type="number" name="_tcacc_horizontal_height" id="_tcacc_horizontal_height" min="200" max="900" step="10" value="<?php echo esc_attr( $height ); ?>" class="small-text" />
				<p class="description">
					<?php esc_html_e( 'Height of the Horizontal layout. Only used when Layout is set to Horizontal accordion.', 'accordions-wp' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Load the Pro scripts and styles together with the accordion script.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		static $localized = false;

		wp_enqueue_script(
			'tcaccordion-pro-layouts',
			TCACCORDION_PLUGIN_URL . 'assets/js/pro/accordion-layouts__premium_only.js',
			array( 'jquery', 'tcaccordion-script' ),
			TCACCORDION_VERSION,
			true
		);

		wp_enqueue_script(
			'tcaccordion-pro-features',
			TCACCORDION_PLUGIN_URL . 'assets/js/pro/accordion-features__premium_only.js',
			array( 'jquery', 'tcaccordion-script' ),
			TCACCORDION_VERSION,
			true
		);

		wp_enqueue_style(
			'tcaccordion-pro',
			TCACCORDION_PLUGIN_URL . 'assets/css/pro/accordion-pro__premium_only.css',
			array( 'tcaccordion-responsive' ),
			TCACCORDION_VERSION
		);

		if ( ! $localized ) {
			wp_localize_script(
				'tcaccordion-pro-features',
				'tcaccProData',
				array(
					'restUrl' => esc_url_raw( rest_url( 'tcaccordion/v1/track' ) ),
				)
			);
			$localized = true;
		}
	}

	// -------------------------------------------------------------------------
	// Behavior
	// -------------------------------------------------------------------------

	/**
	 * Open event and animation speed.
	 *
	 * @param array                $behavior Defaults (open_event, anim_speed).
	 * @param TCAccordion_Renderer $renderer Renderer.
	 * @return array
	 */
	public function behavior_settings( $behavior, $renderer ) {
		$event = $renderer->get_setting( '_tcacc_open_event', 'click' );
		$speed = absint( $renderer->get_setting( '_tcacc_anim_speed', '300' ) );

		$behavior['open_event'] = in_array( $event, array( 'click', 'hover' ), true ) ? $event : 'click';
		$behavior['anim_speed'] = $speed ? $speed : 300;

		return $behavior;
	}

	/**
	 * Save the Pro settings. The nonce and permissions are checked by the free plugin before this runs.
	 *
	 * @param int $post_id Accordion ID.
	 * @return void
	 */
	public function save_settings( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['_tcacc_horizontal_height'] ) ) {
			$height = absint( wp_unslash( $_POST['_tcacc_horizontal_height'] ) );
			update_post_meta( $post_id, '_tcacc_horizontal_height', max( 200, min( 900, $height ? $height : 380 ) ) );
		}

		if ( isset( $_POST['_tcacc_open_event'] ) ) {
			$event = sanitize_text_field( wp_unslash( $_POST['_tcacc_open_event'] ) );
			update_post_meta( $post_id, '_tcacc_open_event', in_array( $event, array( 'click', 'hover' ), true ) ? $event : 'click' );
		}

		if ( isset( $_POST['_tcacc_anim_speed'] ) ) {
			update_post_meta( $post_id, '_tcacc_anim_speed', absint( wp_unslash( $_POST['_tcacc_anim_speed'] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Allow the Pro settings in the Gutenberg Inline block.
	 *
	 * @param array $schema Allowed settings.
	 * @return array
	 */
	public function inline_option_schema( $schema ) {
		$schema['_tcacc_horizontal_height'] = array( 'int', 200, 900 );
		$schema['_tcacc_open_event']        = array( 'enum', array( 'click', 'hover' ) );
		$schema['_tcacc_anim_speed']        = array( 'int', 0, 3000 );

		return $schema;
	}

	// -------------------------------------------------------------------------
	// Nested accordions
	// -------------------------------------------------------------------------

	/**
	 * Keep the nested accordion ID when items are read.
	 *
	 * @param array $item Normalized item.
	 * @param array $raw  Raw item as saved.
	 * @return array
	 */
	public function keep_item_data( $item, $raw ) {
		$item['nested_id'] = isset( $raw['nested_id'] ) ? absint( $raw['nested_id'] ) : 0;

		return $item;
	}

	/**
	 * Keep the nested accordion ID when items are saved.
	 *
	 * @param array $clean   Sanitized item.
	 * @param array $raw     Submitted item.
	 * @param int   $post_id Accordion ID.
	 * @return array
	 */
	public function sanitize_item( $clean, $raw, $post_id ) {
		$nested_id = isset( $raw['nested_id'] ) ? absint( $raw['nested_id'] ) : 0;

		// An accordion can never contain itself.
		$clean['nested_id'] = ( $nested_id === (int) $post_id ) ? 0 : $nested_id;

		return $clean;
	}

	/**
	 * Accordions that can be nested inside an item (every accordion except the current one).
	 *
	 * @return array List of array( 'id' => int, 'label' => string ).
	 */
	private function get_nested_choices() {
		if ( null !== $this->nested_choices ) {
			return $this->nested_choices;
		}

		$this->nested_choices = array();

		$current = (int) get_the_ID();
		$posts   = get_posts(
			array(
				'post_type'      => 'accordion_tp',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'exclude'        => $current ? array( $current ) : array(),
			)
		);

		foreach ( $posts as $nested_post ) {
			$label = '' !== $nested_post->post_title ? $nested_post->post_title : __( '(no title)', 'accordions-wp' );
			$label = sprintf( '%s (#%d)', $label, $nested_post->ID );

			if ( 'publish' !== $nested_post->post_status ) {
				$label .= ' - ' . __( 'not published', 'accordions-wp' );
			}

			$this->nested_choices[] = array(
				'id'    => (int) $nested_post->ID,
				'label' => $label,
			);
		}

		return $this->nested_choices;
	}

	/**
	 * Nested accordion picker shown in every item of the editor.
	 *
	 * @param string|int $index Item index used in field names.
	 * @param array      $item  Item data.
	 * @return void
	 */
	public function render_nested_field( $index, $item ) {
		$nested_id      = isset( $item['nested_id'] ) ? absint( $item['nested_id'] ) : 0;
		$nested_choices = $this->get_nested_choices();
		?>
		<p class="tcaccordion-nested-field">
			<label><strong><?php esc_html_e( 'Nested Accordion (optional)', 'accordions-wp' ); ?></strong></label>
			<select class="widefat" name="<?php echo esc_attr( TCAccordion_Metabox::META_KEY ); ?>[<?php echo esc_attr( $index ); ?>][nested_id]">
				<option value="0"><?php esc_html_e( '— None —', 'accordions-wp' ); ?></option>
				<?php foreach ( $nested_choices as $choice ) : ?>
					<option value="<?php echo esc_attr( $choice['id'] ); ?>" <?php selected( $nested_id, $choice['id'] ); ?>><?php echo esc_html( $choice['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description">
				<?php
				if ( empty( $nested_choices ) ) {
					esc_html_e( 'Create another accordion first, then pick it here to show it inside this item.', 'accordions-wp' );
				} else {
					esc_html_e( 'Shows another accordion inside this item, below the description. It uses this accordion\'s theme.', 'accordions-wp' );
				}
				?>
			</span>
		</p>
		<?php
	}

	/**
	 * Print the nested accordion below an item's description.
	 *
	 * @param string $html    Extra HTML so far.
	 * @param array  $item    Normalized item.
	 * @param array  $context scope_id, item_num, post_id, depth and theme of the parent accordion.
	 * @return string
	 */
	public function render_nested( $html, $item, $context ) {
		$nested_id = ! empty( $item['nested_id'] ) ? absint( $item['nested_id'] ) : 0;

		if ( ! $nested_id ) {
			return $html;
		}

		// Depth limit and protection against an accordion nesting itself (directly or in a loop).
		if ( $context['depth'] >= self::MAX_NESTING_DEPTH || $nested_id === (int) $context['post_id'] || TCAccordion_Renderer::is_rendering( $nested_id ) ) {
			return $html;
		}

		$nested_post = get_post( $nested_id );
		if ( ! $nested_post || 'accordion_tp' !== $nested_post->post_type || 'publish' !== $nested_post->post_status ) {
			return $html;
		}

		$child = new TCAccordion_Renderer( $nested_id );
		$child->set_nested_context( $context['scope_id'] . '-nested-' . $context['item_num'], $context['theme'], $context['depth'] + 1 );

		$child_html = $child->render();

		return $html . ( '' !== $child_html ? '<div class="tc-nested-accordion">' . $child_html . '</div>' : '' );
	}
}

new TCAccordion_Pro();

// The other Pro features. Each file registers its own hooks.
require_once __DIR__ . '/class-tcaccordion-pro-items__premium_only.php';
require_once __DIR__ . '/class-tcaccordion-pro-dynamic__premium_only.php';
require_once __DIR__ . '/class-tcaccordion-pro-analytics__premium_only.php';
require_once __DIR__ . '/class-tcaccordion-pro-woo__premium_only.php';
