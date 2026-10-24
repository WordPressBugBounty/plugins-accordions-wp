<?php
/**
 * WooCommerce Integration Handler
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TCAccordion_WooCommerce {

	public function __construct() {
		add_action( 'wp', array( $this, 'init_hooks' ) );
		// Register product-level dropdown meta box inside WooCommerce product data
		add_action( 'woocommerce_product_options_advanced', array( $this, 'add_product_accordion_meta_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_accordion_meta_field' ) );
	}

	/**
	 * Register frontend hooks on single product pages.
	 */
	public function init_hooks() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		// 1. Hook into standard product tabs
		add_filter( 'woocommerce_product_tabs', array( $this, 'add_custom_product_tab' ), 98 );

		// 2. Hook below add to cart form (prevents duplicate summary rendering)
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render_summary_accordion' ), 20 );

		// Enqueue scripts & styles on product page
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_woocommerce_assets' ) );
	}

	/**
	 * Find active accordions designated for WooCommerce product display.
	 *
	 * @param string $location Filter by 'tab' or 'after_summary'.
	 * @return array List of post IDs.
	 */
	private function get_active_woo_accordions( $location = 'tab', $product_id = 0 ) {
		// 1. Check if the current product has a specific accordion assigned to it
		if ( $product_id ) {
			$specific_acc_id = get_post_meta( $product_id, '_tcacc_assigned_accordion', true );
			if ( ! empty( $specific_acc_id ) ) {
				$saved_location = get_post_meta( $specific_acc_id, '_tcacc_woo_location', true );
				if ( empty( $saved_location ) ) {
					$saved_location = 'tab';
				}
				if ( $saved_location === $location ) {
					return array( absint( $specific_acc_id ) );
				}
				// If a specific accordion is assigned to this product but belongs to a different location, return empty for this location
				return array();
			}
		}

		// 2. Fallback to global setting (accordions enabled globally for all products)
		$args = array(
			'post_type'      => 'accordion_tp',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_tcacc_woo_enable',
					'value'   => 'yes',
					'compare' => '=',
				),
			),
		);

		$accordion_ids = get_posts( $args );

		if ( empty( $accordion_ids ) ) {
			return array();
		}

		$filtered_ids = array();

		foreach ( $accordion_ids as $acc_id ) {
			$saved_location = get_post_meta( $acc_id, '_tcacc_woo_location', true );

			if ( empty( $saved_location ) ) {
				$saved_location = 'tab';
			}

			if ( $saved_location === $location ) {
				$filtered_ids[] = absint( $acc_id );
			}
		}

		/**
		 * Lets add-ons narrow down which globally enabled accordions show on this product
		 * (for example by product category).
		 *
		 * @param int[]  $filtered_ids Accordion IDs enabled for this location.
		 * @param string $location     'tab' or 'after_summary'.
		 * @param int    $product_id   Current product ID.
		 */
		return apply_filters( 'tcaccordion_woo_accordion_ids', $filtered_ids, $location, $product_id );
	}

	/**
	 * Enqueue Accordion Frontend Assets on WooCommerce Product Pages
	 */
	public function enqueue_woocommerce_assets() {
		if ( wp_style_is( 'tcaccordion-responsive', 'registered' ) ) {
			wp_enqueue_style( 'tcaccordion-responsive' );
		}
		if ( wp_style_is( 'tcaccordion-style', 'registered' ) ) {
			wp_enqueue_style( 'tcaccordion-style' );
		}
		if ( wp_script_is( 'tcaccordion-script', 'registered' ) ) {
			wp_enqueue_script( 'tcaccordion-script' );
		}

	}

	/**
	 * Inject Custom WooCommerce Product Tab
	 */
	public function add_custom_product_tab( $tabs ) {
		global $product;
		if ( ! $product ) {
			return $tabs;
		}

		$accordion_ids = $this->get_active_woo_accordions( 'tab', $product->get_id() );

		if ( empty( $accordion_ids ) ) {
			return $tabs;
		}

		foreach ( $accordion_ids as $acc_id ) {
			$title = get_post_meta( $acc_id, '_tcacc_woo_tab_title', true );
			$title = ! empty( $title ) ? $title : get_the_title( $acc_id );

			$tab_key = 'tcacc_tab_' . $acc_id;

			$tabs[ $tab_key ] = array(
				'title'    => esc_html( $title ),
				'priority' => 50,
				'callback' => array( $this, 'render_tab_content' ),
				'acc_id'   => $acc_id,
			);
		}

		return $tabs;
	}

	/**
	 * Render Tab Content Callback
	 */
	public function render_tab_content( $key = '', $tab = array() ) {
		$acc_id = 0;

		if ( ! empty( $tab['acc_id'] ) ) {
			$acc_id = absint( $tab['acc_id'] );
		} elseif ( ! empty( $key ) && 0 === strpos( $key, 'tcacc_tab_' ) ) {
			$acc_id = absint( str_replace( 'tcacc_tab_', '', $key ) );
		}

		if ( $acc_id && class_exists( 'TCAccordion_Renderer' ) ) {
			$renderer = new TCAccordion_Renderer( $acc_id );
			echo $renderer->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Render Accordion below Add to Cart Form
	 */
	public function render_summary_accordion() {
		global $product;
		if ( ! $product ) {
			return;
		}

		$accordion_ids = $this->get_active_woo_accordions( 'after_summary', $product->get_id() );

		if ( empty( $accordion_ids ) ) {
			return;
		}

		foreach ( $accordion_ids as $acc_id ) {
			if ( class_exists( 'TCAccordion_Renderer' ) ) {
				$renderer = new TCAccordion_Renderer( $acc_id );
				echo '<div class="tcaccordion-woo-summary-wrap" style="clear:both; margin-top:20px; margin-bottom:20px;">';
				echo $renderer->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</div>';
			}
		}
	}

	/**
	 * Add dropdown field to individual WooCommerce Product Edit screen (Advanced Tab)
	 */
	public function add_product_accordion_meta_field() {
		global $post;

		// Fetch all created accordions for the dropdown selector
		$accordions = get_posts(
			array(
				'post_type'      => 'accordion_tp',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		$options = array( '' => __( '-- Use Global Default Accordion --', 'accordions-wp' ) );
		if ( ! empty( $accordions ) ) {
			foreach ( $accordions as $acc ) {
				$options[ $acc->ID ] = $acc->post_title;
			}
		}

		$selected = get_post_meta( $post->ID, '_tcacc_assigned_accordion', true );

		echo '<div class="options_group">';
		echo '<p class="form-field tcacc_assigned_accordion_field">';
		echo '<label for="_tcacc_assigned_accordion">' . esc_html__( 'TCP Accordion', 'accordions-wp' ) . '</label>';
		echo '<select id="_tcacc_assigned_accordion" name="_tcacc_assigned_accordion" class="select short">';
		
		foreach ( $options as $id => $label ) {
			echo '<option value="' . esc_attr( $id ) . '" ' . selected( $selected, $id, false ) . '>' . esc_html( $label ) . '</option>';
		}

		echo '</select>';
		echo '<span class="description">' . esc_html__( 'Assign a specific accordion to this product, or leave default to use global display rules.', 'accordions-wp' ) . '</span>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Save product-specific accordion choice
	 */
	public function save_product_accordion_meta_field( $post_id ) {
		if ( isset( $_POST['_tcacc_assigned_accordion'] ) ) {
			update_post_meta( $post_id, '_tcacc_assigned_accordion', absint( $_POST['_tcacc_assigned_accordion'] ) );
		}
	}
}

// Initialize handler via plugins_loaded hook
add_action( 'plugins_loaded', function() {
	if ( class_exists( 'WooCommerce' ) ) {
		new TCAccordion_WooCommerce();
	}
} );