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
			'name'               => _x( 'Accordions', 'Post Type General Name', 'accordions-wp' ),
			'singular_name'      => _x( 'Accordion', 'Post Type Singular Name', 'accordions-wp' ),
			'menu_name'          => __( 'Accordion', 'accordions-wp' ),
			'name_admin_bar'     => __( 'Accordion', 'accordions-wp' ),
			'all_items'          => __( 'All Accordions', 'accordions-wp' ),
			'add_new_item'       => __( 'Add New Accordion', 'accordions-wp' ),
			'add_new'            => __( 'Add New Accordion', 'accordions-wp' ),
			'new_item'           => __( 'New Item Accordion', 'accordions-wp' ),
			'edit_item'          => __( 'Edit Accordion', 'accordions-wp' ),
			'update_item'        => __( 'Update Accordion', 'accordions-wp' ),
			'view_item'          => __( 'View Accordion', 'accordions-wp' ),
			'search_items'       => __( 'Search Accordion', 'accordions-wp' ),
			'not_found'          => __( 'Accordion Not found', 'accordions-wp' ),
			'not_found_in_trash' => __( 'Accordion Not found in Trash', 'accordions-wp' ),
		);

		$args = array(
			'label'               => __( 'Accordion', 'accordions-wp' ),
			'description'         => __( 'Accordion Post Type', 'accordions-wp' ),
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
	 * A value of 0 means unlimited. Filterable via 'tcaccordion_max_items'.
	 *
	 * @return int
	 */
	private function get_max_items() {
		return max( 0, (int) apply_filters( 'tcaccordion_max_items', 0 ) );
	}

	/**
	 * Register the Accordion metabox wrapper.
	 *
	 * @return void
	 */
	public function register_metabox() {
		add_meta_box(
			'custom_accordion_wordpress_feature',
			__( 'Accordion Control Panel', 'accordions-wp' ),
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

		wp_enqueue_style(
			'font-awesome-6',
			TCACCORDION_PLUGIN_URL . 'assets/css/all.min.css',
			array(),
			'6.5.1'
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
				'addText'    => __( 'Add Item', 'accordions-wp' ),
				'removeText' => __( 'Remove', 'accordions-wp' ),
				'copyText'   => __( 'Copy', 'accordions-wp' ),
				'i18n'       => array(
					'confirmRemove'  => __( 'Remove this item?', 'accordions-wp' ),
					'noRows'         => __( 'No items were found in this file. Make sure it has a header row with the columns "title" and "description".', 'accordions-wp' ),
					'readError'      => __( 'The file could not be read.', 'accordions-wp' ),
					'confirmReplace' => __( 'This removes all current items and replaces them with the content of the CSV file. Continue?', 'accordions-wp' ),
					/* translators: %d: number of imported items */
					'imported'       => __( '%d items imported. Click Update to save them.', 'accordions-wp' ),
					/* translators: %d: number of imported items */
					'limited'        => __( 'Only %d items could be imported because of the item limit.', 'accordions-wp' ),
					'largeWarning'   => __( 'Very large accordions can exceed your server\'s PHP max_input_vars limit and lose items when saving. If items are missing after saving, ask your host to raise max_input_vars.', 'accordions-wp' ),
				),
				'limitText'  => sprintf(
					/* translators: %d: maximum number of items */
					__( 'You can add up to %d items to one accordion.', 'accordions-wp' ),
					$this->get_max_items()
				),
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
						<?php esc_html_e( 'Accordion Content', 'accordions-wp' ); ?>
					</a>
				</li>

				<li class="tc-tab-item <?php echo ( '#tab-accordion-settings' === $active_tab ) ? 'current' : ''; ?>">
					<a href="#tab-accordion-settings" class="tc-tab-link">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Accordion Settings', 'accordions-wp' ); ?>
					</a>
				</li>
				
				<li class="tc-tab-item <?php echo ( '#tab-accordion-design' === $active_tab ) ? 'current' : ''; ?>">
					<a href="#tab-accordion-design" class="tc-tab-link">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Accordion Design', 'accordions-wp' ); ?>
					</a>
				</li>

				<li class="tc-tab-item <?php echo ( '#tab-accordion-preview' === $active_tab ) ? 'current' : ''; ?>">
					<a href="#tab-accordion-preview" class="tc-tab-link">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Live Preview', 'accordions-wp' ); ?>
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

				<!-- TAB 3: STYLING & OPTIONS -->
				<div id="tab-accordion-design" class="tc-tab-content <?php echo ( '#tab-accordion-design' === $active_tab ) ? 'active' : ''; ?>">
					<?php $this->render_accordion_design_tab( $post ); ?>
				</div>

				<!-- TAB 3: LIVE PREVIEW TAB -->
				<div id="tab-accordion-preview" class="tc-tab-content <?php echo ( '#tab-accordion-preview' === $active_tab ) ? 'active' : ''; ?>">
					<div class="tc-preview-tab-container" style="padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
						<div class="tc-preview-header" style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
							<div>
								<h3 style="margin: 0; display: inline-block;">
									<span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span>
									<?php esc_html_e( 'Exact Frontend Live Preview', 'accordions-wp' ); ?>
								</h3>
								<span class="description" style="margin-left: 10px;">
									<?php esc_html_e( '(Renders full shortcodes, embeds, and actual frontend CSS)', 'accordions-wp' ); ?>
								</span>
							</div>
							<button type="button" id="tc-refresh-preview-btn" class="button button-secondary">
								<span class="dashicons dashicons-update" style="vertical-align: middle; line-height: 1.3;"></span>
								<?php esc_html_e( 'Refresh Preview', 'accordions-wp' ); ?>
							</button>
						</div>

						<!-- Preview Loader & Display Canvas -->
						<div id="tc-preview-loading" style="display: none; padding: 30px; text-align: center;">
							<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>
							<?php esc_html_e( 'Rendering exact frontend preview...', 'accordions-wp' ); ?>
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
				<button type="button" class="button button-primary" id="tcaccordion-add" <?php echo ( $max_limit > 0 && count( $items ) >= $max_limit ) ? 'disabled="disabled"' : ''; ?>>
					<?php esc_html_e( 'Add Accordion Item', 'accordions-wp' ); ?>
				</button>
			</p>

			<!-- CSV Import / Export (handled in the browser, nothing here is submitted with the form) -->
			<div class="tcaccordion-csv-tools">
				<strong><?php esc_html_e( 'Import / Export', 'accordions-wp' ); ?></strong>
				<select id="tcaccordion-import-mode">
					<option value="append"><?php esc_html_e( 'Add to existing items', 'accordions-wp' ); ?></option>
					<option value="replace"><?php esc_html_e( 'Replace all existing items', 'accordions-wp' ); ?></option>
				</select>
				<button type="button" class="button" id="tcaccordion-import-btn"><?php esc_html_e( 'Import CSV', 'accordions-wp' ); ?></button>
				<input type="file" id="tcaccordion-import-file" accept=".csv,text/csv,text/plain" style="display:none;" />
				<button type="button" class="button" id="tcaccordion-export-btn"><?php esc_html_e( 'Export CSV', 'accordions-wp' ); ?></button>
				<button type="button" class="button-link" id="tcaccordion-sample-btn"><?php esc_html_e( 'Download sample CSV', 'accordions-wp' ); ?></button>
				<p class="description">
					<?php esc_html_e( 'Use a UTF-8 CSV file with a header row containing the columns "title" and "description" (also accepted: question / answer, post_title / post_content). Commas, semicolons or tabs can separate the columns, and descriptions can contain HTML. Imported items are saved when you click Publish or Update.', 'accordions-wp' ); ?>
				</p>
				<p class="description tcaccordion-csv-notice" id="tcaccordion-csv-notice" style="display:none;"></p>
			</div>

			<?php if ( $max_limit > 0 ) : ?>
				<p class="description tcaccordion-limit-notice">
					<?php
					printf(
						/* translators: %d: maximum number of items */
						esc_html__( 'You can add up to %d items to one accordion.', 'accordions-wp' ),
						(int) $max_limit
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

		// Retrieve current saved meta values (with defaults)
		$auto_open_item = get_post_meta( $post->ID, '_tcacc_auto_open_item', true );
		$auto_open_item = ( '' !== $auto_open_item ) ? absint( $auto_open_item ) : 1; // Default: 1st item

		$is_closeable   = get_post_meta( $post->ID, '_tcacc_is_closeable', true );
		$is_closeable   = ( '' !== $is_closeable ) ? sanitize_text_field( $is_closeable ) : 'yes'; // Default: yes

		// Fetch existing meta value (defaults to 'yes')
		$close_others = get_post_meta( $post->ID, '_tcacc_close_others', true );
		$close_others = ! empty( $close_others ) ? $close_others : 'yes';

		// Fetch selected icon style (defaults to 'plus_minus')
		$icon_style = get_post_meta( $post->ID, '_tcacc_icon_style', true );
		$icon_style = ! empty( $icon_style ) ? $icon_style : 'plus_minus';

		// Retrieve saved values or set defaults
		$open_event = get_post_meta( $post->ID, '_tcacc_open_event', true );
		$open_event = ! empty( $open_event ) ? $open_event : 'click';

		// Retrieve FAQ Schema Setting (defaults to 'yes')
		$enable_faq_schema = get_post_meta( $post->ID, '_tcacc_enable_faq_schema', true );
		$enable_faq_schema = ( '' !== $enable_faq_schema ) ? $enable_faq_schema : 'no';

		$anim_speed = get_post_meta( $post->ID, '_tcacc_anim_speed', true );
		$anim_speed = ! empty( $anim_speed ) ? $anim_speed : '300';

		// Search box, Expand/Collapse All and Deep Links (default: off).
		$show_search     = get_post_meta( $post->ID, '_tcacc_show_search', true );
		$show_search     = ( 'yes' === $show_search ) ? 'yes' : 'no';
		$show_toggle_all = get_post_meta( $post->ID, '_tcacc_show_toggle_all', true );
		$show_toggle_all = ( 'yes' === $show_toggle_all ) ? 'yes' : 'no';
		$deep_link       = get_post_meta( $post->ID, '_tcacc_deep_link', true );
		$deep_link       = ( 'yes' === $deep_link ) ? 'yes' : 'no';
		$search_holder   = get_post_meta( $post->ID, '_tcacc_search_placeholder', true );

		// Layout. Extra layouts are added by the Pro add-on through the 'tcaccordion_layouts' filter.
		$layout_options = TCAccordion_Renderer::get_available_layouts();
		$layout         = get_post_meta( $post->ID, '_tcacc_layout', true );
		$layout         = isset( $layout_options[ $layout ] ) ? $layout : 'accordion';

		// Layout cards. Layouts that are not available are shown locked, with a PRO badge.
		$layout_cards = array(
			'accordion'  => __( 'Accordion', 'accordions-wp' ),
			'two-column' => __( 'Two-column FAQ', 'accordions-wp' ),
			'tabs'       => __( 'Tabs', 'accordions-wp' ),
			'horizontal' => __( 'Horizontal', 'accordions-wp' ),
		);
		$layout_img_base = defined( 'TCACCORDION_PLUGIN_URL' ) ? TCACCORDION_PLUGIN_URL . 'admin/images/' : '';


		$pro_ui = (bool) has_action( 'tcaccordion_save_settings' );

		if ( empty( $custom_accordion_columns_post_themes ) ) {
			$custom_accordion_columns_post_themes = 'theme1';
		}

		$themes = array(
			'theme1' => array( 'label' => __( 'Sun Flower', 'accordions-wp' ), 'img' => 'theme1.png' ),
			'theme2' => array( 'label' => __( 'Orange', 'accordions-wp' ),     'img' => 'theme2.png' ),
			'theme3' => array( 'label' => __( 'Pumpkin', 'accordions-wp' ),    'img' => 'theme3.png' ),
			'theme4' => array( 'label' => __( 'Alizarin', 'accordions-wp' ),   'img' => 'theme4.png' ),
			'theme5' => array( 'label' => __( 'Carrot', 'accordions-wp' ),     'img' => 'theme5.png' ),
			'theme-flat'     => array(
				'label'  => __( 'Modern Flat', 'accordions-wp' ),
				'img'    => 'theme-flat.png',
			),
			'theme-shadow'   => array(
				'label'  => __( 'Soft Shadow', 'accordions-wp' ),
				'img'    => 'theme-shadow.png',
			),
			'theme-dark'     => array(
				'label'  => __( 'Dark Mode', 'accordions-wp' ),
				'img'    => 'theme-dark.png',
			),
		);
		?>
		<table class="form-table tc-settings-table">
			<tr valign="top">
				<th scope="row"><label><?php esc_html_e( 'Accordion Themes', 'accordions-wp' ); ?></label></th>
				<td>
					<div class="tc-theme-picker">
						<?php foreach ( $themes as $key => $theme ) : ?>
							<?php 
								// FIX: Replaced $is_pro with $pro_ui and added ! empty() safety check
								$disabled     = false;
								$img_url      = defined( 'TCACCORDION_PLUGIN_URL' ) && isset( $theme['img'] ) ? TCACCORDION_PLUGIN_URL . 'admin/images/' . $theme['img'] : ''; 
							?>
							<label class="tc-theme-option <?php echo $disabled ? 'tc-theme-disabled' : ''; ?>">
								<input type="radio" 
									   name="custom_accordion_columns_post_themes" 
									   value="<?php echo esc_attr( $key ); ?>" 
									   <?php checked( $custom_accordion_columns_post_themes, $key ); ?> 
									   <?php disabled( $disabled ); ?> />
								<div class="tc-theme-card">
									<?php if ( $img_url ) : ?>
										<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( isset( $theme['label'] ) ? $theme['label'] : '' ); ?>" />
									<?php endif; ?>
									<span class="tc-theme-label">
										<?php echo esc_html( isset( $theme['label'] ) ? $theme['label'] : $key ); ?>
									</span>
								</div>
								<?php if ( $disabled ) : ?>
									<span class="tc-pro-badge-overlay"><small class="tc-pro-badge"><?php esc_html_e( 'PRO', 'accordions-wp' ); ?></small></span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>

			<!-- Layout (visual picker, placed right below the themes) -->
			<tr valign="top">
				<th scope="row"><label><?php esc_html_e( 'Layout', 'accordions-wp' ); ?></label></th>
				<td>
					<div class="tc-theme-picker tc-layout-picker">
						<?php foreach ( $layout_cards as $card_value => $card_label ) : ?>
							<?php $card_locked = ! isset( $layout_options[ $card_value ] ); ?>
							<label class="tc-theme-option <?php echo $card_locked ? 'tc-theme-disabled' : ''; ?>"<?php echo $card_locked ? ' title="' . esc_attr__( 'Available in Pro', 'accordions-wp' ) . '"' : ''; ?>>
								<input type="radio"
									   name="_tcacc_layout"
									   value="<?php echo esc_attr( $card_value ); ?>"
									   <?php checked( $layout, $card_value ); ?>
									   <?php disabled( $card_locked ); ?> />
								<div class="tc-theme-card">
									<?php if ( $layout_img_base ) : ?>
										<img src="<?php echo esc_url( $layout_img_base . 'layout-' . $card_value . '.svg' ); ?>" alt="" />
									<?php endif; ?>
									<span class="tc-theme-label"><?php echo esc_html( $card_label ); ?></span>
								</div>
								<?php if ( $card_locked ) : ?>
									<span class="tc-pro-badge-overlay"><small class="tc-pro-badge"><?php esc_html_e( 'PRO', 'accordions-wp' ); ?></small></span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="description">
						<?php esc_html_e( 'Two-column, Tabs and Horizontal apply on screens 768px and wider. On phones and small tablets every layout becomes a normal stacked accordion.', 'accordions-wp' ); ?>
						<?php if ( count( $layout_options ) < count( $layout_cards ) ) : ?>
							<a href="<?php echo esc_url( TCAccordion_Admin_Previews::upgrade_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Unlock Tabs and Horizontal in Pro', 'accordions-wp' ); ?></a>
						<?php endif; ?>
					</p>
				</td>
			</tr>

			<?php
			/**
			 * Extra settings for add-on layouts (for example the height of the horizontal layout).
			 */
			do_action( 'tcaccordion_layout_settings', $post );
			?>

			<!-- Auto Open Item Setting -->
			<tr valign="top">
				<th scope="row">
					<label for="tcacc_auto_open_item">
						<?php esc_html_e( 'Auto Open Item', 'accordions-wp' ); ?>
						
					</label>
				</th>
				<td>
					<input 
						type="number" 
						name="_tcacc_auto_open_item" 
						id="tcacc_auto_open_item" 
						min="0" 
						max="999" 
						value="<?php echo esc_attr( $auto_open_item ); ?>" 

					/>
					<p class="description">
						<?php esc_html_e( 'Specify which item number opens by default on load (e.g., 1 for 1st item, 0 to keep all closed).', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="tcacc_close_others"><?php esc_html_e( 'Close Other Items', 'accordions-wp' ); ?>
						
					</label>
				</th>
				<td>
					<select name="_tcacc_close_others" id="tcacc_close_others">
						<option value="yes" <?php selected( $close_others, 'yes' ); ?>><?php esc_html_e( 'Yes (Close other open items automatically)', 'accordions-wp' ); ?></option>
						<option value="no" <?php selected( $close_others, 'no' ); ?>><?php esc_html_e( 'No (Allow multiple items to stay open)', 'accordions-wp' ); ?></option>
					</select>
					
					<p class="description">
						<?php esc_html_e( 'Choose whether opening a new item automatically closes all other items.', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>


			<!-- Accordion Closeable Setting -->
			<tr valign="top">
				<th scope="row">
					<label for="tcacc_is_closeable">
						<?php esc_html_e( 'Accordion Closeable', 'accordions-wp' ); ?>
						
					</label>
				</th>
				<td>
					<select name="_tcacc_is_closeable" id="tcacc_is_closeable">
						<option value="yes" <?php selected( $is_closeable, 'yes' ); ?>><?php esc_html_e( 'Yes (Items can be collapsed)', 'accordions-wp' ); ?></option>
						<option value="no" <?php selected( $is_closeable, 'no' ); ?>><?php esc_html_e( 'No (Always keep one item open)', 'accordions-wp' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Choose whether clicking an open item can close it.', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="tcacc_icon_style"><?php esc_html_e( 'Accordion Icon Style', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<select name="_tcacc_icon_style" id="tcacc_icon_style">
						<option value="plus_minus" <?php selected( $icon_style, 'plus_minus' ); ?>><?php esc_html_e( 'Plus / Minus ( + / - )', 'accordions-wp' ); ?></option>
						<option value="chevron" <?php selected( $icon_style, 'chevron' ); ?>><?php esc_html_e( 'Chevron ( ❯ / 🔽 )', 'accordions-wp' ); ?></option>
						<option value="angle" <?php selected( $icon_style, 'angle' ); ?>><?php esc_html_e( 'Angle ( ❯ / 🔽 )', 'accordions-wp' ); ?></option>
						<option value="caret" <?php selected( $icon_style, 'caret' ); ?>><?php esc_html_e( 'Caret Solid ( ▶ / ▼ )', 'accordions-wp' ); ?></option>
						<option value="folder" <?php selected( $icon_style, 'folder' ); ?>><?php esc_html_e( 'Folder ( 📁 / 📂 )', 'accordions-wp' ); ?></option>
						<option value="none" <?php selected( $icon_style, 'none' ); ?>><?php esc_html_e( 'None (No Icon)', 'accordions-wp' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Select the icon style for accordion open/close indicators.', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>

			<!-- Open Trigger Event -->
			<tr valign="top">
				<th scope="row">
					<label for="_tcacc_open_event"><?php esc_html_e( 'Open Event', 'accordions-wp' ); ?>
						<?php if ( ! $pro_ui ) : ?>
							<span class="tcacc-pro-badge" style="color: #d63638; font-size: 11px; font-weight: 600; margin-left: 4px;"><?php esc_html_e( '(PRO)', 'accordions-wp' ); ?></span>
						<?php endif; ?>
					</label>
				</th>
				<td>
					<select name="_tcacc_open_event" id="_tcacc_open_event" <?php disabled( ! $pro_ui ); ?>>
						<option value="click" <?php selected( $open_event, 'click' ); ?>><?php esc_html_e( 'On Click', 'accordions-wp' ); ?></option>
						<option value="hover" <?php selected( $open_event, 'hover' ); ?>><?php esc_html_e( 'On Mouse Hover', 'accordions-wp' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Select hover triggers.', 'accordions-wp' ); ?></p>
				</td>
			</tr>

			<!-- Animation Speed Slider -->
			<tr valign="top">
				<th scope="row">
					<label for="_tcacc_anim_speed"><?php esc_html_e( 'Animation Speed (ms)', 'accordions-wp' ); ?>
						<?php if ( ! $pro_ui ) : ?>
							<span class="tcacc-pro-badge" style="color: #d63638; font-size: 11px; font-weight: 600; margin-left: 4px;"><?php esc_html_e( '(PRO)', 'accordions-wp' ); ?></span>
						<?php endif; ?>
					</label>
				</th>
				<td>
					<input type="range" name="_tcacc_anim_speed" id="_tcacc_anim_speed" min="100" max="1000" step="50" value="<?php echo esc_attr( $anim_speed ); ?>" <?php disabled( ! $pro_ui ); ?> oninput="this.nextElementSibling.value = this.value + 'ms'">
					<output><?php echo esc_html( $anim_speed ); ?>ms</output>
					<p class="description">
						<?php esc_html_e( 'Customize accordion slide animation speeds.', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>

			<!-- Enable FAQ Schema (JSON-LD) Field -->
			<tr valign="top">
				<th scope="row">
					<label for="_tcacc_enable_faq_schema"><?php esc_html_e( 'Enable FAQ Schema', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<label for="_tcacc_enable_faq_schema" style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
						<input 
							type="checkbox" 
							name="_tcacc_enable_faq_schema" 
							id="_tcacc_enable_faq_schema" 
							value="yes" 
							<?php checked( $enable_faq_schema, 'yes' ); ?> 
						/>
						<strong><?php esc_html_e( 'Enable Google FAQ Schema (JSON-LD)', 'accordions-wp' ); ?></strong>
					</label>
					<p class="description">
						<?php esc_html_e( 'Automatically generates valid FAQPage structured data to enable rich snippet Q&A listings in Google search results.', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>

			<!-- Search Box -->
			<tr valign="top">
				<th scope="row">
					<label for="_tcacc_show_search"><?php esc_html_e( 'Search Box', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<select name="_tcacc_show_search" id="_tcacc_show_search">
						<option value="no" <?php selected( $show_search, 'no' ); ?>><?php esc_html_e( 'Hide', 'accordions-wp' ); ?></option>
						<option value="yes" <?php selected( $show_search, 'yes' ); ?>><?php esc_html_e( 'Show', 'accordions-wp' ); ?></option>
					</select>
					<input type="text" name="_tcacc_search_placeholder" id="_tcacc_search_placeholder" class="regular-text" value="<?php echo esc_attr( $search_holder ); ?>" placeholder="<?php esc_attr_e( 'Search questions...', 'accordions-wp' ); ?>" />
					<p class="description">
						<?php esc_html_e( 'Adds a live search field above the accordion that filters items by title and content. Optionally change the placeholder text.', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>

			<!-- Expand / Collapse All -->
			<tr valign="top">
				<th scope="row">
					<label for="_tcacc_show_toggle_all"><?php esc_html_e( 'Expand / Collapse All', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<select name="_tcacc_show_toggle_all" id="_tcacc_show_toggle_all">
						<option value="no" <?php selected( $show_toggle_all, 'no' ); ?>><?php esc_html_e( 'Hide', 'accordions-wp' ); ?></option>
						<option value="yes" <?php selected( $show_toggle_all, 'yes' ); ?>><?php esc_html_e( 'Show', 'accordions-wp' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Shows "Expand all" and "Collapse all" buttons above the accordion.', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>

			<!-- Deep Links -->
			<tr valign="top">
				<th scope="row">
					<label for="_tcacc_deep_link"><?php esc_html_e( 'Deep Links', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<select name="_tcacc_deep_link" id="_tcacc_deep_link">
						<option value="no" <?php selected( $deep_link, 'no' ); ?>><?php esc_html_e( 'Disabled', 'accordions-wp' ); ?></option>
						<option value="yes" <?php selected( $deep_link, 'yes' ); ?>><?php esc_html_e( 'Enabled', 'accordions-wp' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Each item gets a link based on its title (for example yoursite.com/faq/#return-policy). Opening that link opens and scrolls to the item, and clicking an item updates the address bar.', 'accordions-wp' ); ?>
					</p>
				</td>
			</tr>

			<?php
			if ( has_action( 'tcaccordion_pro_settings' ) ) {
				/**
				 * Settings sections added by add-ons (groups, dynamic content, analytics, WooCommerce rules...).
				 * Each handler prints complete table rows.
				 *
				 * @param WP_Post $post Accordion post.
				 */
				do_action( 'tcaccordion_pro_settings', $post );
			} else {
				// Free version: a picture of the Pro settings with an upgrade message.
				TCAccordion_Admin_Previews::render_pro_teaser();
			}
			?>

			<!-- WooCommerce Integration Settings -->
			<?php
			if ( class_exists( 'WooCommerce' ) ) :
				$woo_tab_enable = get_post_meta( $post->ID, '_tcacc_woo_enable', true );
				$woo_tab_enable = ! empty( $woo_tab_enable ) ? $woo_tab_enable : 'no';

				$woo_tab_title = get_post_meta( $post->ID, '_tcacc_woo_tab_title', true );
				$woo_tab_title = ! empty( $woo_tab_title ) ? $woo_tab_title : __( 'Product FAQs', 'accordions-wp' );

				$woo_location = get_post_meta( $post->ID, '_tcacc_woo_location', true );
				$woo_location = ! empty( $woo_location ) ? $woo_location : 'tab';
				?>
				<tr valign="top">
					<th scope="row">
						<label for="_tcacc_woo_enable"><?php esc_html_e( 'WooCommerce Integration', 'accordions-wp' ); ?></label>
					</th>
					<td>
						<div style="display: flex; flex-direction: column; gap: 10px;">
							<label style="cursor: pointer;">
								<input type="checkbox" name="_tcacc_woo_enable" id="_tcacc_woo_enable" value="yes" <?php checked( $woo_tab_enable, 'yes' ); ?> />
								<strong><?php esc_html_e( 'Display on Single Product Pages', 'accordions-wp' ); ?></strong>
							</label>

							<div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
								<label for="_tcacc_woo_location"><strong><?php esc_html_e( 'Display Location:', 'accordions-wp' ); ?></strong></label>
								<select name="_tcacc_woo_location" id="_tcacc_woo_location">
									<option value="tab" <?php selected( $woo_location, 'tab' ); ?>><?php esc_html_e( 'Custom Product Tab', 'accordions-wp' ); ?></option>
									<option value="after_summary" <?php selected( $woo_location, 'after_summary' ); ?>><?php esc_html_e( 'Below Add to Cart / Summary', 'accordions-wp' ); ?></option>
								</select>
							</div>

							<div style="display: flex; align-items: center; gap: 10px;">
								<label for="_tcacc_woo_tab_title"><strong><?php esc_html_e( 'Tab / Section Title:', 'accordions-wp' ); ?></strong></label>
								<input type="text" name="_tcacc_woo_tab_title" id="_tcacc_woo_tab_title" value="<?php echo esc_attr( $woo_tab_title ); ?>" class="regular-text" />
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Automatically embed this accordion on single WooCommerce product pages.', 'accordions-wp' ); ?></p>
					</td>
				</tr>
			<?php endif; ?>

		</table>
		<?php
	}


	/**
	 * Tab 3 Render: Settings & Theme Picker Controls
	 */
	private function render_accordion_design_tab( $post ) {

		// Meta Values Retrieve
		$custom_accordion_title_bg_color           = get_post_meta( $post->ID, 'custom_accordion_title_bg_color', true );
		$custom_accordion_title_font_color         = get_post_meta( $post->ID, 'custom_accordion_title_font_color', true );

		// Hover and open (active) state colors.
		$state_colors = array(
			'hover'  => array(
				'title_bg'    => get_post_meta( $post->ID, 'custom_accordion_title_bg_color_hover', true ),
				'title_color' => get_post_meta( $post->ID, 'custom_accordion_title_font_color_hover', true ),
				'icon_color'  => get_post_meta( $post->ID, 'custom_accordion_icon_color_hover', true ),
			),
			'active' => array(
				'title_bg'    => get_post_meta( $post->ID, 'custom_accordion_title_bg_color_active', true ),
				'title_color' => get_post_meta( $post->ID, 'custom_accordion_title_font_color_active', true ),
				'icon_color'  => get_post_meta( $post->ID, 'custom_accordion_icon_color_active', true ),
			),
		);
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
		// Retrieve existing icon color meta values
		$custom_accordion_icon_color    = get_post_meta( $post->ID, 'custom_accordion_icon_color', true );
		$custom_accordion_icon_bg_color = get_post_meta( $post->ID, 'custom_accordion_icon_bg_color', true );

		$custom_accordion_border_style = get_post_meta( $post->ID, 'custom_accordion_border_style', true );
		$custom_accordion_border_style = ! empty( $custom_accordion_border_style ) ? $custom_accordion_border_style : 'none';

		$custom_accordion_border_width = get_post_meta( $post->ID, 'custom_accordion_border_width', true );
		$custom_accordion_border_width = ( '' !== $custom_accordion_border_width ) ? $custom_accordion_border_width : '1';

		$custom_accordion_border_color = get_post_meta( $post->ID, 'custom_accordion_border_color', true );

		?>
		<table class="form-table tc-settings-table">

			<!-- Title BG Color -->
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-title-bg-color"><?php esc_html_e( 'Title BG Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_title_bg_color" class="tc-color-field" id="custom-accordion-title-bg-color" type="text" value="<?php echo esc_attr( $custom_accordion_title_bg_color ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom-accordion-title-font-color"><?php esc_html_e( 'Title Font Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_title_font_color" class="tc-color-field" id="custom-accordion-title-font-color" type="text" value="<?php echo esc_attr( $custom_accordion_title_font_color ); ?>" /></td>
			</tr>

			<!-- Hover state colors -->
			<tr valign="top">
				<th scope="row" colspan="2">
					<h3 style="margin:14px 0 0;"><?php esc_html_e( 'Title Hover Colors', 'accordions-wp' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Optional. Applied when the mouse is over a title (or it has keyboard focus). Leave empty to keep the theme colors.', 'accordions-wp' ); ?></p>
				</th>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-title-bg-color-hover"><?php esc_html_e( 'Hover BG Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_title_bg_color_hover" class="tc-color-field" id="custom-accordion-title-bg-color-hover" type="text" value="<?php echo esc_attr( $state_colors['hover']['title_bg'] ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-title-font-color-hover"><?php esc_html_e( 'Hover Font Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_title_font_color_hover" class="tc-color-field" id="custom-accordion-title-font-color-hover" type="text" value="<?php echo esc_attr( $state_colors['hover']['title_color'] ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-icon-color-hover"><?php esc_html_e( 'Hover Icon Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_icon_color_hover" class="tc-color-field" id="custom-accordion-icon-color-hover" type="text" value="<?php echo esc_attr( $state_colors['hover']['icon_color'] ); ?>" /></td>
			</tr>

			<!-- Open (active) state colors -->
			<tr valign="top">
				<th scope="row" colspan="2">
					<h3 style="margin:14px 0 0;"><?php esc_html_e( 'Title Open (Active) Colors', 'accordions-wp' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Optional. Applied to the title of the item that is currently open (the selected tab in the Tabs layout). Leave empty to keep the theme colors.', 'accordions-wp' ); ?></p>
				</th>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-title-bg-color-active"><?php esc_html_e( 'Open BG Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_title_bg_color_active" class="tc-color-field" id="custom-accordion-title-bg-color-active" type="text" value="<?php echo esc_attr( $state_colors['active']['title_bg'] ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-title-font-color-active"><?php esc_html_e( 'Open Font Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_title_font_color_active" class="tc-color-field" id="custom-accordion-title-font-color-active" type="text" value="<?php echo esc_attr( $state_colors['active']['title_color'] ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-icon-color-active"><?php esc_html_e( 'Open Icon Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_icon_color_active" class="tc-color-field" id="custom-accordion-icon-color-active" type="text" value="<?php echo esc_attr( $state_colors['active']['icon_color'] ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_title_font_size"><?php esc_html_e( 'Title Font Size', 'accordions-wp' ); ?></label></th>
				<td><input type="number" name="custom_accordion_title_font_size" id="custom_accordion_title_font_size" value="<?php echo ! empty( $custom_accordion_title_font_size ) ? esc_attr( $custom_accordion_title_font_size ) : '18'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_title_line_height"><?php esc_html_e( 'Title Line Height', 'accordions-wp' ); ?></label></th>
				<td><input type="number" step="0.1" name="custom_accordion_title_line_height" id="custom_accordion_title_line_height" min="0.8" max="3" value="<?php echo ! empty( $custom_accordion_title_line_height ) ? esc_attr( $custom_accordion_title_line_height ) : '1.4'; ?>"> (e.g. 1.4)</td>
			</tr>

			<tr valign="top">
				<th scope="row">
					<label><?php esc_html_e( 'Title Text Position', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<div class="tcacc-icon-selector">
						<label class="tcacc-icon-btn <?php echo ( 'left' === $_tpaccpro_wiki_acc_themes_title_position ) ? 'active' : ''; ?>">
							<input type="radio" name="_tpaccpro_wiki_acc_themes_title_position" value="left" <?php checked( $_tpaccpro_wiki_acc_themes_title_position, 'left' ); ?>>
							<span class="dashicons dashicons-editor-alignleft"></span>
						</label>

						<label class="tcacc-icon-btn <?php echo ( 'center' === $_tpaccpro_wiki_acc_themes_title_position ) ? 'active' : ''; ?>">
							<input type="radio" name="_tpaccpro_wiki_acc_themes_title_position" value="center" <?php checked( $_tpaccpro_wiki_acc_themes_title_position, 'center' ); ?>>
							<span class="dashicons dashicons-editor-aligncenter"></span>
						</label>

						<label class="tcacc-icon-btn <?php echo ( 'right' === $_tpaccpro_wiki_acc_themes_title_position ) ? 'active' : ''; ?>">
							<input type="radio" name="_tpaccpro_wiki_acc_themes_title_position" value="right" <?php checked( $_tpaccpro_wiki_acc_themes_title_position, 'right' ); ?>>
							<span class="dashicons dashicons-editor-alignright"></span>
						</label>
					</div>
				</td>
			</tr>

			<!-- Border Style -->
			<tr valign="top">
				<th scope="row">
					<label for="custom_accordion_border_style"><?php esc_html_e( 'Border Style', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<select name="custom_accordion_border_style" id="custom_accordion_border_style">
						<option value="none" <?php selected( $custom_accordion_border_style, 'none' ); ?>><?php esc_html_e( 'None', 'accordions-wp' ); ?></option>
						<option value="solid" <?php selected( $custom_accordion_border_style, 'solid' ); ?>><?php esc_html_e( 'Solid', 'accordions-wp' ); ?></option>
						<option value="dashed" <?php selected( $custom_accordion_border_style, 'dashed' ); ?>><?php esc_html_e( 'Dashed', 'accordions-wp' ); ?></option>
						<option value="dotted" <?php selected( $custom_accordion_border_style, 'dotted' ); ?>><?php esc_html_e( 'Dotted', 'accordions-wp' ); ?></option>
						<option value="double" <?php selected( $custom_accordion_border_style, 'double' ); ?>><?php esc_html_e( 'Double', 'accordions-wp' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Select border style for accordion items.', 'accordions-wp' ); ?></p>
				</td>
			</tr>

			<!-- Border Width -->
			<tr valign="top">
				<th scope="row">
					<label for="custom_accordion_border_width"><?php esc_html_e( 'Border Width', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<input type="number" name="custom_accordion_border_width" id="custom_accordion_border_width" min="0" max="10" value="<?php echo esc_attr( $custom_accordion_border_width ); ?>"> px
					<p class="description"><?php esc_html_e( 'Specify border thickness in pixels.', 'accordions-wp' ); ?></p>
				</td>
			</tr>

			<!-- Border Color -->
			<tr valign="top">
				<th scope="row">
					<label for="custom-accordion-border-color"><?php esc_html_e( 'Border Color', 'accordions-wp' ); ?></label>
				</th>
				<td>
					<input name="custom_accordion_border_color" class="tc-color-field" id="custom-accordion-border-color" type="text" value="<?php echo esc_attr( $custom_accordion_border_color ); ?>" />
				</td>
			</tr>


			<tr valign="top">
				<th scope="row"><label for="_tpaccpro_wiki_acc_themes_show_hide_icons"><?php esc_html_e( 'Show/Hide Icon', 'accordions-wp' ); ?></label></th>
				<td>
					<select name="_tpaccpro_wiki_acc_themes_show_hide_icons" id="_tpaccpro_wiki_acc_themes_show_hide_icons">
						<option value="1" <?php selected( $_tpaccpro_wiki_acc_themes_show_hide_icons, '1' ); ?>><?php esc_html_e( 'Show', 'accordions-wp' ); ?></option>
						<option value="2" <?php selected( $_tpaccpro_wiki_acc_themes_show_hide_icons, '2' ); ?>><?php esc_html_e( 'Hide', 'accordions-wp' ); ?></option>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="_tpaccpro_wiki_acc_themes_icon_position"><?php esc_html_e( 'Icon Position', 'accordions-wp' ); ?></label></th>
				<td>
					<select name="_tpaccpro_wiki_acc_themes_icon_position" id="_tpaccpro_wiki_acc_themes_icon_position">
						<option value="1" <?php selected( $_tpaccpro_wiki_acc_themes_icon_position, '1' ); ?>><?php esc_html_e( 'Left', 'accordions-wp' ); ?></option>
						<option value="2" <?php selected( $_tpaccpro_wiki_acc_themes_icon_position, '2' ); ?>><?php esc_html_e( 'Right', 'accordions-wp' ); ?></option>
					</select>
				</td>
			</tr>

			<!-- Icon Color Field -->
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-icon-color"><?php esc_html_e( 'Icon Color', 'accordions-wp' ); ?></label></th>
				<td>
					<input name="custom_accordion_icon_color" class="tc-color-field" id="custom-accordion-icon-color" type="text" value="<?php echo esc_attr( $custom_accordion_icon_color ); ?>" />
				</td>
			</tr>

			<!-- Icon Background Color Field -->
			<tr valign="top">
				<th scope="row"><label for="custom-accordion-icon-bg-color"><?php esc_html_e( 'Icon BG Color', 'accordions-wp' ); ?></label></th>
				<td>
					<input name="custom_accordion_icon_bg_color" class="tc-color-field" id="custom-accordion-icon-bg-color" type="text" value="<?php echo esc_attr( $custom_accordion_icon_bg_color ); ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom-accordion-content-bg-color"><?php esc_html_e( 'Content BG Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_content_bg_color" class="tc-color-field" id="custom-accordion-content-bg-color" type="text" value="<?php echo esc_attr( $custom_accordion_content_bg_color ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom-accordion-content-font-color"><?php esc_html_e( 'Content Font Color', 'accordions-wp' ); ?></label></th>
				<td><input name="custom_accordion_content_font_color" class="tc-color-field" id="custom-accordion-content-font-color" type="text" value="<?php echo esc_attr( $custom_accordion_content_font_color ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_font_size"><?php esc_html_e( 'Content Font Size', 'accordions-wp' ); ?></label></th>
				<td><input type="number" name="custom_accordion_content_font_size" id="custom_accordion_content_font_size" value="<?php echo ! empty( $custom_accordion_content_font_size ) ? esc_attr( $custom_accordion_content_font_size ) : '16'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_line_height"><?php esc_html_e( 'Content Line Height', 'accordions-wp' ); ?></label></th>
				<td><input type="number" step="0.1" name="custom_accordion_content_line_height" id="custom_accordion_content_line_height" min="0.8" max="3" value="<?php echo ! empty( $custom_accordion_content_line_height ) ? esc_attr( $custom_accordion_content_line_height ) : '1.6'; ?>"> (e.g. 1.6)</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_letter_spacing"><?php esc_html_e( 'Content Letter Spacing', 'accordions-wp' ); ?></label></th>
				<td><input type="number" step="0.1" name="custom_accordion_content_letter_spacing" id="custom_accordion_content_letter_spacing" min="0" max="10" value="<?php echo ! empty( $custom_accordion_content_letter_spacing ) ? esc_attr( $custom_accordion_content_letter_spacing ) : '0'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="_tpaccpro_wiki_acc_theme_content_margin"><?php esc_html_e( 'Margin Between Accordion', 'accordions-wp' ); ?></label></th>
				<td><input type="number" name="_tpaccpro_wiki_acc_theme_content_margin" id="_tpaccpro_wiki_acc_theme_content_margin" value="<?php echo ! empty( $_tpaccpro_wiki_acc_theme_content_margin ) ? esc_attr( $_tpaccpro_wiki_acc_theme_content_margin ) : '15'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_padding"><?php esc_html_e( 'Content Padding', 'accordions-wp' ); ?></label></th>
				<td><input type="number" name="custom_accordion_content_padding" id="custom_accordion_content_padding" value="<?php echo ! empty( $custom_accordion_content_padding ) ? esc_attr( $custom_accordion_content_padding ) : '15'; ?>"> px</td>
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
			return apply_filters(
				'tcaccordion_admin_item',
				array(
					'title'       => $title,
					'description' => $details,
				),
				$data
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
					<?php echo esc_html( $title ? $title : __( 'New Accordion', 'accordions-wp' ) ); ?>
				</strong>
				<span class="tcaccordion-header-actions">
					<button type="button" class="button-link tcaccordion-duplicate" title="<?php esc_attr_e( 'Duplicate this item', 'accordions-wp' ); ?>" aria-label="<?php esc_attr_e( 'Duplicate this item', 'accordions-wp' ); ?>">
						<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
					</button>
					<button type="button" class="button-link tcaccordion-remove" title="<?php esc_attr_e( 'Remove this item', 'accordions-wp' ); ?>" aria-label="<?php esc_attr_e( 'Remove this item', 'accordions-wp' ); ?>">
						<span class="dashicons dashicons-trash" aria-hidden="true"></span>
					</button>
				</span>
				<button type="button" class="button-link tcaccordion-toggle" aria-label="<?php esc_attr_e( 'Show or hide this item', 'accordions-wp' ); ?>">
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
				</button>
			</div>

			<div class="tcaccordion-body">
				<p>
					<label><strong><?php esc_html_e( 'Accordion Title', 'accordions-wp' ); ?></strong></label>
					<input
						type="text"
						class="widefat tcaccordion-title"
						name="<?php echo esc_attr( self::META_KEY ); ?>[<?php echo esc_attr( $index ); ?>][title]"
						value="<?php echo esc_attr( $title ); ?>"
					>
				</p>

				<p>
					<label><strong><?php esc_html_e( 'Description', 'accordions-wp' ); ?></strong></label>
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

				<?php
				if ( has_action( 'tcaccordion_item_extra_fields' ) ) {
					/**
					 * Extra per-item fields added by add-ons (for example the nested accordion picker).
					 *
					 * @param string|int $index Item index used in field names.
					 * @param array      $item  Item data.
					 */
					do_action( 'tcaccordion_item_extra_fields', $index, $item );
				} else {
					TCAccordion_Admin_Previews::render_item_teaser();
				}
				?>
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
			$raw_posted_items = wp_unslash( $_POST[ self::META_KEY ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per key/value inside foreach.

			if ( is_array( $raw_posted_items ) ) {
				foreach ( array_values( $raw_posted_items ) as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}

					if ( $max_limit > 0 && count( $items ) >= $max_limit ) {
						break;
					}

					$title   = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
					
					// Updated sanitization to fully allow iframes and video embed attributes
					$details = '';
					if ( isset( $item['description'] ) ) {
						$iframe_allowed = wp_kses_allowed_html( 'post' );
						$iframe_allowed['iframe'] = array(
							'src'             => true,
							'width'           => true,
							'height'          => true,
							'frameborder'     => true,
							'allow'           => true,
							'allowfullscreen' => true,
							'title'           => true,
							'style'           => true,
							'class'           => true,
							'loading'         => true,
							'referrerpolicy'  => true,
						);
						$details = wp_kses( $item['description'], $iframe_allowed );
					}

					if ( '' !== $title || '' !== $details ) {
						$clean_item = array(
							'title'       => $title,
							'description' => $details,
						);

						// Add-ons can keep extra per-item fields (for example a nested accordion).
						$items[] = apply_filters( 'tcaccordion_sanitize_item', $clean_item, $item, $post_id );
					}
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

		// Save Enable FAQ Schema Checkbox Setting
		$enable_faq_schema = isset( $_POST['_tcacc_enable_faq_schema'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_tcacc_enable_faq_schema', $enable_faq_schema );

		// Save Layout settings.
		if ( isset( $_POST['_tcacc_layout'] ) ) {
			$layout_val = sanitize_text_field( wp_unslash( $_POST['_tcacc_layout'] ) );
			$layout_val = array_key_exists( $layout_val, TCAccordion_Renderer::get_available_layouts() ) ? $layout_val : 'accordion';
			update_post_meta( $post_id, '_tcacc_layout', $layout_val );
		}

		// Save Search Box, Expand/Collapse All and Deep Link settings.
		foreach ( array( '_tcacc_show_search', '_tcacc_show_toggle_all', '_tcacc_deep_link' ) as $toggle_key ) {
			if ( isset( $_POST[ $toggle_key ] ) ) {
				$toggle_val = sanitize_text_field( wp_unslash( $_POST[ $toggle_key ] ) );
				update_post_meta( $post_id, $toggle_key, ( 'yes' === $toggle_val ) ? 'yes' : 'no' );
			}
		}

		if ( isset( $_POST['_tcacc_search_placeholder'] ) ) {
			update_post_meta( $post_id, '_tcacc_search_placeholder', sanitize_text_field( wp_unslash( $_POST['_tcacc_search_placeholder'] ) ) );
		}


		if ( isset( $_POST['_tcacc_woo_enable'] ) ) {
			update_post_meta( $post_id, '_tcacc_woo_enable', 'yes' );
		} else {
			update_post_meta( $post_id, '_tcacc_woo_enable', 'no' );
		}

		if ( isset( $_POST['_tcacc_woo_tab_title'] ) ) {
			update_post_meta( $post_id, '_tcacc_woo_tab_title', sanitize_text_field( wp_unslash( $_POST['_tcacc_woo_tab_title'] ) ) );
		}

		if ( isset( $_POST['_tcacc_woo_location'] ) ) {
			update_post_meta( $post_id, '_tcacc_woo_location', sanitize_text_field( wp_unslash( $_POST['_tcacc_woo_location'] ) ) );
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
			'custom_accordion_title_bg_color_hover'     => 'sanitize_hex_color',
			'custom_accordion_title_font_color_hover'   => 'sanitize_hex_color',
			'custom_accordion_icon_color_hover'         => 'sanitize_hex_color',
			'custom_accordion_title_bg_color_active'    => 'sanitize_hex_color',
			'custom_accordion_title_font_color_active'  => 'sanitize_hex_color',
			'custom_accordion_icon_color_active'        => 'sanitize_hex_color',
			'custom_accordion_content_bg_color'         => 'sanitize_hex_color',
			'custom_accordion_content_font_color'       => 'sanitize_hex_color',
			'custom_accordion_icon_color'               => 'sanitize_hex_color', // Added Icon Color
			'custom_accordion_icon_bg_color'            => 'sanitize_hex_color', // Added Icon BG Color
			'custom_accordion_border_style'             => 'sanitize_text_field', // Added Border Style
			'custom_accordion_border_width'             => 'sanitize_text_field', // Added Border Width
			'custom_accordion_border_color'             => 'sanitize_hex_color',
		);

		foreach ( $style_fields as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is unslashed first and sanitized via the specific callback below.
				$raw_val = wp_unslash( $_POST[ $field ] );

				if ( 'sanitize_hex_color' === $sanitize_callback ) {
					$val = sanitize_hex_color( $raw_val );
				} elseif ( 'sanitize_text_field' === $sanitize_callback ) {
					$val = sanitize_text_field( $raw_val );
				} elseif ( is_callable( $sanitize_callback ) ) {
					$val = call_user_func( $sanitize_callback, $raw_val );
				} else {
					$val = sanitize_text_field( $raw_val );
				}

				update_post_meta( $post_id, $field, $val );
			}
		}

		if ( isset( $_POST['_tcacc_icon_style'] ) ) {
			$icon_style     = sanitize_text_field( wp_unslash( $_POST['_tcacc_icon_style'] ) );
			$allowed_styles = array( 'plus_minus', 'chevron', 'angle', 'caret', 'folder', 'none' );
			$icon_style     = in_array( $icon_style, $allowed_styles, true ) ? $icon_style : 'plus_minus';
			update_post_meta( $post_id, '_tcacc_icon_style', $icon_style );
		}

		// Behavior settings
		if ( isset( $_POST['_tcacc_auto_open_item'] ) ) {
			update_post_meta( $post_id, '_tcacc_auto_open_item', absint( wp_unslash( $_POST['_tcacc_auto_open_item'] ) ) );
		}

		if ( isset( $_POST['_tcacc_is_closeable'] ) ) {
			$closeable = sanitize_text_field( wp_unslash( $_POST['_tcacc_is_closeable'] ) );
			update_post_meta( $post_id, '_tcacc_is_closeable', in_array( $closeable, array( 'yes', 'no' ), true ) ? $closeable : 'yes' );
		}

		if ( isset( $_POST['_tcacc_close_others'] ) ) {
			$close_others = sanitize_text_field( wp_unslash( $_POST['_tcacc_close_others'] ) );
			update_post_meta( $post_id, '_tcacc_close_others', in_array( $close_others, array( 'yes', 'no' ), true ) ? $close_others : 'yes' );
		}

		/**
		 * Lets add-ons save their own settings. The nonce and permissions are already verified above.
		 *
		 * @param int $post_id Accordion ID.
		 */
		do_action( 'tcaccordion_save_settings', $post_id );
	}

	/**
	 * Custom Admin Post Columns
	 */
	public function register_admin_columns( $columns ) {
		return array(
			'cb'          => '<input type="checkbox" />',
			'title'       => __( 'Shortcode Name', 'accordions-wp' ),
			'shortcode'   => __( 'Shortcode', 'accordions-wp' ),
			'doshortcode' => __( 'Template Shortcode', 'accordions-wp' ),
			'date'        => __( 'Date', 'accordions-wp' ),
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
					<p><strong><?php esc_html_e( 'Shortcode', 'accordions-wp' ); ?>:</strong> <span id="tc-sc-notice" style="color: green; display: none; margin-left: 10px;"><?php esc_html_e( 'Copied!', 'accordions-wp' ); ?></span></p>
					<input type="text" style="width:100%; cursor:pointer;" value="<?php echo esc_attr( $shortcode ); ?>" readonly onclick="tcCopyNotice(this, 'tc-sc-notice')" />
				</div>
				<div style="width: 50%;">
					<p><strong><?php esc_html_e( 'PHP Code', 'accordions-wp' ); ?>:</strong> <span id="tc-php-notice" style="color: green; display: none; margin-left: 10px;"><?php esc_html_e( 'Copied!', 'accordions-wp' ); ?></span></p>
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
			return __( 'Accordion Group Title', 'accordions-wp' );
		}
		return $title;
	}

	/**
	 * AJAX Handler to generate exact live frontend markup from unsaved form data.
	 */
	public function ajax_render_live_preview() {
		check_ajax_referer( 'tcaccordion_save_action', 'security' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'accordions-wp' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		// Parse serialized form data string sent from JavaScript
		$posted_meta = array();
		if ( ! empty( $_POST['form_data'] ) ) {
			$raw_form_data = sanitize_text_field( wp_unslash( $_POST['form_data'] ) );
			wp_parse_str( $raw_form_data, $posted_meta );
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
		}
	}

}

new TCAccordion_Metabox();