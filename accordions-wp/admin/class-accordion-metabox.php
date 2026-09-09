<?php
/**
 * Custom Metabox Class for Accordion Items & Settings.
 * Handles legacy CMB2 multi-row conversion, dynamic field parsing, limits, and styling options.
 *
 * @package TCAccordion
 * @version 3.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TCAccordion_Metabox {

	/**
	 * Meta key used for storing accordion items in postmeta.
	 *
	 * @var string
	 */
	const META_KEY = 'custom_accordion_wordpresspro_columns';

	/**
	 * Maximum allowed accordion items for Free users.
	 *
	 * @var int
	 */
	const FREE_MAX_ITEMS = 5;

	/**
	 * Maximum allowed accordion items for Pro users.
	 *
	 * @var int
	 */
	const PRO_MAX_ITEMS = 25;

	/**
	 * Constructor to register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ), 0 );
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post_accordion_tp', array( $this, 'save_metabox' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Admin Columns & Shortcode Notice
		add_filter( 'manage_accordion_tp_posts_columns', array( $this, 'register_admin_columns' ) );
		add_action( 'manage_accordion_tp_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		add_action( 'edit_form_after_title', array( $this, 'render_shortcode_section' ) );

		add_action( 'wp_ajax_tcaccordion_get_live_preview', array( $this, 'ajax_render_live_preview' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_preview_styles' ) );

		add_filter( 'enter_title_here', array( $this, 'tcaccordion_change_title_placeholder' ) );
	}

	/**
	 * Register Custom Post Type
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Accordions', 'Post Type General Name', 'tcaccordion' ),
			'singular_name'      => _x( 'Accordion', 'Post Type Singular Name', 'tcaccordion' ),
			'menu_name'          => __( 'Accordion', 'tcaccordion' ),
			'name_admin_bar'     => __( 'Accordion', 'tcaccordion' ),
			'all_items'          => __( 'All Accordions', 'tcaccordion' ),
			'add_new_item'       => __( 'Add New Accordion', 'tcaccordion' ),
			'add_new'            => __( 'Add New Accordion', 'tcaccordion' ),
			'new_item'           => __( 'New Item Accordion', 'tcaccordion' ),
			'edit_item'          => __( 'Edit Accordion', 'tcaccordion' ),
			'update_item'        => __( 'Update Accordion', 'tcaccordion' ),
			'view_item'          => __( 'View Accordion', 'tcaccordion' ),
			'search_items'       => __( 'Search Accordion', 'tcaccordion' ),
			'not_found'          => __( 'Accordion Not found', 'tcaccordion' ),
			'not_found_in_trash' => __( 'Accordion Not found in Trash', 'tcaccordion' ),
		);

		$args = array(
			'label'               => __( 'Accordion', 'tcaccordion' ),
			'description'         => __( 'Accordion Post Type', 'tcaccordion' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'menu_icon'           => defined( 'TCACCORDION_PLUGIN_URL' ) ? TCACCORDION_PLUGIN_URL . 'assets/css/accordion.png' : 'dashicons-list-view',
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
		);

		register_post_type( 'accordion_tp', $args );
	}

	/**
	 * Check if the plugin is in Pro version.
	 *
	 * @return bool
	 */
	private function is_pro() {
		return false;
	}

	/**
	 * Get the maximum item limit based on user license.
	 *
	 * @return int
	 */
	private function get_max_items() {
		return $this->is_pro() ? self::PRO_MAX_ITEMS : self::FREE_MAX_ITEMS;
	}

	/**
	 * Register the Accordion metabox wrapper.
	 *
	 * @return void
	 */
	public function register_metabox() {
		add_meta_box(
			'custom_accordion_wordpress_feature',
			__( 'Accordion Control Panel', 'tcaccordion' ),
			array( $this, 'render_metabox_wrapper' ),
			'accordion_tp',
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		global $post_type;

		if ( 'accordion_tp' !== $post_type ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'tcaccordion-metabox',
			TCACCORDION_PLUGIN_URL . 'admin/css/accordion-metabox.css',
			array(),
			'3.0.7'
		);

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_editor();

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'tcaccordion-color-picker',
			TCACCORDION_PLUGIN_URL . 'admin/js/color-picker.js',
			array( 'wp-color-picker' ),
			TCACCORDION_VERSION,
			true
		);

		wp_enqueue_script(
			'tcaccordion-metabox',
			TCACCORDION_PLUGIN_URL . 'admin/js/accordion-metabox.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			'3.0.7',
			true
		);

		wp_localize_script(
			'tcaccordion-metabox',
			'tcAccordion',
			array(
				'maxItems'   => $this->get_max_items(),
				'isPro'      => $this->is_pro(),
				'addText'    => __( 'Add Item', 'tcaccordion' ),
				'removeText' => __( 'Remove', 'tcaccordion' ),
				'limitText'  => sprintf( __( 'Free version is limited to %d items.', 'tcaccordion' ), self::FREE_MAX_ITEMS ),
			)
		);
	}

	/**
	 * Main Tabbed Container Wrapper
	 */
	public function render_metabox_wrapper( $post ) {
		wp_nonce_field( 'tcaccordion_save_action', 'tcaccordion_nonce_field' );

		// Retrieve active tab from meta or default to '#tab-accordion-content'
		$active_tab = get_post_meta( $post->ID, '_tc_active_tab', true );
		if ( empty( $active_tab ) ) {
			$active_tab = '#tab-accordion-content';
		}
		?>
		<div id="tc-tabs-container" class="tc-tabs-container">
			<!-- Hidden input to post the current active tab -->
			<input type="hidden" id="tc_active_tab_input" name="tc_active_tab_input" value="<?php echo esc_attr( $active_tab ); ?>" />

			<ul class="tc-tabs-menu">
				<li class="tc-tab-item <?php echo ( '#tab-accordion-content' === $active_tab ) ? 'current' : ''; ?>">
					<a href="#tab-accordion-content" class="tc-tab-link">
						<span class="dashicons dashicons-editor-justify"></span>
						<?php esc_html_e( 'Accordion Content', 'tcaccordion' ); ?>
					</a>
				</li>
				<li class="tc-tab-item <?php echo ( '#tab-accordion-settings' === $active_tab ) ? 'current' : ''; ?>">
					<a href="#tab-accordion-settings" class="tc-tab-link">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Accordion Settings', 'tcaccordion' ); ?>
					</a>
				</li>

				<li class="tc-tab-item <?php echo ( '#tab-accordion-preview' === $active_tab ) ? 'current' : ''; ?>">
					<a href="#tab-accordion-preview" class="tc-tab-link">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Live Preview', 'tcaccordion' ); ?>
					</a>
				</li>
			</ul>

			<div class="tc-tab-wrapper">
				<!-- TAB 1: REPEATER / GROUP FIELDS -->
				<div id="tab-accordion-content" class="tc-tab-content <?php echo ( '#tab-accordion-content' === $active_tab ) ? 'active' : ''; ?>">
					<?php $this->render_accordion_content_tab( $post ); ?>
				</div>

				<!-- TAB 2: STYLING & OPTIONS -->
				<div id="tab-accordion-settings" class="tc-tab-content <?php echo ( '#tab-accordion-settings' === $active_tab ) ? 'active' : ''; ?>">
					<?php $this->render_accordion_settings_tab( $post ); ?>
				</div>

				<!-- TAB 3: LIVE PREVIEW TAB -->
<div id="tab-accordion-preview" class="tc-tab-content <?php echo ( '#tab-accordion-preview' === $active_tab ) ? 'active' : ''; ?>">
    <div class="tc-preview-tab-container" style="padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
        <div class="tc-preview-header" style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; display: inline-block;">
                    <span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Exact Frontend Live Preview', 'tcaccordion' ); ?>
                </h3>
                <span class="description" style="margin-left: 10px;">
                    <?php esc_html_e( '(Renders full shortcodes, embeds, and actual frontend CSS)', 'tcaccordion' ); ?>
                </span>
            </div>
            <button type="button" id="tc-refresh-preview-btn" class="button button-secondary">
                <span class="dashicons dashicons-update" style="vertical-align: middle; line-height: 1.3;"></span>
                <?php esc_html_e( 'Refresh Preview', 'tcaccordion' ); ?>
            </button>
        </div>

        <!-- Preview Loader & Display Canvas -->
        <div id="tc-preview-loading" style="display: none; padding: 30px; text-align: center;">
            <span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>
            <?php esc_html_e( 'Rendering exact frontend preview...', 'tcaccordion' ); ?>
        </div>

        <div id="tc-exact-preview-canvas" class="tc-frontend-preview-wrap">
            <!-- Exact Frontend HTML loaded here via AJAX -->
        </div>
    </div>
</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Tab 1 Render: Repeater Items Markup
	 */
	private function render_accordion_content_tab( $post ) {
		$items     = $this->get_items_from_db( $post->ID );
		$max_limit = $this->get_max_items();
		?>
		<div id="tcaccordion-wrapper">
			<div id="tcaccordion-sortable">
				<?php
				if ( ! empty( $items ) && is_array( $items ) ) {
					foreach ( $items as $index => $item ) {
						$this->render_item( $index, $item );
					}
				}
				?>
			</div>

			<p class="tcaccordion-actions">
				<button type="button" class="button button-primary" id="tcaccordion-add" <?php echo count( $items ) >= $max_limit ? 'disabled="disabled"' : ''; ?>>
					<?php esc_html_e( 'Add Accordion Item', 'tcaccordion' ); ?>
				</button>
			</p>

			<?php if ( ! $this->is_pro() ) : ?>
				<p class="description tcaccordion-limit-notice">
					<?php
					printf(
						/* translators: %d: maximum number of items */
						esc_html__( 'Free version is limited to %d items. Upgrade to Pro for unlimited items.', 'tcaccordion' ),
						self::FREE_MAX_ITEMS
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<script type="text/template" id="tcaccordion-template">
			<?php
			ob_start();
			$this->render_item(
				'__INDEX__',
				array(
					'title'       => '',
					'description' => '',
				)
			);
			$template_content = ob_get_clean();
			echo str_replace( array( '<script', '</script>' ), array( '&lt;script', '&lt;/script' ), $template_content );
			?>
		</script>
		<?php
	}

	/**
	 * Tab 2 Render: Settings & Theme Picker Controls
	 */
	private function render_accordion_settings_tab( $post ) {
		// Meta Values Retrieve
		$custom_accordion_columns_post_themes      = get_post_meta( $post->ID, 'custom_accordion_columns_post_themes', true );
		$custom_accordion_title_bg_color           = get_post_meta( $post->ID, 'custom_accordion_title_bg_color', true );
		$custom_accordion_title_font_color         = get_post_meta( $post->ID, 'custom_accordion_title_font_color', true );
		$custom_accordion_title_font_size          = get_post_meta( $post->ID, 'custom_accordion_title_font_size', true );
		$custom_accordion_content_bg_color         = get_post_meta( $post->ID, 'custom_accordion_content_bg_color', true );
		$custom_accordion_content_font_color       = get_post_meta( $post->ID, 'custom_accordion_content_font_color', true );
		$custom_accordion_content_font_size        = get_post_meta( $post->ID, 'custom_accordion_content_font_size', true );
		$custom_accordion_content_padding          = get_post_meta( $post->ID, 'custom_accordion_content_padding', true );
		$_tpaccpro_wiki_acc_themes_title_position  = get_post_meta( $post->ID, '_tpaccpro_wiki_acc_themes_title_position', true );
		$_tpaccpro_wiki_acc_themes_show_hide_icons = get_post_meta( $post->ID, '_tpaccpro_wiki_acc_themes_show_hide_icons', true );
		$_tpaccpro_wiki_acc_themes_icon_position   = get_post_meta( $post->ID, '_tpaccpro_wiki_acc_themes_icon_position', true );
		$_tpaccpro_wiki_acc_theme_content_margin   = get_post_meta( $post->ID, '_tpaccpro_wiki_acc_theme_content_margin', true );
		$custom_accordion_title_line_height        = get_post_meta( $post->ID, 'custom_accordion_title_line_height', true );
		$custom_accordion_content_line_height      = get_post_meta( $post->ID, 'custom_accordion_content_line_height', true );
		$custom_accordion_content_letter_spacing   = get_post_meta( $post->ID, 'custom_accordion_content_letter_spacing', true );

		if ( empty( $custom_accordion_columns_post_themes ) ) {
			$custom_accordion_columns_post_themes = 'theme1';
		}

		$themes = array(
			'theme1' => array( 'label' => __( 'Sun Flower', 'tcaccordion' ), 'img' => 'theme1.png' ),
			'theme2' => array( 'label' => __( 'Orange', 'tcaccordion' ),     'img' => 'theme2.png' ),
			'theme3' => array( 'label' => __( 'Pumpkin', 'tcaccordion' ),    'img' => 'theme3.png' ),
			'theme4' => array( 'label' => __( 'Alizarin', 'tcaccordion' ),   'img' => 'theme4.png' ),
			'theme5' => array( 'label' => __( 'Carrot', 'tcaccordion' ),     'img' => 'theme5.png' ),
		);
		?>
<table class="form-table tc-settings-table">
			<tr valign="top">
				<th scope="row"><label><?php esc_html_e( 'Accordion Themes', 'tcaccordion' ); ?></label></th>
				<td>
					<div class="tc-theme-picker">
						<?php foreach ( $themes as $key => $theme ) : ?>
							<?php $img_url = defined( 'TCACCORDION_PLUGIN_URL' ) ? TCACCORDION_PLUGIN_URL . 'admin/images/' . $theme['img'] : ''; ?>
							<label class="tc-theme-option">
								<input type="radio" name="custom_accordion_columns_post_themes" value="<?php echo esc_attr( $key ); ?>" <?php checked( $custom_accordion_columns_post_themes, $key ); ?> />
								<div class="tc-theme-card">
									<?php if ( $img_url ) : ?>
										<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $theme['label'] ); ?>" />
									<?php endif; ?>
									<span><?php echo esc_html( $theme['label'] ); ?></span>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom-accordion-title-bg-color"><?php esc_html_e( 'Title BG Color', 'tcaccordion' ); ?></label></th>
				<td><input name="custom_accordion_title_bg_color" class="tc-color-field" id="custom-accordion-title-bg-color" type="text" value="<?php echo esc_attr( $custom_accordion_title_bg_color ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom-accordion-title-font-color"><?php esc_html_e( 'Title Font Color', 'tcaccordion' ); ?></label></th>
				<td><input name="custom_accordion_title_font_color" class="tc-color-field" id="custom-accordion-title-font-color" type="text" value="<?php echo esc_attr( $custom_accordion_title_font_color ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_title_font_size"><?php esc_html_e( 'Title Font Size', 'tcaccordion' ); ?></label></th>
				<td><input type="number" name="custom_accordion_title_font_size" id="custom_accordion_title_font_size" min="10" max="45" value="<?php echo ! empty( $custom_accordion_title_font_size ) ? esc_attr( $custom_accordion_title_font_size ) : '15'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_title_line_height"><?php esc_html_e( 'Title Line Height', 'tcaccordion' ); ?></label></th>
				<td><input type="number" step="0.1" name="custom_accordion_title_line_height" id="custom_accordion_title_line_height" min="0.8" max="3" value="<?php echo ! empty( $custom_accordion_title_line_height ) ? esc_attr( $custom_accordion_title_line_height ) : '1.4'; ?>"> (e.g. 1.4)</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="_tpaccpro_wiki_acc_themes_title_position"><?php esc_html_e( 'Title Text Position', 'tcaccordion' ); ?></label></th>
				<td>
					<select name="_tpaccpro_wiki_acc_themes_title_position" id="_tpaccpro_wiki_acc_themes_title_position">
						<option value="left" <?php selected( $_tpaccpro_wiki_acc_themes_title_position, 'left' ); ?>><?php esc_html_e( 'Left', 'tcaccordion' ); ?></option>
						<option value="center" <?php selected( $_tpaccpro_wiki_acc_themes_title_position, 'center' ); ?>><?php esc_html_e( 'Center', 'tcaccordion' ); ?></option>
						<option value="right" <?php selected( $_tpaccpro_wiki_acc_themes_title_position, 'right' ); ?>><?php esc_html_e( 'Right', 'tcaccordion' ); ?></option>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="_tpaccpro_wiki_acc_themes_show_hide_icons"><?php esc_html_e( 'Show/Hide Icon', 'tcaccordion' ); ?></label></th>
				<td>
					<select name="_tpaccpro_wiki_acc_themes_show_hide_icons" id="_tpaccpro_wiki_acc_themes_show_hide_icons">
						<option value="1" <?php selected( $_tpaccpro_wiki_acc_themes_show_hide_icons, '1' ); ?>><?php esc_html_e( 'Show', 'tcaccordion' ); ?></option>
						<option value="2" <?php selected( $_tpaccpro_wiki_acc_themes_show_hide_icons, '2' ); ?>><?php esc_html_e( 'Hide', 'tcaccordion' ); ?></option>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="_tpaccpro_wiki_acc_themes_icon_position"><?php esc_html_e( 'Icon Position', 'tcaccordion' ); ?></label></th>
				<td>
					<select name="_tpaccpro_wiki_acc_themes_icon_position" id="_tpaccpro_wiki_acc_themes_icon_position">
						<option value="1" <?php selected( $_tpaccpro_wiki_acc_themes_icon_position, '1' ); ?>><?php esc_html_e( 'Left', 'tcaccordion' ); ?></option>
						<option value="2" <?php selected( $_tpaccpro_wiki_acc_themes_icon_position, '2' ); ?>><?php esc_html_e( 'Right', 'tcaccordion' ); ?></option>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom-accordion-content-bg-color"><?php esc_html_e( 'Content BG Color', 'tcaccordion' ); ?></label></th>
				<td><input name="custom_accordion_content_bg_color" class="tc-color-field" id="custom-accordion-content-bg-color" type="text" value="<?php echo esc_attr( $custom_accordion_content_bg_color ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom-accordion-content-font-color"><?php esc_html_e( 'Content Font Color', 'tcaccordion' ); ?></label></th>
				<td><input name="custom_accordion_content_font_color" class="tc-color-field" id="custom-accordion-content-font-color" type="text" value="<?php echo esc_attr( $custom_accordion_content_font_color ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_font_size"><?php esc_html_e( 'Content Font Size', 'tcaccordion' ); ?></label></th>
				<td><input type="number" name="custom_accordion_content_font_size" id="custom_accordion_content_font_size" min="10" max="45" value="<?php echo ! empty( $custom_accordion_content_font_size ) ? esc_attr( $custom_accordion_content_font_size ) : '14'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_line_height"><?php esc_html_e( 'Content Line Height', 'tcaccordion' ); ?></label></th>
				<td><input type="number" step="0.1" name="custom_accordion_content_line_height" id="custom_accordion_content_line_height" min="0.8" max="3" value="<?php echo ! empty( $custom_accordion_content_line_height ) ? esc_attr( $custom_accordion_content_line_height ) : '1.6'; ?>"> (e.g. 1.6)</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_letter_spacing"><?php esc_html_e( 'Content Letter Spacing', 'tcaccordion' ); ?></label></th>
				<td><input type="number" step="0.1" name="custom_accordion_content_letter_spacing" id="custom_accordion_content_letter_spacing" min="0" max="10" value="<?php echo ! empty( $custom_accordion_content_letter_spacing ) ? esc_attr( $custom_accordion_content_letter_spacing ) : '0'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="_tpaccpro_wiki_acc_theme_content_margin"><?php esc_html_e( 'Margin Between Accordion', 'tcaccordion' ); ?></label></th>
				<td><input type="number" name="_tpaccpro_wiki_acc_theme_content_margin" id="_tpaccpro_wiki_acc_theme_content_margin" min="0" max="45" value="<?php echo ! empty( $_tpaccpro_wiki_acc_theme_content_margin ) ? esc_attr( $_tpaccpro_wiki_acc_theme_content_margin ) : '5'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_padding"><?php esc_html_e( 'Content Padding', 'tcaccordion' ); ?></label></th>
				<td><input type="number" name="custom_accordion_content_padding" id="custom_accordion_content_padding" min="0" max="45" value="<?php echo ! empty( $custom_accordion_content_padding ) ? esc_attr( $custom_accordion_content_padding ) : '12'; ?>"> px</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Universal Meta Parser: Fetches all postmeta rows and flattens both CMB2 legacy entries and array formats.
	 *
	 * @param int $post_id Post ID.
	 * @return array Standardized array of items.
	 */
	private function get_items_from_db( $post_id ) {
		$all_meta_rows = get_post_meta( $post_id, self::META_KEY, false );
		$items         = array();

		if ( empty( $all_meta_rows ) || ! is_array( $all_meta_rows ) ) {
			return $items;
		}

		foreach ( $all_meta_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			if ( isset( $row[0] ) && is_array( $row[0] ) ) {
				foreach ( $row as $sub_item ) {
					if ( is_array( $sub_item ) ) {
						$item = $this->extract_item_fields( $sub_item );
						if ( $item ) {
							$items[] = $item;
						}
					}
				}
			} else {
				$item = $this->extract_item_fields( $row );
				if ( $item ) {
					$items[] = $item;
				}
			}
		}

		return $items;
	}

	/**
	 * Extract title and description from multiple legacy field key variants.
	 *
	 * @param array $data Raw array row.
	 * @return array|false Formatted item or false if empty.
	 */
	private function extract_item_fields( $data ) {
		$title = '';
		if ( isset( $data['title'] ) ) {
			$title = $data['title'];
		} elseif ( isset( $data['custom_accordions_pro_title'] ) ) {
			$title = $data['custom_accordions_pro_title'];
		}

		$details = '';
		if ( isset( $data['description'] ) ) {
			$details = $data['description'];
		} elseif ( isset( $data['custom_accordions_pro_details'] ) ) {
			$details = $data['custom_accordions_pro_details'];
		}

		if ( '' !== $title || '' !== $details ) {
			return array(
				'title'       => $title,
				'description' => $details,
			);
		}

		return false;
	}

	/**
	 * Render Individual Item Markup.
	 *
	 * @param string|int $index Index key.
	 * @param array      $item  Item details.
	 * @return void
	 */
	private function render_item( $index, $item ) {
		$title   = isset( $item['title'] ) ? $item['title'] : '';
		$content = isset( $item['description'] ) ? $item['description'] : '';

		$editor_id = 'tcaccordion_editor_' . sanitize_html_class( $index );
		?>

		<div class="tcaccordion-item">
			<div class="tcaccordion-header">
				<span class="dashicons dashicons-menu"></span>
				<strong class="tcaccordion-label">
					<?php echo esc_html( $title ? $title : __( 'New Accordion', 'tcaccordion' ) ); ?>
				</strong>
				<button type="button" class="button-link tcaccordion-toggle">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</button>
			</div>

			<div class="tcaccordion-body">
				<p>
					<label><strong><?php esc_html_e( 'Accordion Title', 'tcaccordion' ); ?></strong></label>
					<input
						type="text"
						class="widefat tcaccordion-title"
						name="<?php echo esc_attr( self::META_KEY ); ?>[<?php echo esc_attr( $index ); ?>][title]"
						value="<?php echo esc_attr( $title ); ?>"
					>
				</p>

				<p>
					<label><strong><?php esc_html_e( 'Description', 'tcaccordion' ); ?></strong></label>
					<?php
					wp_editor(
						$content,
						$editor_id,
						array(
							'textarea_name' => self::META_KEY . '[' . $index . '][description]',
							'textarea_rows' => 6,
							'media_buttons' => false,
							'teeny'         => true,
							'quicktags'     => true,
						)
					);
					?>
				</p>

				<p>
					<button type="button" class="button tcaccordion-remove">
						<?php esc_html_e( 'Remove', 'tcaccordion' ); ?>
					</button>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Combined Save Handler (Group Fields + Style Settings)
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post Object.
	 * @return void
	 */
	public function save_metabox( $post_id, $post ) {
		if (
			! isset( $_POST['tcaccordion_nonce_field'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tcaccordion_nonce_field'] ) ), 'tcaccordion_save_action' )
		) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 1. SAVE REPEATER / GROUP ITEMS
		if ( empty( $_POST[ self::META_KEY ] ) || ! is_array( $_POST[ self::META_KEY ] ) ) {
			delete_post_meta( $post_id, self::META_KEY );
		} else {
			$items            = array();
			$max_limit        = $this->get_max_items();
			$raw_posted_items = wp_unslash( $_POST[ self::META_KEY ] );

			foreach ( array_values( $raw_posted_items ) as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				if ( count( $items ) >= $max_limit ) {
					break;
				}

				$title   = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
				$details = isset( $item['description'] ) ? wp_kses_post( $item['description'] ) : '';

				if ( '' !== $title || '' !== $details ) {
					$items[] = array(
						'title'       => $title,
						'description' => $details,
					);
				}
			}

			delete_post_meta( $post_id, self::META_KEY );
			if ( ! empty( $items ) ) {
				update_post_meta( $post_id, self::META_KEY, $items );
			}
		}

		// Save Active Tab State
		if ( isset( $_POST['tc_active_tab_input'] ) ) {
			$active_tab = sanitize_text_field( wp_unslash( $_POST['tc_active_tab_input'] ) );
			update_post_meta( $post_id, '_tc_active_tab', $active_tab );
		}

		// 2. SAVE ACCORDION STYLE & OPTION METAS
		$style_fields = array(
			'custom_accordion_columns_post_themes'      => 'sanitize_text_field',
			'custom_accordion_title_font_size'          => 'sanitize_text_field',
			'custom_accordion_content_font_size'        => 'sanitize_text_field',
			'custom_accordion_content_padding'          => 'sanitize_text_field',
			'_tpaccpro_wiki_acc_themes_title_position'  => 'sanitize_text_field',
			'_tpaccpro_wiki_acc_themes_show_hide_icons' => 'sanitize_text_field',
			'_tpaccpro_wiki_acc_themes_icon_position'   => 'sanitize_text_field',
			'_tpaccpro_wiki_acc_theme_content_margin'   => 'sanitize_text_field',
			'custom_accordion_title_line_height'        => 'sanitize_text_field',
			'custom_accordion_content_line_height'      => 'sanitize_text_field',
			'custom_accordion_content_letter_spacing'   => 'sanitize_text_field',
			'custom_accordion_title_bg_color'           => 'sanitize_hex_color',
			'custom_accordion_title_font_color'         => 'sanitize_hex_color',
			'custom_accordion_content_bg_color'         => 'sanitize_hex_color',
			'custom_accordion_content_font_color'       => 'sanitize_hex_color',
		);

		foreach ( $style_fields as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				$val = call_user_func( $sanitize_callback, $_POST[ $field ] );
				update_post_meta( $post_id, $field, $val );
			}
		}
	}

	/**
	 * Custom Admin Post Columns
	 */
	public function register_admin_columns( $columns ) {
		return array(
			'cb'          => '<input type="checkbox" />',
			'title'       => __( 'Shortcode Name', 'tcaccordion' ),
			'shortcode'   => __( 'Shortcode', 'tcaccordion' ),
			'doshortcode' => __( 'Template Shortcode', 'tcaccordion' ),
			'date'        => __( 'Date', 'tcaccordion' ),
		);
	}

	/**
	 * Display Admin Post Columns Content
	 */
	public function render_admin_columns( $column, $post_id ) {
		if ( 'shortcode' === $column ) {
			echo '<input style="background:#ddd" type="text" readonly onClick="this.select();" value="[tcpaccordion id=&quot;' . esc_attr( $post_id ) . '&quot;]" />';
		}
		if ( 'doshortcode' === $column ) {
			echo '<textarea cols="40" rows="2" style="background:#ddd;" readonly onClick="this.select();">&lt;?php echo do_shortcode("[tcpaccordion id=\'' . esc_attr( $post_id ) . '\']"); ?&gt;</textarea>';
		}
	}

	/**
	 * Display Shortcode Notice Below Title Input
	 */
	public function render_shortcode_section( $post ) {
		if ( 'accordion_tp' !== $post->post_type ) {
			return;
		}

		$shortcode = "[tcpaccordion id='" . $post->ID . "']";
		$php_code  = '<?php echo do_shortcode("[tcpaccordion id=' . $post->ID . ']"); ?>';
		?>
		<div style="padding: 15px; border: 1px solid #ddd; background: #f9f9f9; margin-top: 15px;">
			<div style="display: flex; gap: 20px;">
				<div style="width: 50%;">
					<p><strong><?php esc_html_e( 'Shortcode', 'tcaccordion' ); ?>:</strong> <span id="tc-sc-notice" style="color: green; display: none; margin-left: 10px;"><?php esc_html_e( 'Copied!', 'tcaccordion' ); ?></span></p>
					<input type="text" style="width:100%; cursor:pointer;" value="<?php echo esc_attr( $shortcode ); ?>" readonly onclick="tcCopyNotice(this, 'tc-sc-notice')" />
				</div>
				<div style="width: 50%;">
					<p><strong><?php esc_html_e( 'PHP Code', 'tcaccordion' ); ?>:</strong> <span id="tc-php-notice" style="color: green; display: none; margin-left: 10px;"><?php esc_html_e( 'Copied!', 'tcaccordion' ); ?></span></p>
					<input type="text" style="width:100%; cursor:pointer;" value="<?php echo esc_attr( $php_code ); ?>" readonly onclick="tcCopyNotice(this, 'tc-php-notice')" />
				</div>
			</div>
		</div>
		<script>
			function tcCopyNotice(input, noticeId) {
				input.select();
				navigator.clipboard.writeText(input.value);
				var notice = document.getElementById(noticeId);
				notice.style.display = "inline";
				setTimeout(function() { notice.style.display = "none"; }, 2000);
			}
		</script>
		<?php
	}

	/**
	 * Change Post Title Placeholder
	 */

	public function tcaccordion_change_title_placeholder( $title ) {

		$screen = get_current_screen();

		if ( $screen && 'accordion_tp' === $screen->post_type ) {
			return __( 'Accordion Group Title', 'tcaccordion' );
		}
		return $title;
	}

	/**
	 * AJAX Handler to generate exact live frontend markup from unsaved form data.
	 */
	public function ajax_render_live_preview() {
	    check_ajax_referer( 'tcaccordion_save_action', 'security' );

	    if ( ! current_user_can( 'edit_posts' ) ) {
	        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tcaccordion' ) ) );
	    }

	    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

	    // Parse serialized form data string sent from JavaScript
	    $posted_meta = array();
	    if ( ! empty( $_POST['form_data'] ) ) {
	        wp_parse_str( $_POST['form_data'], $posted_meta );
	    }

	    // Initialize the renderer
	    $renderer = new TCAccordion_Renderer( $post_id );

	    // Pass live unsaved form fields to the renderer instance
	    if ( ! empty( $posted_meta ) && method_exists( $renderer, 'set_preview_data' ) ) {
	        $renderer->set_preview_data( $posted_meta );
	    }

	    $html = $renderer->render();

	    wp_send_json_success( array( 'html' => $html ) );
	}

	/**
     * Enqueue Admin Scripts & Styles for Preview
     */
    public function enqueue_admin_preview_styles( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        $screen = get_current_screen();
        if ( is_object( $screen ) && 'accordion_tp' === $screen->post_type ) {
            wp_enqueue_style( 'tcaccordion-responsive', TCACCORDION_PLUGIN_URL . 'assets/css/responsive-accordion.css', array(), TCACCORDION_VERSION );
            wp_enqueue_style( 'tcaccordion-style', TCACCORDION_PLUGIN_URL . 'assets/css/style.css', array(), TCACCORDION_VERSION );
            //wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), '4.7.0' );
        }
    }

}

new TCAccordion_Metabox();