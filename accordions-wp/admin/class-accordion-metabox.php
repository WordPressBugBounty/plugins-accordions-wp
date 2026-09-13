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
		return tc_acc_is_pro() ? self::PRO_MAX_ITEMS : self::FREE_MAX_ITEMS;
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

			<?php if ( ! tc_acc_is_pro() ) : ?>
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

		$anim_speed = get_post_meta( $post->ID, '_tcacc_anim_speed', true );
		$anim_speed = ! empty( $anim_speed ) ? $anim_speed : '300';


		$is_pro_active  = tc_acc_is_pro();
		
		$is_pro = function_exists( 'tc_acc_is_pro' ) && tc_acc_is_pro();

		if ( empty( $custom_accordion_columns_post_themes ) ) {
			$custom_accordion_columns_post_themes = 'theme1';
		}

		$themes = array(
			'theme1' => array( 'label' => __( 'Sun Flower', 'tcaccordion' ), 'img' => 'theme1.png' ),
			'theme2' => array( 'label' => __( 'Orange', 'tcaccordion' ),     'img' => 'theme2.png' ),
			'theme3' => array( 'label' => __( 'Pumpkin', 'tcaccordion' ),    'img' => 'theme3.png' ),
			'theme4' => array( 'label' => __( 'Alizarin', 'tcaccordion' ),   'img' => 'theme4.png' ),
			'theme5' => array( 'label' => __( 'Carrot', 'tcaccordion' ),     'img' => 'theme5.png' ),
			'theme-flat'     => array(
				'label'  => __( 'Modern Flat', 'tcaccordion' ),
				'img'    => 'theme-flat.png',
				'is_pro' => true,
			),
			'theme-shadow'   => array(
				'label'  => __( 'Soft Shadow', 'tcaccordion' ),
				'img'    => 'theme-shadow.png',
				'is_pro' => true,
			),
			'theme-dark'     => array(
				'label'  => __( 'Dark Mode', 'tcaccordion' ),
				'img'    => 'theme-dark.png',
				'is_pro' => true,
			),
		);
		?>
		<table class="form-table tc-settings-table">
			<tr valign="top">
				<th scope="row"><label><?php esc_html_e( 'Accordion Themes', 'tcaccordion' ); ?></label></th>
				<td>
					<div class="tc-theme-picker">
						<?php foreach ( $themes as $key => $theme ) : ?>
							<?php 
								// FIX: Replaced $is_pro with $is_pro_active and added ! empty() safety check
								$is_theme_pro = ! empty( $theme['is_pro'] );
								$disabled     = ( $is_theme_pro && ! $is_pro_active ); 
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
									<span class="tc-pro-badge-overlay"><small class="tc-pro-badge"><?php esc_html_e( 'PRO', 'tcaccordion' ); ?></small></span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>

			<!-- Auto Open Item Setting -->
			<tr valign="top">
			    <th scope="row">
			        <label for="tcacc_auto_open_item">
			            <?php esc_html_e( 'Auto Open Item', 'tcaccordion' ); ?>
			            <?php if ( ! $is_pro_active ) : ?>
			                <span class="tcacc-pro-badge" style="color: #d63638; font-size: 11px; font-weight: 600; margin-left: 4px;"><?php esc_html_e( '(PRO)', 'tcaccordion' ); ?></span>
			            <?php endif; ?>
			        </label>
			    </th>
			    <td>
			        <input 
			            type="number" 
			            name="_tcacc_auto_open_item" 
			            id="tcacc_auto_open_item" 
			            min="0" 
			            max="25" 
			            value="<?php echo esc_attr( $auto_open_item ); ?>" 
			            <?php disabled( ! $is_pro_active ); ?> 
			        />
			        <p class="description">
			            <?php esc_html_e( 'Specify which item number opens by default on load (e.g., 1 for 1st item, 0 to keep all closed).', 'tcaccordion' ); ?>
			        </p>
			    </td>
			</tr>

			<tr>
		        <th scope="row">
		            <label for="tcacc_close_others"><?php esc_html_e( 'Close Other Items', 'tcaccordion' ); ?>
			            <?php if ( ! $is_pro_active ) : ?>
			                <span class="tcacc-pro-badge" style="color: #d63638; font-size: 11px; font-weight: 600; margin-left: 4px;"><?php esc_html_e( '(PRO)', 'tcaccordion' ); ?></span>
			            <?php endif; ?>		            	
		            </label>
		        </th>
		        <td>
		            <select name="_tcacc_close_others" id="tcacc_close_others" <?php disabled( ! $is_pro_active ); ?>>
		                <option value="yes" <?php selected( $close_others, 'yes' ); ?>><?php esc_html_e( 'Yes (Close other open items automatically)', 'tcaccordion' ); ?></option>
		                <option value="no" <?php selected( $close_others, 'no' ); ?>><?php esc_html_e( 'No (Allow multiple items to stay open)', 'tcaccordion' ); ?></option>
		            </select>
		            <?php if ( ! $is_pro_active ) : ?>
		                <input type="hidden" name="_tcacc_close_others" value="<?php echo esc_attr( $close_others ); ?>" />
		            <?php endif; ?>
		            <p class="description">
		                <?php esc_html_e( 'Choose whether opening a new item automatically closes all other items.', 'tcaccordion' ); ?>
		            </p>
		        </td>
		    </tr>


			<!-- Accordion Closeable Setting -->
			<tr valign="top">
			    <th scope="row">
			        <label for="tcacc_is_closeable">
			            <?php esc_html_e( 'Accordion Closeable', 'tcaccordion' ); ?>
			            <?php if ( ! $is_pro_active ) : ?>
			                <span class="tcacc-pro-badge" style="color: #d63638; font-size: 11px; font-weight: 600; margin-left: 4px;"><?php esc_html_e( '(PRO)', 'tcaccordion' ); ?></span>
			            <?php endif; ?>
			        </label>
			    </th>
			    <td>
			        <select name="_tcacc_is_closeable" id="tcacc_is_closeable" <?php disabled( ! $is_pro_active ); ?>>
			            <option value="yes" <?php selected( $is_closeable, 'yes' ); ?>><?php esc_html_e( 'Yes (Items can be collapsed)', 'tcaccordion' ); ?></option>
			            <option value="no" <?php selected( $is_closeable, 'no' ); ?>><?php esc_html_e( 'No (Always keep one item open)', 'tcaccordion' ); ?></option>
			        </select>
			        <p class="description">
			            <?php esc_html_e( 'Choose whether clicking an open item can close it.', 'tcaccordion' ); ?>
			        </p>
			    </td>
			</tr>

			<tr>
			    <th scope="row">
			        <label for="tcacc_icon_style"><?php esc_html_e( 'Accordion Icon Style', 'tcaccordion' ); ?></label>
			    </th>
			    <td>
			        <select name="_tcacc_icon_style" id="tcacc_icon_style">
			            <option value="plus_minus" <?php selected( $icon_style, 'plus_minus' ); ?>><?php esc_html_e( 'Plus / Minus ( + / - )', 'tcaccordion' ); ?></option>
			            <option value="chevron" <?php selected( $icon_style, 'chevron' ); ?>><?php esc_html_e( 'Chevron ( ❯ / 🔽 )', 'tcaccordion' ); ?></option>
			            <option value="angle" <?php selected( $icon_style, 'angle' ); ?>><?php esc_html_e( 'Angle ( ❯ / 🔽 )', 'tcaccordion' ); ?></option>
			            <option value="caret" <?php selected( $icon_style, 'caret' ); ?>><?php esc_html_e( 'Caret Solid ( ▶ / ▼ )', 'tcaccordion' ); ?></option>
			            <option value="folder" <?php selected( $icon_style, 'folder' ); ?>><?php esc_html_e( 'Folder ( 📁 / 📂 )', 'tcaccordion' ); ?></option>
			            <option value="none" <?php selected( $icon_style, 'none' ); ?>><?php esc_html_e( 'None (No Icon)', 'tcaccordion' ); ?></option>
			        </select>
			        <p class="description">
			            <?php esc_html_e( 'Select the icon style for accordion open/close indicators.', 'tcaccordion' ); ?>
			        </p>
			    </td>
			</tr>

			<!-- Open Trigger Event -->
			<tr valign="top">
			    <th scope="row">
			        <label for="_tcacc_open_event"><?php esc_html_e( 'Open Event', 'tcaccordion' ); ?>
			            <?php if ( ! $is_pro_active ) : ?>
			                <span class="tcacc-pro-badge" style="color: #d63638; font-size: 11px; font-weight: 600; margin-left: 4px;"><?php esc_html_e( '(PRO)', 'tcaccordion' ); ?></span>
			            <?php endif; ?>
			        </label>
			    </th>
			    <td>
			        <select name="_tcacc_open_event" id="_tcacc_open_event" <?php disabled( ! $is_pro_active ); ?>>
			            <option value="click" <?php selected( $open_event, 'click' ); ?>><?php esc_html_e( 'On Click', 'tcaccordion' ); ?></option>
			            <option value="hover" <?php selected( $open_event, 'hover' ); ?>><?php esc_html_e( 'On Mouse Hover', 'tcaccordion' ); ?></option>
			        </select>
			        <p class="description"><?php esc_html_e( 'Select hover triggers.', 'tcaccordion' ); ?></p>
			    </td>
			</tr>

			<!-- Animation Speed Slider -->
			<tr valign="top">
			    <th scope="row">
			        <label for="_tcacc_anim_speed"><?php esc_html_e( 'Animation Speed (ms)', 'tcaccordion' ); ?>
			            <?php if ( ! $is_pro_active ) : ?>
			                <span class="tcacc-pro-badge" style="color: #d63638; font-size: 11px; font-weight: 600; margin-left: 4px;"><?php esc_html_e( '(PRO)', 'tcaccordion' ); ?></span>
			            <?php endif; ?>
			        </label>
			    </th>
			    <td>
			        <input type="range" name="_tcacc_anim_speed" id="_tcacc_anim_speed" min="100" max="1000" step="50" value="<?php echo esc_attr( $anim_speed ); ?>" <?php disabled( ! $is_pro_active ); ?> oninput="this.nextElementSibling.value = this.value + 'ms'">
			        <output><?php echo esc_html( $anim_speed ); ?>ms</output>
			        <p class="description">
			            <?php esc_html_e( 'Customize accordion slide animation speeds.', 'tcaccordion' ); ?>
			        </p>
			    </td>
			</tr>

			<!-- Title BG Color -->
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
				<td><input type="number" name="custom_accordion_title_font_size" id="custom_accordion_title_font_size" value="<?php echo ! empty( $custom_accordion_title_font_size ) ? esc_attr( $custom_accordion_title_font_size ) : '18'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_title_line_height"><?php esc_html_e( 'Title Line Height', 'tcaccordion' ); ?></label></th>
				<td><input type="number" step="0.1" name="custom_accordion_title_line_height" id="custom_accordion_title_line_height" min="0.8" max="3" value="<?php echo ! empty( $custom_accordion_title_line_height ) ? esc_attr( $custom_accordion_title_line_height ) : '1.4'; ?>"> (e.g. 1.4)</td>
			</tr>

