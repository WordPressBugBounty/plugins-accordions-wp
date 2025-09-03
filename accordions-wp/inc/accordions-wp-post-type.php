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
			'menu_icon' 			=> TCACCORDION_PLUGIN_URL.'css/accordion.png',
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
			add_meta_box('accordion_sectionid', __( 'Accordion Settings','tcaccordion' ),'custom_accordion_wordpress_inner_custom_box', $screen);
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

		//get the saved meta as an arry
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
		?>

		<div id="tabs-container">
			<ul class="tabs-menu">
				<li class="current"><a href="#tab-1"><?php _e('Accordion Settings', 'tcaccordion'); ?></a></li>
			</ul>
			<div class="tab">
				<div id="tab-1" class="tab-content">
					<div class="wrap">
						<table class="form-table">

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_columns_post_themes"><?php _e('Accordion Themes', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<select class="timezone_string" name="custom_accordion_columns_post_themes">
										<option value="theme1" <?php if($custom_accordion_columns_post_themes=='theme1') echo "selected"; ?> ><?php _e('Sun Flower', 'tcaccordion'); ?></option>
										<option value="theme2" <?php if($custom_accordion_columns_post_themes=='theme2') echo "selected"; ?> ><?php _e('Orange', 'tcaccordion'); ?></option>
										<option value="theme3" <?php if($custom_accordion_columns_post_themes=='theme3') echo "selected"; ?> ><?php _e('Pumkin', 'tcaccordion'); ?></option>
										<option value="theme4" <?php if($custom_accordion_columns_post_themes=='theme4') echo "selected"; ?> ><?php _e('Alizarin', 'tcaccordion'); ?></option>
										<option value="theme5" <?php if($custom_accordion_columns_post_themes=='theme5') echo "selected"; ?>><?php _e('Carrot', 'tcaccordion'); ?></option>
									</select><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Choose Your Accordion Themes.', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_title_bg_color"><?php _e('Title BG Color', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<input  size='7' name='custom_accordion_title_bg_color' class='custom-accordion-columns-bg-color' id="custom-accordion-columns-bg-color" type='text' value='<?php echo sanitize_text_field($custom_accordion_title_bg_color) ?>' />
									<br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Title Background Color.', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_title_font_color"><?php _e('Title Font Color', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<input  size='7' name='custom_accordion_title_font_color' class='custom-accordion-title-font-color' id="custom-accordion-title-font-color" type='text' value='<?php echo sanitize_text_field($custom_accordion_title_font_color) ?>' />
									<br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Title Font Color.', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_title_font_size"><?php _e('Title Font Size', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<input type="number" name="custom_accordion_title_font_size" id="custom_accordion_title_font_size" min="10" max="45" class="timezone_string" value="<?php  if($custom_accordion_title_font_size !=''){echo $custom_accordion_title_font_size; }else{ echo '15';} ?>"><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Title Font Size. default font size:14px', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="_tpaccpro_wiki_acc_themes_title_position"><?php _e('Title Text Position', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align: middle;">
									<select name="_tpaccpro_wiki_acc_themes_title_position" id="_tpaccpro_wiki_acc_themes_title_position" class="timezone_string">
										<option value="left" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_title_position ) ) selected( $_tpaccpro_wiki_acc_themes_title_position, 'left' ); ?>><?php _e('Left', 'tcaccordion'); ?></option>
										<option value="center" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_title_position ) ) selected( $_tpaccpro_wiki_acc_themes_title_position, 'center' ); ?>><?php _e('Center', 'tcaccordion'); ?></option>
										<option value="right" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_title_position ) ) selected( $_tpaccpro_wiki_acc_themes_title_position, 'right' ); ?>><?php _e('Right', 'tcaccordion'); ?></option>
									</select><br>
									<span class="tp_accordions_pro_hint"><?php echo __('Select your title text position (Only Pro).', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="_tpaccpro_wiki_acc_themes_show_hide_icons"><?php _e('Shwo/Hide Icon', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align: middle;">
									<select name="_tpaccpro_wiki_acc_themes_show_hide_icons" id="_tpaccpro_wiki_acc_themes_show_hide_icons" class="timezone_string">
										<option value="1" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_show_hide_icons ) ) selected( $_tpaccpro_wiki_acc_themes_show_hide_icons, '1' ); ?>><?php _e('Show', 'tcaccordion'); ?></option>
										<option value="2" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_show_hide_icons ) ) selected( $_tpaccpro_wiki_acc_themes_show_hide_icons, '2' ); ?>><?php _e('Hide', 'tcaccordion'); ?></option>
									</select><br>
									<span class="tp_accordions_pro_hint"><?php echo __('show or hide your accordion icon (Only Pro).', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="_tpaccpro_wiki_acc_themes_icon_position"><?php _e('Icon Position', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align: middle;">
									<select name="_tpaccpro_wiki_acc_themes_icon_position" id="_tpaccpro_wiki_acc_themes_icon_position" class="timezone_string">
										<option value="1" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_icon_position ) ) selected( $_tpaccpro_wiki_acc_themes_icon_position, '1' ); ?>><?php _e('Left', 'tcaccordion'); ?></option>
										<option value="2" <?php if ( isset ( $_tpaccpro_wiki_acc_themes_icon_position ) ) selected( $_tpaccpro_wiki_acc_themes_icon_position, '2' ); ?>><?php _e('Right', 'tcaccordion'); ?></option>
									</select><br>
									<span class="tp_accordions_pro_hint"><?php echo __('Choose accordion icon position Left or Right (Only Pro).', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_content_bg_color"><?php _e('Content BG Color', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<input  size='7' name='custom_accordion_content_bg_color' class='custom-accordion-content-bg-color' id="custom-accordion-content-bg-color" type='text' value='<?php echo sanitize_text_field($custom_accordion_content_bg_color) ?>' />
									<br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Content Background Color.', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_content_font_color"><?php _e('Content Font Color', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<input  size='7' name='custom_accordion_content_font_color' class='custom-accordion-content-font-color' id="custom-accordion-content-font-color" type='text' value='<?php echo sanitize_text_field($custom_accordion_content_font_color) ?>' />
									<br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Content Font Color.', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_content_font_size"><?php _e('Content Font Size', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<input type="number" name="custom_accordion_content_font_size" id="custom_accordion_content_font_size" min="10" max="45" class="timezone_string" value="<?php  if($custom_accordion_content_font_size !=''){echo $custom_accordion_content_font_size; }else{ echo '14';} ?>"><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Content Font Size. default font size:15px', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="_tpaccpro_wiki_acc_theme_content_margin"><?php _e('Margin Between Accordion', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<input type="number" name="_tpaccpro_wiki_acc_theme_content_margin" id="_tpaccpro_wiki_acc_theme_content_margin" min="5" max="45" class="timezone_string" value="<?php  if($_tpaccpro_wiki_acc_theme_content_margin !=''){echo $_tpaccpro_wiki_acc_theme_content_margin; }else{ echo '5';} ?>"><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Choose Accordion Item Margin Bottom. default 5 px  (Only Pro)', 'tcaccordion'); ?></span>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									<label for="custom_accordion_content_padding"><?php _e('Content Padding', 'tcaccordion'); ?></label>
								</th>
								<td style="vertical-align:middle;">
									<input type="number" name="custom_accordion_content_padding" id="custom_accordion_content_padding" min="10" max="45" class="timezone_string" value="<?php  if($custom_accordion_content_padding !=''){echo $custom_accordion_content_padding; }else{ echo '12';} ?>"><br/>
									<span class="tp_accordions_pro_hint"><?php _e('Select Accordion Content Padding. default 12 px', 'tcaccordion'); ?></span>
								</td>
							</tr>

						</table>
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

	    // Check if it's an autosave
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	        return $post_id;
	    }

	    // Check if our nonce is set.
	    if ( ! isset( $_POST['custom_accordion_wordpress_dynamicMeta_noncename'] ) ) {
	        return $post_id;
	    }

	    // Verify that the nonce is valid.
	    if ( ! wp_verify_nonce( $_POST['custom_accordion_wordpress_dynamicMeta_noncename'], plugin_basename( __FILE__ ) ) ) {
	        return $post_id;
	    }

		// OK, we're authenticated: we need to find and save the data

		// Checks for input and sanitizes/saves if needed    
		if( isset($_POST['custom_accordion_columns_post_themes']) && !empty($_POST['custom_accordion_columns_post_themes']) ) {
		    $custom_accordion_columns_post_themes = sanitize_text_field( $_POST['custom_accordion_columns_post_themes'] );
		    update_post_meta( $post_id, 'custom_accordion_columns_post_themes', $custom_accordion_columns_post_themes );
		}

		// Checks for input and sanitizes/saves if needed    
		if( isset($_POST['custom_accordion_title_font_size']) && !empty($_POST['custom_accordion_title_font_size']) ) {
		    $custom_accordion_title_font_size = sanitize_text_field( $_POST['custom_accordion_title_font_size'] );
		    update_post_meta( $post_id, 'custom_accordion_title_font_size', $custom_accordion_title_font_size );
		}

		// Checks for input and sanitizes/saves if needed    
		if( isset($_POST['custom_accordion_content_font_size']) && !empty($_POST['custom_accordion_content_font_size']) ) {
		    $custom_accordion_content_font_size = sanitize_text_field( $_POST['custom_accordion_content_font_size'] );
		    update_post_meta( $post_id, 'custom_accordion_content_font_size', $custom_accordion_content_font_size );
		}

		// Checks for input and sanitizes/saves if needed    
		if( isset($_POST['custom_accordion_content_padding']) && !empty($_POST['custom_accordion_content_padding']) ) {
		    $custom_accordion_content_padding = sanitize_text_field( $_POST['custom_accordion_content_padding'] );
		    update_post_meta( $post_id, 'custom_accordion_content_padding', $custom_accordion_content_padding );
		}

		// Checks for input and sanitizes/saves if needed    
		if( isset($_POST['_tpaccpro_wiki_acc_themes_title_position']) && !empty($_POST['_tpaccpro_wiki_acc_themes_title_position']) ) {
		    $_tpaccpro_wiki_acc_themes_title_position = sanitize_text_field( $_POST['_tpaccpro_wiki_acc_themes_title_position'] );
		    update_post_meta( $post_id, '_tpaccpro_wiki_acc_themes_title_position', $_tpaccpro_wiki_acc_themes_title_position );
		}

		// Checks for input and sanitizes/saves if needed    
		if( isset($_POST['_tpaccpro_wiki_acc_themes_show_hide_icons']) && !empty($_POST['_tpaccpro_wiki_acc_themes_show_hide_icons']) ) {
		    $_tpaccpro_wiki_acc_themes_show_hide_icons = sanitize_text_field( $_POST['_tpaccpro_wiki_acc_themes_show_hide_icons'] );
		    update_post_meta( $post_id, '_tpaccpro_wiki_acc_themes_show_hide_icons', $_tpaccpro_wiki_acc_themes_show_hide_icons );
		}

		// Checks for input and sanitizes/saves if needed    
		if( isset($_POST['_tpaccpro_wiki_acc_themes_icon_position']) && !empty($_POST['_tpaccpro_wiki_acc_themes_icon_position']) ) {
		    $_tpaccpro_wiki_acc_themes_icon_position = sanitize_text_field( $_POST['_tpaccpro_wiki_acc_themes_icon_position'] );
		    update_post_meta( $post_id, '_tpaccpro_wiki_acc_themes_icon_position', $_tpaccpro_wiki_acc_themes_icon_position );
		}

		// Checks for input and sanitizes/saves if needed    
		if( isset($_POST['_tpaccpro_wiki_acc_theme_content_margin']) && !empty($_POST['_tpaccpro_wiki_acc_theme_content_margin']) ) {
		    $_tpaccpro_wiki_acc_theme_content_margin = sanitize_text_field( $_POST['_tpaccpro_wiki_acc_theme_content_margin'] );
		    update_post_meta( $post_id, '_tpaccpro_wiki_acc_theme_content_margin', $_tpaccpro_wiki_acc_theme_content_margin );
		}

		// Checks for input and sanitizes/saves if needed
		if( isset($_POST['custom_accordion_title_bg_color']) && !empty($_POST['custom_accordion_title_bg_color']) ) {
		    $custom_accordion_title_bg_color = sanitize_hex_color( $_POST['custom_accordion_title_bg_color'] );
		    update_post_meta( $post_id, 'custom_accordion_title_bg_color', $custom_accordion_title_bg_color );
		}

		// Checks for input and sanitizes/saves if needed
		if( isset($_POST['custom_accordion_title_font_color']) && !empty($_POST['custom_accordion_title_font_color']) ) {
		    $custom_accordion_title_font_color = sanitize_hex_color( $_POST['custom_accordion_title_font_color'] );
		    update_post_meta( $post_id, 'custom_accordion_title_font_color', $custom_accordion_title_font_color );
		}

		// Checks for input and sanitizes/saves if needed
		if( isset($_POST['custom_accordion_content_bg_color']) && !empty($_POST['custom_accordion_content_bg_color']) ) {
		    $custom_accordion_content_bg_color = sanitize_hex_color( $_POST['custom_accordion_content_bg_color'] );
		    update_post_meta( $post_id, 'custom_accordion_content_bg_color', $custom_accordion_content_bg_color );
		}

		// Checks for input and sanitizes/saves if needed
		if( isset($_POST['custom_accordion_content_font_color']) && !empty($_POST['custom_accordion_content_font_color']) ) {
		    $custom_accordion_content_font_color = sanitize_hex_color( $_POST['custom_accordion_content_font_color'] );
		    update_post_meta( $post_id, 'custom_accordion_content_font_color', $custom_accordion_content_font_color );
		}
	}
	// Do something with the data entered
	add_action( 'save_post', 'custom_accordion_wordpress_save_postdata' );

	# Carousel Manage Shortcode Column 
	function custom_accordion_wordpress_shortcode_column( $rsbboxcolumns ) {
		$order='asc';
		if($_GET['order']=='asc') {
			$order='desc';
		}

		$rsbboxcolumns = array(
			"cb"          => "<input type=\"checkbox\" />",
			"title"       => __('Shortcode Name', 'tcaccordion'),
			"shortcode"   => __('Shortcode', 'tcaccordion'),
			'doshortcode' => __( 'Template Shortcode', 'tcaccordion' ),
			"date"        => __('Date', 'tcaccordion'),
		);
		return $rsbboxcolumns;
	}
	add_filter( 'manage_accordion_tp_posts_columns' , 'custom_accordion_wordpress_shortcode_column' );

	function tp_custom_accordion_posts_shortcode_display( $rsbbox_column, $post_id ) {
		if ( $rsbbox_column == 'shortcode' ){ ?>
			<input style="background:#ddd" type="text" onClick="this.select();" value="[tcpaccordion <?php echo 'id=&quot;'.$post_id.'&quot;';?>]" />
			<?php 
		}
	 	if ( $rsbbox_column == 'doshortcode' ){ ?>
			<textarea cols="40" rows="2" style="background:#ddd;" onClick="this.select();" ><?php echo '<?php echo do_shortcode("[tcpaccordion id='; echo "'".$post_id."']"; echo '"); ?>'; ?></textarea>
			<?php
	 	}
	}
	add_action( 'manage_accordion_tp_posts_custom_column' , 'tp_custom_accordion_posts_shortcode_display', 10, 2 );

	function accordion_wp_shortcode_section($post) {
	    // Show only for 'accordion_tp' post type
	    if ($post->post_type !== 'accordion_tp') {
	        return;
	    }

	    // Generate the dynamic shortcode
	    $shortcode = "[tcpaccordion id='" . $post->ID . "']";
	    $php_code = '<?php echo do_shortcode("[tcpaccordion id=' . $post->ID . ']"); ?>';

	    ?>
	    <div style="padding: 15px 15px 25px 15px; border: 1px solid #ddd; background: #f9f9f9; margin-top: 15px;">
		    <div style="display: flex; gap: 20px;">
			    <div style="width: 50%;">
			        <p>
			            <strong><?php _e( 'Shortcode','tcaccordion' ); ?>:</strong>
			            <span id="shortcode-notice" style="color: green; display: none; margin-left: 10px;"><?php _e( 'Shortcode copied!','tcaccordion' ); ?></span>
			        </p>
			        <p class="option-info"><?php _e('Click to copy the shortcode and paste it into a page or post to display Accordion.','tcaccordion' ); ?></p>
			        <input type="text" id="shortcode-text" style="width:100%; cursor:pointer; box-shadow: none; border:none;outline:none;border-radius: 0" value="<?php echo esc_attr($shortcode); ?>" readonly onclick="copyToClipboard(this, 'shortcode-notice')">
			    </div>
			    <div style="width: 50%;">
			        <p>
			            <strong><?php _e( 'PHP Code for Theme Files','tcaccordion' ); ?>:</strong>
			            <span id="php-notice" style="color: green; display: none; margin-left: 10px;"><?php _e( 'PHP code copied!','tcaccordion' ); ?></span>
			        </p>
			        <p class="option-info"><?php _e('Click to copy the PHP code and use it in your theme files to display Accordion.','tcaccordion' ); ?></p>
			        <input type="text" id="php-code-text" style="width:100%; cursor:pointer; box-shadow: none; border:none;outline:none;border-radius: 0" value="<?php echo esc_attr($php_code); ?>" readonly onclick="copyToClipboard(this, 'php-notice')">
			    </div>
		    </div>
	    </div>
	    <script>
	        function copyToClipboard(inputField, noticeId) {
	            inputField.select();
	            navigator.clipboard.writeText(inputField.value);

	            // Show copied message beside the label
	            var notice = document.getElementById(noticeId);
	            notice.style.display = "inline";

	            // Hide the message after 2 seconds
	            setTimeout(function() {
	                notice.style.display = "none";
	            }, 2000);
	        }
	    </script>
	    <?php
	}
	add_action('edit_form_after_title', 'accordion_wp_shortcode_section');