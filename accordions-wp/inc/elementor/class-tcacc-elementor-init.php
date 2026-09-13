<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TCACC_Elementor_Init {
    public function __construct() {
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
    }

    public function register_widgets( $widgets_manager ) {
        require_once plugin_dir_path( __FILE__ ) . 'widgets/class-tcacc-elementor-widget.php';

		// Register the widget
		$widgets_manager->register( new \TCAccordion_Elementor_Widget() );
    }
}
new TCACC_Elementor_Init();