<tr valign="top">
    <th scope="row">
        <label><?php esc_html_e( 'Title Text Position', 'tcaccordion' ); ?></label>
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
				<td><input type="number" name="custom_accordion_content_font_size" id="custom_accordion_content_font_size" value="<?php echo ! empty( $custom_accordion_content_font_size ) ? esc_attr( $custom_accordion_content_font_size ) : '16'; ?>"> px</td>
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
				<td><input type="number" name="_tpaccpro_wiki_acc_theme_content_margin" id="_tpaccpro_wiki_acc_theme_content_margin" value="<?php echo ! empty( $_tpaccpro_wiki_acc_theme_content_margin ) ? esc_attr( $_tpaccpro_wiki_acc_theme_content_margin ) : '5'; ?>"> px</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="custom_accordion_content_padding"><?php esc_html_e( 'Content Padding', 'tcaccordion' ); ?></label></th>
				<td><input type="number" name="custom_accordion_content_padding" id="custom_accordion_content_padding" value="<?php echo ! empty( $custom_accordion_content_padding ) ? esc_attr( $custom_accordion_content_padding ) : '12'; ?>"> px</td>
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

if ( isset( $_POST['_tcacc_icon_style'] ) ) {
    $icon_style = sanitize_text_field( wp_unslash( $_POST['_tcacc_icon_style'] ) );
    $allowed_styles = array( 'plus_minus', 'chevron', 'angle', 'caret', 'folder', 'none' );
    $icon_style = in_array( $icon_style, $allowed_styles, true ) ? $icon_style : 'plus_minus';
    update_post_meta( $post_id, '_tcacc_icon_style', $icon_style );
}

		// Only process and update Pro fields if user has an active Pro license
		// 4. Save Pro Settings (Only if Pro is active)
	    if ( function_exists( 'tc_acc_is_pro' ) && tc_acc_is_pro() ) {

	        // Auto Open Item (Sanitize Integer)
	        if ( isset( $_POST['_tcacc_auto_open_item'] ) ) {
	            $auto_open = absint( $_POST['_tcacc_auto_open_item'] );
	            update_post_meta( $post_id, '_tcacc_auto_open_item', $auto_open );
	        }

	        // Accordion Closeable (Sanitize Enum)
	        if ( isset( $_POST['_tcacc_is_closeable'] ) ) {
	            $closeable = sanitize_text_field( wp_unslash( $_POST['_tcacc_is_closeable'] ) );
	            $closeable = in_array( $closeable, array( 'yes', 'no' ), true ) ? $closeable : 'yes';
	            update_post_meta( $post_id, '_tcacc_is_closeable', $closeable );
	        }

	        // Close Other Items (Sanitize Enum)
	        if ( isset( $_POST['_tcacc_close_others'] ) ) {
	            $close_others = sanitize_text_field( wp_unslash( $_POST['_tcacc_close_others'] ) );
	            $close_others = in_array( $close_others, array( 'yes', 'no' ), true ) ? $close_others : 'yes';
	            update_post_meta( $post_id, '_tcacc_close_others', $close_others );
	        }

			if ( isset( $_POST['_tcacc_open_event'] ) ) {
			    $event = sanitize_text_field( wp_unslash( $_POST['_tcacc_open_event'] ) );
			    update_post_meta( $post_id, '_tcacc_open_event', in_array( $event, array( 'click', 'hover' ), true ) ? $event : 'click' );
			}

			if ( isset( $_POST['_tcacc_anim_speed'] ) ) {
			    update_post_meta( $post_id, '_tcacc_anim_speed', absint( $_POST['_tcacc_anim_speed'] ) );
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
        }
    }

}

new TCAccordion_Metabox();