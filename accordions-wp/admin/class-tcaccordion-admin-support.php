<?php
/**
 * Admin Support Page Class.
 *
 * @package     TCAccordion
 * @subpackage  TCAccordion/Admin
 * @version     3.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the admin support page rendering and menu integration.
 */
class TCAccordion_Admin_Support {

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_support_page' ), 99 );
	}

	/**
	 * Register the admin submenu page under the main plugin menu.
	 *
	 * @return void
	 */
	public function register_support_page() {
		add_submenu_page(
			'edit.php?post_type=accordion_tp',
			__( 'Support', 'tcaccordion' ),
			__( 'Support', 'tcaccordion' ),
			'manage_options',
			'tcaccordion-support',
			array( $this, 'render_support_page' )
		);
	}

	/**
	 * Render the support page view.
	 *
	 * @return void
	 */
	public function render_support_page() {
		$view_path = plugin_dir_path( __FILE__ ) . 'views/support-page.php';

		if ( file_exists( $view_path ) ) {
			include $view_path;
		}
	}
}

// Instantiate class.
new TCAccordion_Admin_Support();