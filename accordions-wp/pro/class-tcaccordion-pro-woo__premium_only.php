<?php
/**
 * Pro: WooCommerce category rules. Show an accordion only on products in chosen categories,
 * or hide it on products in other categories.
 *
 * The "__premium_only" part of the file name makes Freemius remove it from the free version.
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product category rules for WooCommerce accordions.
 */
class TCAccordion_Pro_Woo {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'tcaccordion_woo_accordion_ids', array( $this, 'filter_accordions' ), 10, 3 );
		add_action( 'tcaccordion_pro_settings', array( $this, 'render_settings' ), 40 );
		add_action( 'tcaccordion_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Keep only the accordions whose category rules match the product.
	 *
	 * @param int[]  $ids        Accordion IDs enabled for the location.
	 * @param string $location   'tab' or 'after_summary'.
	 * @param int    $product_id Product ID.
	 * @return int[]
	 */
	public function filter_accordions( $ids, $location, $product_id ) {
		unset( $location );

		if ( empty( $ids ) || ! $product_id ) {
			return $ids;
		}

		$keep = array();

		foreach ( $ids as $accordion_id ) {
			$include = $this->with_children( (array) get_post_meta( $accordion_id, '_tcacc_woo_categories', true ) );
			$exclude = $this->with_children( (array) get_post_meta( $accordion_id, '_tcacc_woo_exclude_categories', true ) );

			if ( ! empty( $exclude ) && has_term( $exclude, 'product_cat', $product_id ) ) {
				continue;
			}

			if ( ! empty( $include ) && ! has_term( $include, 'product_cat', $product_id ) ) {
				continue;
			}

			$keep[] = $accordion_id;
		}

		return $keep;
	}

	/**
	 * Add the sub-categories of every selected category.
	 *
	 * @param array $term_ids Selected category IDs.
	 * @return int[]
	 */
	private function with_children( $term_ids ) {
		$term_ids = array_filter( array_map( 'absint', $term_ids ) );
		$all      = $term_ids;

		foreach ( $term_ids as $term_id ) {
			$children = get_term_children( $term_id, 'product_cat' );
			if ( ! is_wp_error( $children ) ) {
				$all = array_merge( $all, array_map( 'absint', $children ) );
			}
		}

		return array_values( array_unique( $all ) );
	}

	/**
	 * Product categories as a flat, indented list.
	 *
	 * @param array $terms  All category terms.
	 * @param int   $parent Parent term ID.
	 * @param int   $depth  Current depth.
	 * @return array List of array( 'id' => int, 'label' => string ).
	 */
	private function flatten( $terms, $parent = 0, $depth = 0 ) {
		$list = array();

		foreach ( $terms as $term ) {
			if ( (int) $term->parent === (int) $parent ) {
				$list[] = array(
					'id'    => (int) $term->term_id,
					'label' => str_repeat( '— ', $depth ) . $term->name,
				);
				$list   = array_merge( $list, $this->flatten( $terms, $term->term_id, $depth + 1 ) );
			}
		}

		return $list;
	}

	/**
	 * Settings rows (only when WooCommerce is active).
	 *
	 * @param WP_Post $post Accordion post.
	 * @return void
	 */
	public function render_settings( $post ) {
		if ( ! class_exists( 'WooCommerce' ) || ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$list    = $this->flatten( $terms );
		$include = array_map( 'absint', (array) get_post_meta( $post->ID, '_tcacc_woo_categories', true ) );
		$exclude = array_map( 'absint', (array) get_post_meta( $post->ID, '_tcacc_woo_exclude_categories', true ) );
		?>
		<tr valign="top">
			<th scope="row" colspan="2">
				<h3 style="margin:18px 0 0;"><?php esc_html_e( 'WooCommerce category rules (Pro)', 'accordions-wp' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Works together with "Enable on WooCommerce products". Sub-categories are included. A product-specific accordion chosen on the product page always wins.', 'accordions-wp' ); ?></p>
			</th>
		</tr>
		<?php
		$fields = array(
			'_tcacc_woo_categories'         => array( __( 'Show only in categories', 'accordions-wp' ), $include, __( 'Leave empty to show on products in every category.', 'accordions-wp' ) ),
			'_tcacc_woo_exclude_categories' => array( __( 'Hide in categories', 'accordions-wp' ), $exclude, __( 'Products in these categories never show this accordion.', 'accordions-wp' ) ),
		);

		foreach ( $fields as $name => $field ) :
			?>
			<tr valign="top">
				<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
				<td>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[]" value="" />
					<select name="<?php echo esc_attr( $name ); ?>[]" id="<?php echo esc_attr( $name ); ?>" multiple="multiple" size="6" style="min-width:280px;">
						<?php foreach ( $list as $option ) : ?>
							<option value="<?php echo esc_attr( $option['id'] ); ?>" <?php selected( in_array( $option['id'], $field[1], true ) ); ?>><?php echo esc_html( $option['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php echo esc_html( $field[2] ); ?> <?php esc_html_e( 'Hold Ctrl (Cmd on Mac) to select several.', 'accordions-wp' ); ?></p>
				</td>
			</tr>
			<?php
		endforeach;
	}

	/**
	 * Save the category rules. The nonce and permissions are checked by the free plugin first.
	 *
	 * @param int $post_id Accordion ID.
	 * @return void
	 */
	public function save_settings( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( array( '_tcacc_woo_categories', '_tcacc_woo_exclude_categories' ) as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST[ $key ] ) ) ) ) );

			if ( empty( $ids ) ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $ids );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
}

new TCAccordion_Pro_Woo();
