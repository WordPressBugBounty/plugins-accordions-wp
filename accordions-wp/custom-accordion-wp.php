<?php
	/*
		Plugin Name: Accordion-Wp
		Plugin URI: https://themepoints.com/product/wp-accordions-pro
		Description: Wp Accordions is a component ready to use on mobile devices and desktop devices. It’s a fluid component and easy to use. It provides various skins, options and features for data organization and it comes with many different styles.
		Version: 2.8
		Author: Themepoints
		Author URI: https://themepoints.com
		TextDomain: tcaccordion
		License: GPLv2
	*/


	if ( ! defined( 'ABSPATH' ) )
	die( "Can't load this file directly" );

	/***************************************
	wp accordion plugins path register
	***************************************/

	define('CUSTOM_ACCORDION_PLUGIN_PATH', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/' );

	# Include Meta Box Class File
	include( plugin_dir_path( __FILE__ ) . 'metabox/custom-meta-boxes.php' );
	include( plugin_dir_path( __FILE__ ) . 'inc/accordions-wp-post-type.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'theme/custom-wp-accordion-themes.php');

	# wp accordion admin enqueue scripts
	function custom_accordion_active_script(){
		wp_enqueue_script('jquery');
		wp_register_script('accordion-responsive-js', plugins_url( '/js/responsive-accordion.min.js', __FILE__ ), array('jquery'), '1.0', false);
		wp_register_style('accordion-responsive-css', CUSTOM_ACCORDION_PLUGIN_PATH.'css/responsive-accordion.css');
		wp_register_style('accordion-main-css', CUSTOM_ACCORDION_PLUGIN_PATH.'css/style.css');
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('accordion-wp-color-picker', plugins_url(), array( 'wp-color-picker' ), false, true );
	}
	add_action('init', 'custom_accordion_active_script');
	
	# Wp Accordion Admin enqueue Scripts
	function custom_accordion_admin_enqueue_scripts(){
		global $typenow;
		if(($typenow == 'accordion_tp')){
			wp_enqueue_script('jquery');
			wp_enqueue_style('accordion-admin-css', CUSTOM_ACCORDION_PLUGIN_PATH.'admin/css/accordion-backend-admin.css');
			wp_enqueue_script('accordion-admin-js', CUSTOM_ACCORDION_PLUGIN_PATH.'admin/js/accordion-backend-admin.js', array('jquery'), '1.0.0', true );

			wp_enqueue_style('wp-color-picker');
			wp_enqueue_script( 'accordion_color_picker', plugins_url('admin/js/color-picker.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
			wp_enqueue_script("jquery-ui-sortable");
			wp_enqueue_script("jquery-ui-draggable");
			wp_enqueue_script("jquery-ui-droppable");
		}
	}
	add_action('admin_enqueue_scripts', 'custom_accordion_admin_enqueue_scripts');	

	// Pro Version Purchase Link
	function tps_accordion_prover_action_links( $links ) {
		$links[] = '<a href="https://themepoints.com/product/wp-accordions-pro" style="color: red; font-weight: bold;" target="_blank">Buy Pro!</a>';
		return $links;
	}
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tps_accordion_prover_action_links' );

	# Register Meta Boxes
	function custom_accordion_wordpress_filter_meta_box( $meta_boxes ) {
	  $meta_boxes[] = array(
		'id'          => 'custom_accordion_wordpress_feature',
		'title'       => 'Accordion',
		'pages'       => array('accordion_tp'),
		'context'     => 'normal',
		'priority'    => 'high',
		'show_names'  => true, 
		'fields' 	  => array(
			array(
				'id'   => 'custom_accordion_wordpresspro_columns',
				'name'    => 'Accordion Item Details',
				'type' => 'group',
				'repeatable'     => true,
				'sortable'       => true,			
				'repeatable_max' => 5,

				'fields' => array(
					array(
						'id'              => 'custom_accordions_pro_title',
						'name'            => 'Accordion Title',                
						'type'            => 'text',
						'cols'            => 4
					),
					array(
						'id'              => 'custom_accordions_pro_details',
						'name'            => 'Description',                
						'type'            => 'wysiwyg',
						'sanitization_cb' => false,
						'options' => array( 'textarea_rows' => 8, ),
						'default'         => 'Insert Your Description Here?',
					),
				)
			)
		)
	);

	return $meta_boxes;
	}
	add_filter( 'cmb_meta_boxes', 'custom_accordion_wordpress_filter_meta_box' );


	# Accordion Custom Title Filter
	function custom_accordion_wordpress_title( $title ){
	  $screen = get_current_screen();
	  if  ( 'accordion_tp' == $screen->post_type ) {
		$title = 'Accordion Group Title';
	  }  
	  return $title;
	}	
	add_filter( 'enter_title_here', 'custom_accordion_wordpress_title' );

	/***************************************
	wp accordion option init
	***************************************/
	function themepoints_custom_accordion_option_init(){
		register_setting( 'custom_accordion_options_setting', 'themepoints_accordion_theme');
		register_setting( 'custom_accordion_options_setting', 'accordion_content_font_pages');
	}
	add_action('admin_init', 'themepoints_custom_accordion_option_init' );

	function themepoints_custom_accordion_submenu_pages() {
		add_submenu_page( 'edit.php?post_type=accordion_tp', __('Help & Support', 'tcaccordion'), __('Help & Support', 'tcaccordion'), 'manage_options', 'support', 'themepoints_custom_accordion_support_callback' );
	}

	function themepoints_custom_accordion_support_callback() {
		require_once(plugin_dir_path(__FILE__).'custom-accordion-admin.php');
	}
	add_action('admin_menu', 'themepoints_custom_accordion_submenu_pages');

	/*==========================================================================
		Custom Accordion register shortcode
	==========================================================================*/
	function custom_accordion_shortcode_register($atts, $content = null){
		wp_enqueue_script( 'accordion-responsive-js' );
	    wp_enqueue_style( 'accordion-responsive-css' );
	    wp_enqueue_style( 'accordion-main-css' );
		$atts = shortcode_atts(
			array(
				'id' => "",
			), $atts);
			global $post;
			$post_id = $atts['id'];
			
			$content = '';
			$content.= TCP_accordions_wordpress_table_body($post_id);
			return $content;
	}// shortcode hook
	add_shortcode('tcpaccordion', 'custom_accordion_shortcode_register');

?>