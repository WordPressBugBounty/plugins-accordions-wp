<?php
/**
 * Plugin Name: Accordion-WP
 * Plugin URI:  https://themepoints.com/wp-accordions/
 * Description: Create beautiful, responsive accordions and FAQ sections with multiple styles, skins, and advanced customization—perfect for organizing content and improving UX.
 * Version:     3.0.7
 * Author:      Themepoints
 * Author URI:  https://themepoints.com
 * Text Domain: tcaccordion
 * Domain Path: /languages
 * License:     GPL v2 or later
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Bootstrap Class.
 */
final class TCAccordion {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '3.0.7';

	/**
	 * Option key for storing the installation timestamp.
	 *
	 * @var string
	 */
	const INSTALL_KEY = 'tcaccordion_installed_time';

	/**
	 * Instance of this class.
	 *
	 * @var TCAccordion|null
	 */
	private static $instance = null;

	/**
	 * Main TCAccordion Instance.
	 *
	 * @return TCAccordion
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce Singleton pattern.
	 */
	private function __construct() {
		$this->define_constants();
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Define Plugin Constants.
	 *
	 * @return void
	 */
	private function define_constants() {
		define( 'TCACCORDION_VERSION', self::VERSION );
		define( 'TCACCORDION_FILE', __FILE__ );
		define( 'TCACCORDION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'TCACCORDION_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		define( 'TCACCORDION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Include Required Class Files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// Admin Metabox Manager
		require_once TCACCORDION_PLUGIN_PATH . 'admin/class-accordion-metabox.php';

		// Admin Support Page
		require_once TCACCORDION_PLUGIN_PATH . 'admin/class-tcaccordion-admin-support.php';

		// Admin Review Notice
		require_once TCACCORDION_PLUGIN_PATH . 'admin/class-tcaccordion-review-notice.php';

		// Frontend Renderer Engine
		require_once TCACCORDION_PLUGIN_PATH . 'theme/class-tcaccordion-renderer.php';

		// Shortcode Handler
		require_once TCACCORDION_PLUGIN_PATH . 'inc/class-tcaccordion-shortcode.php';
	}

	/**
	 * Register WordPress Hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Register activation hook
		register_activation_hook( TCACCORDION_FILE, array( $this, 'activate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . TCACCORDION_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Plugin Activation Callback.
	 * Sets installation timestamp if it doesn't already exist.
	 *
	 * @return void
	 */
	public function activate() {
		if ( ! get_option( self::INSTALL_KEY ) ) {
			update_option( self::INSTALL_KEY, time() );
		}
	}

	/**
	 * Get the installation timestamp.
	 *
	 * @return int Timestamp in seconds.
	 */
	public static function get_installation_time() {
		return (int) get_option( self::INSTALL_KEY, time() );
	}

	/**
	 * Load Translation Text Domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'tcaccordion',
			false,
			dirname( TCACCORDION_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register Frontend Scripts and Styles.
	 *
	 * @return void
	 */
	public function register_frontend_assets() {
		wp_register_style(
			'tcaccordion-responsive',
			TCACCORDION_PLUGIN_URL . 'assets/css/responsive-accordion.css',
			array(),
			TCACCORDION_VERSION
		);

		wp_register_style(
			'tcaccordion-style',
			TCACCORDION_PLUGIN_URL . 'assets/css/style.css',
			array(),
			TCACCORDION_VERSION
		);

		wp_register_script(
			'tcaccordion-script',
			TCACCORDION_PLUGIN_URL . 'assets/js/responsive-accordion.min.js',
			array( 'jquery' ),
			TCACCORDION_VERSION,
			true
		);
	}

	/**
	 * Register Plugin Admin Settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'custom_accordion_options_setting',
			'themepoints_accordion_theme'
		);

		register_setting(
			'custom_accordion_options_setting',
			'accordion_content_font_pages'
		);
	}

	/**
	 * Add custom action links on the Plugins page.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( $links ) {
		$custom_links = array(
			sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" style="color:#d63638;font-weight:600;">%s</a>',
				esc_url( 'https://themepoints.com/wp-accordions/' ),
				esc_html__( 'Buy Pro!', 'tcaccordion' )
			),
		);

		return array_merge( $links, $custom_links );
	}
}

/**
 * Global function instance accessor.
 *
 * @return TCAccordion
 */
function tcaccordion() {
	return TCAccordion::instance();
}

// Initialize the plugin.
tcaccordion();