<?php
	/**
	 * Plugin Name: Accordion-WP
	 * Plugin URI:  https://themepoints.com/wp-accordions/
	 * Description: Create beautiful, responsive accordions and FAQ sections with multiple styles, skins, and advanced customization—perfect for organizing content and improving UX.
	 * Version:     3.0.6
	 * Author:      Themepoints
	 * Author URI:  https://themepoints.com
	 * Text Domain: tcaccordion
	 * License:     GPLv2 or later
	*/

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	/** 
	 * Define plugin constants
	 */
	define( 'TCACCORDION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'TCACCORDION_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

	/**
	 * Include required files
	 */
	require_once TCACCORDION_PLUGIN_PATH . 'metabox/custom-meta-boxes.php';
	require_once TCACCORDION_PLUGIN_PATH . 'inc/accordions-wp-post-type.php';
	require_once TCACCORDION_PLUGIN_PATH . 'theme/custom-wp-accordion-themes.php';

	/**
	 * Load translation for the plugin
	 */
	function custom_accordion_load_textdomain(){
		load_plugin_textdomain('tcaccordion', false, dirname( plugin_basename( __FILE__ ) ) .'/languages/' );
	}
	add_action('plugins_loaded', 'custom_accordion_load_textdomain');

	/**
	 * Enqueue frontend scripts and styles
	 */
	function custom_accordion_active_script(){
		wp_register_style( 'accordion-responsive-css', plugins_url( 'css/responsive-accordion.css' , __FILE__ ) );
		wp_register_style( 'accordion-main-css', plugins_url( 'css/style.css' , __FILE__ ) );
		wp_enqueue_script('jquery');
		wp_register_script('accordion-responsive-js', plugins_url( 'js/responsive-accordion.min.js', __FILE__ ), array('jquery'), '1.0', false);
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('accordion-wp-color-picker', plugins_url(), array( 'wp-color-picker' ), false, true );
	}
	add_action('wp_enqueue_scripts', 'custom_accordion_active_script');

	/**
	 * Enqueue admin scripts and styles for custom post type
	 */
	function custom_accordion_admin_enqueue_scripts(){
		global $typenow;
		if(($typenow == 'accordion_tp')){
			wp_enqueue_style( 'accordion-admin-css', plugins_url( 'admin/css/accordion-backend-admin.css' , __FILE__ ) );
			wp_enqueue_script('jquery');
			wp_enqueue_script('accordion-admin-js', plugins_url( 'admin/js/accordion-backend-admin.js', __FILE__ ), array('jquery'), '1.0', false);
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_script( 'accordion_color_picker', plugins_url('admin/js/color-picker.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
			wp_enqueue_script("jquery-ui-sortable");
			wp_enqueue_script("jquery-ui-draggable");
			wp_enqueue_script("jquery-ui-droppable");
		}
	}
	add_action('admin_enqueue_scripts', 'custom_accordion_admin_enqueue_scripts');	

	/**
	 * Add Pro Version Link on Plugin Page
	 */
	function tps_accordion_prover_action_links( $links ) {
		$links[] = '<a href="https://themepoints.com/wp-accordions/" style="color: red; font-weight: bold;" target="_blank">Buy Pro!</a>';
		return $links;
	}
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tps_accordion_prover_action_links' );

	/**
	 * Register Metabox
	 */
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

	/**
	 * Add Accordion Title Filter
	 */
	function custom_accordion_wordpress_title( $title ){
	  $screen = get_current_screen();
	  if  ( 'accordion_tp' == $screen->post_type ) {
		$title = 'Accordion Group Title';
	  }  
	  return $title;
	}
	add_filter( 'enter_title_here', 'custom_accordion_wordpress_title' );

	/**
	 * Add Options Page
	 */
	function themepoints_custom_accordion_option_init(){
		register_setting( 'custom_accordion_options_setting', 'themepoints_accordion_theme');
		register_setting( 'custom_accordion_options_setting', 'accordion_content_font_pages');
	}
	add_action('admin_init', 'themepoints_custom_accordion_option_init' );

	/**
	 * Add Plugin Submenu Page
	 */
	function themepoints_custom_accordion_submenu_pages() {
		add_submenu_page( 'edit.php?post_type=accordion_tp', __('Help & Support', 'tcaccordion'), __('Help & Support', 'tcaccordion'), 'manage_options', 'support', 'themepoints_custom_accordion_support_callback' );
	}

	/**
	 * Plugin Callback Function
	 */
	function themepoints_custom_accordion_support_callback() {
		require_once(plugin_dir_path(__FILE__).'inc/custom-accordion-admin.php');
	}
	add_action('admin_menu', 'themepoints_custom_accordion_submenu_pages');

	/**
	 * Register Plugin Shortcode
	 */
	function custom_accordion_shortcode_register($atts, $content = null){
		wp_enqueue_script( 'accordion-responsive-js' );
	    wp_enqueue_style( 'accordion-responsive-css' );
	    wp_enqueue_style( 'accordion-main-css' );
		$atts = shortcode_atts(
			array(
				'id' => "",
			), $atts
		);
		global $post;
		
		$post_id = $atts['id'];

		$content = '';
		$content.= TCP_accordions_wordpress_table_body($post_id);
		return $content;
	}// shortcode hook
	add_shortcode('tcpaccordion', 'custom_accordion_shortcode_register');