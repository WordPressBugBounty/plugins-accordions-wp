<?php

    if( !defined( 'ABSPATH' ) ){
        exit;
    }

	// Register Custom Post Type
	function custom_accordion_post_register() {
		$labels = array(
			'name'                  => _x( 'Accordions', 'Post Type General Name', 'tcaccordion' ),
			'singular_name'         => _x( 'Accordion', 'Post Type Singular Name', 'tcaccordion' ),
			'menu_name'             => __( 'Accordion', 'tcaccordion' ),
			'name_admin_bar'        => __( 'Accordion', 'tcaccordion' ),
			'archives'              => __( 'Item Archives', 'tcaccordion' ),
			'attributes'            => __( 'Item Attributes', 'tcaccordion' ),
			'parent_item_colon'     => __( 'Parent Item:', 'tcaccordion' ),
			'all_items'             => __( 'All Accordions', 'tcaccordion' ),
			'add_new_item'          => __( 'Add New Accordion', 'tcaccordion' ),
			'add_new'               => __( 'Add New Accordion', 'tcaccordion' ),
			'new_item'              => __( 'New Item Accordion', 'tcaccordion' ),
			'edit_item'             => __( 'Edit Accordion', 'tcaccordion' ),
			'update_item'           => __( 'Update Accordion', 'tcaccordion' ),
			'view_item'             => __( 'View Accordion', 'tcaccordion' ),
			'view_items'            => __( 'View Accordions', 'tcaccordion' ),
			'search_items'          => __( 'Search Accordion', 'tcaccordion' ),
			'not_found'             => __( 'Accordion Not found', 'tcaccordion' ),
			'not_found_in_trash'    => __( 'Accordion Not found in Trash', 'tcaccordion' ),
			'featured_image'        => __( '', 'tcaccordion' ),
			'set_featured_image'    => __( 'Set featured image', 'tcaccordion' ),
			'remove_featured_image' => __( 'Remove featured image', 'tcaccordion' ),
			'use_featured_image'    => __( 'Use as featured image', 'tcaccordion' ),
			'insert_into_item'      => __( 'Insert into item', 'tcaccordion' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'tcaccordion' ),
			'items_list'            => __( 'Items list', 'tcaccordion' ),
			'items_list_navigation' => __( 'Items list navigation', 'tcaccordion' ),
			'filter_items_list'     => __( 'Filter items list', 'tcaccordion' ),
		);
		$args = array(
			'label'                 => __( 'Accordion', 'tcaccordion' ),
			'description'           => __( 'Accordion Post Type Description', 'tcaccordion' ),
			'labels'                => $labels,
			'supports'              => array( 'title'),
			'menu_icon' 			=> CUSTOM_ACCORDION_PLUGIN_PATH.'css/accordion.png',
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
		);
		register_post_type( 'accordion_tp', $args );
	}
	add_action( 'init', 'custom_accordion_post_register', 0 );


	/*==========================================================================
		Adds a box to the main column on the Post and Page edit screens
	==========================================================================*/

	function custom_accordion_wordpress_add_custom_box() {
		$screens = array( 'accordion_tp' );
		foreach ( $screens as $screen ){
			add_meta_box('accordion_sectionid', __( 'Accordion Configure','tcaccordion' ),'custom_accordion_wordpress_inner_custom_box', $screen);
		}     
	}
	add_action( 'add_meta_boxes', 'custom_accordion_wordpress_add_custom_box' );

	/*==========================================================================
		Prints the box content 
	==========================================================================*/

	function custom_accordion_wordpress_inner_custom_box() {
		global $post;
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'custom_accordion_wordpress_dynamicMeta_noncename' );
		?>
		<?php

		//get the saved meta as an arry
		
		$custom_accordion_columns_post_themes 			= get_post_meta( $post->ID, 'custom_accordion_columns_post_themes', true );
		$custom_accordion_title_bg_color 				= get_post_meta( $post->ID, 'custom_accordion_title_bg_color', true );
		$custom_accordion_title_font_color 				= get_post_meta( $post->ID, 'custom_accordion_title_font_color', true );
		$custom_accordion_title_font_size 				= get_post_meta( $post->ID, 'custom_accordion_title_font_size', true );
		$custom_accordion_content_bg_color 				= get_post_meta( $post->ID, 'custom_accordion_content_bg_color', true );
		$custom_accordion_content_font_color 			= get_post_meta( $post->ID, 'custom_accordion_content_font_color', true );
		$custom_accordion_content_font_size 			= get_post_meta( $post->ID, 'custom_accordion_content_font_size', true );
		$custom_accordion_content_padding 				= get_post_meta( $post->ID, 'custom_accordion_content_padding', true );		
		$_tpaccpro_wiki_acc_themes_title_position 		= get_post_meta( $post->ID, '_tpaccpro_wiki_acc_themes_title_position', true );
		$_tpaccpro_wiki_acc_themes_show_hide_icons 		= get_post_meta( $post->ID, '_tpaccpro_wiki_acc_themes_show_hide_icons', true );
		$_tpaccpro_wiki_acc_themes_icon_position 		= get_post_meta( $post->ID, '_tpaccpro_wiki_acc_themes_icon_position', true );
		$_tpaccpro_wiki_acc_theme_content_margin 		= get_post_meta( $post->ID, '_tpaccpro_wiki_acc_theme_content_margin', true );		
		?>

		<div id="tabs-container">
			<ul class="tabs-menu">
				<li class="current"><a href="#tab-1"><?php _e('Accordion Settings', 'tcaccordion')?></a></li>
				<li><a href="#tab-2"><?php _e('Accordion Shortcode', 'tcaccordion')?></a></li>
			</ul>	
		
			<div class="tab">
				<div id="tab-1" class="tab-content">
					<div class="wrap">				
						<table class="form-table">

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_columns_post_themes"><?php _e('Accordion Themes', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<select class="timezone_string" name="custom_accordion_columns_post_themes">
										<option value="theme1" <?php if($custom_accordion_columns_post_themes=='theme1') echo "selected"; ?> ><?php _e('Sun Flower', 'tcaccordion')?></option>
										<option value="theme2" <?php if($custom_accordion_columns_post_themes=='theme2') echo "selected"; ?> ><?php _e('Orange', 'tcaccordion')?></option>
										<option value="theme3" <?php if($custom_accordion_columns_post_themes=='theme3') echo "selected"; ?> ><?php _e('Pumkin', 'tcaccordion')?></option>
										<option value="theme4" <?php if($custom_accordion_columns_post_themes=='theme4') echo "selected"; ?> ><?php _e('Alizarin', 'tcaccordion')?></option>
										<option value="theme5" <?php if($custom_accordion_columns_post_themes=='theme5') echo "selected"; ?>><?php _e('Carrot', 'tcaccordion')?></option>
									</select><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Choose Your Accordion Themes.', 'tcaccordion')?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_title_bg_color"><?php _e('Title BG Color', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<input  size='7' name='custom_accordion_title_bg_color' class='custom-accordion-columns-bg-color' id="custom-accordion-columns-bg-color" type='text' value='<?php echo sanitize_text_field($custom_accordion_title_bg_color) ?>' />
									<br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Title Background Color.', 'tcaccordion')?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_title_font_color"><?php _e('Title Font Color', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<input  size='7' name='custom_accordion_title_font_color' class='custom-accordion-title-font-color' id="custom-accordion-title-font-color" type='text' value='<?php echo sanitize_text_field($custom_accordion_title_font_color) ?>' />
									<br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Title Font Color.', 'tcaccordion')?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_title_font_size"><?php _e('Title Font Size', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<input type="number" name="custom_accordion_title_font_size" id="custom_accordion_title_font_size" min="10" max="45" class="timezone_string" value="<?php  if($custom_accordion_title_font_size !=''){echo $custom_accordion_title_font_size; }else{ echo '15';} ?>"><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Title Font Size. default font size:14px', 'tcaccordion')?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="_tpaccpro_wiki_acc_themes_title_position"><?php _e('Title Text Position', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align: middle;">
									<select name="_tpaccpro_wiki_acc_themes_title_position" id="_tpaccpro_wiki_acc_themes_title_position" class="timezone_string">
										<option value="left" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_title_position ) ) selected( $_tpaccpro_wiki_acc_themes_title_position, 'left' ); ?>><?php _e('Left', 'tcaccordion')?></option>
										<option value="center" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_title_position ) ) selected( $_tpaccpro_wiki_acc_themes_title_position, 'center' ); ?>><?php _e('Center', 'tcaccordion')?></option>
										<option value="right" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_title_position ) ) selected( $_tpaccpro_wiki_acc_themes_title_position, 'right' ); ?>><?php _e('Right', 'tcaccordion')?></option>
									</select><br>
									<span class="tp_accordions_pro_hint"><?php echo __('Select your title text position (Only Pro).', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="_tpaccpro_wiki_acc_themes_show_hide_icons"><?php _e('Shwo/Hide Icon', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align: middle;">
									<select name="_tpaccpro_wiki_acc_themes_show_hide_icons" id="_tpaccpro_wiki_acc_themes_show_hide_icons" class="timezone_string">
										<option value="1" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_show_hide_icons ) ) selected( $_tpaccpro_wiki_acc_themes_show_hide_icons, '1' ); ?>><?php _e('Show', 'tcaccordion')?></option>
										<option value="2" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_show_hide_icons ) ) selected( $_tpaccpro_wiki_acc_themes_show_hide_icons, '2' ); ?>><?php _e('Hide', 'tcaccordion')?></option>
									</select><br>
									<span class="tp_accordions_pro_hint"><?php echo __('show or hide your accordion icon (Only Pro).', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="_tpaccpro_wiki_acc_themes_icon_position"><?php _e('Icon Position', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align: middle;">
									<select name="_tpaccpro_wiki_acc_themes_icon_position" id="_tpaccpro_wiki_acc_themes_icon_position" class="timezone_string">
										<option value="1" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_icon_position ) ) selected( $_tpaccpro_wiki_acc_themes_icon_position, '1' ); ?>><?php _e('Left', 'tcaccordion')?></option>
										<option value="2" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_icon_position ) ) selected( $_tpaccpro_wiki_acc_themes_icon_position, '2' ); ?>><?php _e('Right', 'tcaccordion')?></option>
									</select><br>
									<span class="tp_accordions_pro_hint"><?php echo __('Choose accordion icon position Left or Right (Only Pro).', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_content_bg_color"><?php _e('Content BG Color', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<input  size='7' name='custom_accordion_content_bg_color' class='custom-accordion-content-bg-color' id="custom-accordion-content-bg-color" type='text' value='<?php echo sanitize_text_field($custom_accordion_content_bg_color) ?>' />
									<br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Content Background Color.', 'tcaccordion')?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_content_font_color"><?php _e('Content Font Color', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<input  size='7' name='custom_accordion_content_font_color' class='custom-accordion-content-font-color' id="custom-accordion-content-font-color" type='text' value='<?php echo sanitize_text_field($custom_accordion_content_font_color) ?>' />
									<br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Content Font Color.', 'tcaccordion')?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_content_font_size"><?php _e('Content Font Size', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<input type="number" name="custom_accordion_content_font_size" id="custom_accordion_content_font_size" min="10" max="45" class="timezone_string" value="<?php  if($custom_accordion_content_font_size !=''){echo $custom_accordion_content_font_size; }else{ echo '14';} ?>"><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Content Font Size. default font size:15px', 'tcaccordion')?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="_tpaccpro_wiki_acc_theme_content_margin"><?php _e('Margin Between Accordion', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<input type="number" name="_tpaccpro_wiki_acc_theme_content_margin" id="_tpaccpro_wiki_acc_theme_content_margin" min="5" max="45" class="timezone_string" value="<?php  if($_tpaccpro_wiki_acc_theme_content_margin !=''){echo $_tpaccpro_wiki_acc_theme_content_margin; }else{ echo '5';} ?>"><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Choose Accordion Item Margin Bottom. default 5 px  (Only Pro)', 'tcaccordion')?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_content_padding"><?php _e('Content Padding', 'tcaccordion')?></label>
								</th>
								<td style="vertical-align:middle;">
									<input type="number" name="custom_accordion_content_padding" id="custom_accordion_content_padding" min="10" max="45" class="timezone_string" value="<?php  if($custom_accordion_content_padding !=''){echo $custom_accordion_content_padding; }else{ echo '12';} ?>"><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Content Padding. default 12 px', 'tcaccordion')?></span>
								</td>
							</tr>

						</table>
					</div>
				</div>
				<div id="tab-2" class="tab-content">
					<div id="meta_inner">
						<div class="tp-accordions-pro-shortcodes">
							<h2><?php _e('Shortcodes', 'tcaccordion');?></h2>
							<p><?php _e('Use following shortcode to display the Accordion anywhere:', 'tcaccordion');?></p>
							<textarea cols="30" rows="1" onClick="this.select();">[tcpaccordion <?php echo 'id="'.$post->ID.'"';?>]</textarea>
							<p><?php _e('If you need to put the shortcode in theme file use this:', 'tcaccordion');?></p>            
							<textarea cols="54" rows="1" onClick="this.select();"><?php echo '<?php echo do_shortcode("[tcpaccordion id='; echo "'".$post->ID."']"; echo '");?>';?></textarea>
						</div>
					</div>
				</div>
			</div>
		</div>	
	<?php
	}
	
	/*==========================================================================
		When the post is saved, saves our custom data
	==========================================================================*/	

	function custom_accordion_wordpress_save_postdata( $post_id ) {
		// verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !isset( $_POST['custom_accordion_wordpress_dynamicMeta_noncename'] ) )
			return;

		if ( !wp_verify_nonce( $_POST['custom_accordion_wordpress_dynamicMeta_noncename'], plugin_basename( __FILE__ ) ) )
			return;

		// OK, we're authenticated: we need to find and save the data

		$custom_accordion_columns_post_themes 	= sanitize_text_field( $_POST['custom_accordion_columns_post_themes'] );
		$custom_accordion_title_bg_color 		= sanitize_text_field( $_POST['custom_accordion_title_bg_color'] );
		$custom_accordion_title_font_color 		= sanitize_text_field( $_POST['custom_accordion_title_font_color'] );
		$custom_accordion_title_font_size 		= sanitize_text_field( $_POST['custom_accordion_title_font_size'] );
		$custom_accordion_content_bg_color 		= sanitize_text_field( $_POST['custom_accordion_content_bg_color'] );
		$custom_accordion_content_font_color 	= sanitize_text_field( $_POST['custom_accordion_content_font_color'] );
		$custom_accordion_content_font_size 	= sanitize_text_field( $_POST['custom_accordion_content_font_size'] );
		$custom_accordion_content_padding 		= sanitize_text_field( $_POST['custom_accordion_content_padding'] );

		update_post_meta( $post_id, 'custom_accordion_columns_post_themes', $custom_accordion_columns_post_themes );		
		update_post_meta( $post_id, 'custom_accordion_title_bg_color', $custom_accordion_title_bg_color );
		update_post_meta( $post_id, 'custom_accordion_title_font_color', $custom_accordion_title_font_color );
		update_post_meta( $post_id, 'custom_accordion_title_font_size', $custom_accordion_title_font_size );
		update_post_meta( $post_id, 'custom_accordion_content_bg_color', $custom_accordion_content_bg_color );
		update_post_meta( $post_id, 'custom_accordion_content_font_color', $custom_accordion_content_font_color );
		update_post_meta( $post_id, 'custom_accordion_content_font_size', $custom_accordion_content_font_size );
		update_post_meta( $post_id, 'custom_accordion_content_padding', $custom_accordion_content_padding );


		#Checks for input and saves if needed
		if(isset($_POST['_tpaccpro_wiki_acc_themes_title_position'])) {
			update_post_meta($post_id, '_tpaccpro_wiki_acc_themes_title_position', $_POST['_tpaccpro_wiki_acc_themes_title_position']);
		}

		#Checks for input and saves if needed
		if(isset($_POST['_tpaccpro_wiki_acc_themes_show_hide_icons'])) {
			update_post_meta($post_id, '_tpaccpro_wiki_acc_themes_show_hide_icons', $_POST['_tpaccpro_wiki_acc_themes_show_hide_icons']);
		}

		#Checks for input and saves if needed
		if(isset($_POST['_tpaccpro_wiki_acc_themes_icon_position'])) {
			update_post_meta($post_id, '_tpaccpro_wiki_acc_themes_icon_position', $_POST['_tpaccpro_wiki_acc_themes_icon_position']);
		}

		#Checks for input and saves if needed
		if(isset($_POST['_tpaccpro_wiki_acc_theme_content_margin'])) {
			update_post_meta($post_id, '_tpaccpro_wiki_acc_theme_content_margin', $_POST['_tpaccpro_wiki_acc_theme_content_margin']);
		}
	}
	// Do something with the data entered
	add_action( 'save_post', 'custom_accordion_wordpress_save_postdata' );

?>