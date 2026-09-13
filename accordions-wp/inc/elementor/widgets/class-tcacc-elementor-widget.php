<?php
/**
 * TCAccordion Elementor Widget.
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class TCAccordion_Elementor_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     */
    public function get_name() {
        return 'tcpaccordion_widget';
    }

    /**
     * Get widget title.
     */
    public function get_title() {
        return esc_html__( 'TCP Accordion', 'tcaccordion' );
    }

    /**
     * Get widget icon.
     */
    public function get_icon() {
        return 'eicon-accordion';
    }

    /**
     * Get widget categories.
     */
    public function get_categories() {
        return array( 'general' );
    }

/**
 * Enqueue styles specifically for Elementor Editor Preview & Frontend.
 *
 * @return array
 */
public function get_style_depends() {
    return array( 
        'tcaccordion-responsive', 
        'tcaccordion-style',
        'font-awesome' // Include Font Awesome if your icons rely on it
    );
}

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => esc_html__( 'Accordion Settings', 'tcaccordion' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        // Fetch published post type 'accordion_tp' to match your shortcode
        $accordions = get_posts(
            array(
                'post_type'      => 'accordion_tp',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            )
        );

        $options = array( '' => esc_html__( 'Select Accordion', 'tcaccordion' ) );
        if ( ! empty( $accordions ) ) {
            foreach ( $accordions as $accordion ) {
                $options[ $accordion->ID ] = $accordion->post_title;
            }
        }

        $this->add_control(
            'accordion_id',
            array(
                'label'       => esc_html__( 'Select Accordion', 'tcaccordion' ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'default'     => '',
                'options'     => $options,
                'description' => esc_html__( 'Choose an accordion created with TCP Accordion.', 'tcaccordion' ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     */
protected function render() {
    $settings     = $this->get_settings_for_display();
    $accordion_id = ! empty( $settings['accordion_id'] ) ? absint( $settings['accordion_id'] ) : 0;

    // Show a helpful placeholder in the Elementor Editor if no ID is selected yet
    if ( ! $accordion_id ) {
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            echo '<div style="padding: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; text-align: center; border-radius: 6px;">';
            echo '<p style="margin: 0; color: #64748b; font-weight: 600;">' . esc_html__( 'Please select an accordion from the left widget panel.', 'tcaccordion' ) . '</p>';
            echo '</div>';
        }
        return;
    }

    // Force post query setup for Elementor editor rendering context
    global $post;
    $original_post = $post;
    
    // Execute shortcode
    $output = do_shortcode( sprintf( '[tcpaccordion id="%d"]', $accordion_id ) );

    if ( empty( trim( $output ) ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        echo '<div style="padding: 15px; background: #fff1f2; border: 1px solid #fecdd3; color: #e11d48; text-align: center; border-radius: 6px;">';
        echo esc_html__( 'Accordion found, but contains no active items or failed to render.', 'tcaccordion' );
        echo '</div>';
    } else {
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
}