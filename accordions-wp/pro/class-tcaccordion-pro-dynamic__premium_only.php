<?php
/**
 * Pro: dynamic content. Build the accordion items from posts, a custom post type or an ACF repeater
 * instead of typing them by hand.
 *
 * The "__premium_only" part of the file name makes Freemius remove it from the free version.
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic content source.
 */
class TCAccordion_Pro_Dynamic {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'tcaccordion_items', array( $this, 'build_items' ), 10, 2 );
		add_action( 'tcaccordion_pro_settings', array( $this, 'render_settings' ), 20 );
		add_action( 'tcaccordion_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Replace the manual items when a dynamic source is selected.
	 *
	 * @param array                $items    Manual items.
	 * @param TCAccordion_Renderer $renderer Renderer.
	 * @return array
	 */
	public function build_items( $items, $renderer ) {
		$source = $renderer->get_setting( '_tcacc_source', 'manual' );

		if ( 'posts' === $source ) {
			return $this->items_from_posts( $renderer );
		}

		if ( 'acf' === $source ) {
			$acf_items = $this->items_from_acf( $renderer );
			return null === $acf_items ? $items : $acf_items;
		}

		return $items;
	}

	/**
	 * Post types that can be listed (public, and never the accordions themselves).
	 *
	 * @return array Slug => label.
	 */
	private function get_post_type_choices() {
		$choices = array();

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $slug => $type ) {
			if ( in_array( $slug, array( 'attachment', 'accordion_tp' ), true ) ) {
				continue;
			}
			$choices[ $slug ] = $type->labels->name;
		}

		return $choices;
	}

	/**
	 * Taxonomies that can filter the posts.
	 *
	 * @return array Slug => label.
	 */
	private function get_taxonomy_choices() {
		$choices = array();

		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $slug => $taxonomy ) {
			if ( 'post_format' === $slug ) {
				continue;
			}
			$choices[ $slug ] = $taxonomy->labels->name;
		}

		return $choices;
	}

	/**
	 * Build the items from WordPress posts.
	 *
	 * @param TCAccordion_Renderer $renderer Renderer.
	 * @return array
	 */
	private function items_from_posts( $renderer ) {
		$post_type = sanitize_key( (string) $renderer->get_setting( '_tcacc_src_post_type', 'post' ) );
		if ( ! array_key_exists( $post_type, $this->get_post_type_choices() ) ) {
			$post_type = 'post';
		}

		$count = absint( $renderer->get_setting( '_tcacc_src_count', '10' ) );
		$count = max( 1, min( 100, $count ? $count : 10 ) );

		$orderby = (string) $renderer->get_setting( '_tcacc_src_orderby', 'date' );
		$orderby = in_array( $orderby, array( 'date', 'title', 'menu_order', 'modified', 'rand' ), true ) ? $orderby : 'date';

		$order = ( 'ASC' === strtoupper( (string) $renderer->get_setting( '_tcacc_src_order', 'DESC' ) ) ) ? 'ASC' : 'DESC';

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $count,
			'orderby'             => $orderby,
			'order'               => $order,
			'has_password'        => false,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		$taxonomy = sanitize_key( (string) $renderer->get_setting( '_tcacc_src_taxonomy', '' ) );
		$terms    = array();

		if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) ) {
			$terms = array_filter( array_map( 'sanitize_title', explode( ',', (string) $renderer->get_setting( '_tcacc_src_terms', '' ) ) ) );

			if ( ! empty( $terms ) ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => array_values( $terms ),
					),
				);
			}
		}

		$use_excerpt   = ( 'full' !== $renderer->get_setting( '_tcacc_src_content', 'excerpt' ) );
		$read_more     = ( 'yes' === $renderer->get_setting( '_tcacc_src_readmore', 'no' ) );
		$group_by_term = ( 'yes' === $renderer->get_setting( '_tcacc_src_group_by_term', 'no' ) ) && '' !== $taxonomy && taxonomy_exists( $taxonomy );

		$query = new WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$content = preg_replace( '/<!--.*?-->/s', '', strip_shortcodes( (string) $post->post_content ) );

			if ( $use_excerpt ) {
				$text = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $content ), 55 );
				$html = '<p>' . esc_html( $text ) . '</p>';
			} else {
				$html = wpautop( wp_kses_post( $content ) );
			}

			if ( $read_more ) {
				$html .= '<p><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html__( 'Read more', 'accordions-wp' ) . '</a></p>';
			}

			$group = '';
			if ( $group_by_term ) {
				$names = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'names', 'number' => 1 ) );
				$group = ( ! is_wp_error( $names ) && ! empty( $names ) ) ? mb_substr( wp_strip_all_tags( $names[0] ), 0, 40 ) : '';
			}

			$items[] = array(
				'title'       => wp_strip_all_tags( get_the_title( $post ) ),
				'description' => $html,
				'group'       => $group,
			);
		}

		return $items;
	}

	/**
	 * Build the items from an Advanced Custom Fields repeater.
	 *
	 * @param TCAccordion_Renderer $renderer Renderer.
	 * @return array|null Items, or null when ACF is not available.
	 */
	private function items_from_acf( $renderer ) {
		if ( ! function_exists( 'get_field' ) ) {
			return null;
		}

		$clean = static function ( $name ) {
			return preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $name );
		};

		$field         = $clean( $renderer->get_setting( '_tcacc_acf_field', '' ) );
		$title_field   = $clean( $renderer->get_setting( '_tcacc_acf_title', '' ) );
		$content_field = $clean( $renderer->get_setting( '_tcacc_acf_content', '' ) );

		if ( '' === $field || '' === $title_field || '' === $content_field ) {
			return array();
		}

		$location = ( 'option' === $renderer->get_setting( '_tcacc_acf_location', 'current' ) ) ? 'option' : get_queried_object_id();
		$rows     = get_field( $field, $location );
		$items    = array();

		if ( ! is_array( $rows ) ) {
			return $items;
		}

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$title   = isset( $row[ $title_field ] ) ? wp_strip_all_tags( (string) $row[ $title_field ] ) : '';
			$content = isset( $row[ $content_field ] ) ? wpautop( wp_kses_post( (string) $row[ $content_field ] ) ) : '';

			if ( '' !== $title || '' !== $content ) {
				$items[] = array(
					'title'       => $title,
					'description' => $content,
				);
			}
		}

		return $items;
	}

	/**
	 * Settings rows.
	 *
	 * @param WP_Post $post Accordion post.
	 * @return void
	 */
	public function render_settings( $post ) {
		$get = static function ( $key, $default = '' ) use ( $post ) {
			$value = get_post_meta( $post->ID, $key, true );
			return ( '' === $value || false === $value ) ? $default : $value;
		};

		$source = $get( '_tcacc_source', 'manual' );
		?>
		<tr valign="top">
			<th scope="row" colspan="2">
				<h3 style="margin:18px 0 0;"><?php esc_html_e( 'Dynamic content (Pro)', 'accordions-wp' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Build the items automatically instead of typing them. When a source other than Manual is selected, the items in the Content tab are not shown on the site.', 'accordions-wp' ); ?></p>
			</th>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="_tcacc_source"><?php esc_html_e( 'Content source', 'accordions-wp' ); ?></label></th>
			<td>
				<select name="_tcacc_source" id="_tcacc_source">
					<option value="manual" <?php selected( $source, 'manual' ); ?>><?php esc_html_e( 'Manual items (Content tab)', 'accordions-wp' ); ?></option>
					<option value="posts" <?php selected( $source, 'posts' ); ?>><?php esc_html_e( 'Posts / custom post type', 'accordions-wp' ); ?></option>
					<option value="acf" <?php selected( $source, 'acf' ); ?>><?php esc_html_e( 'ACF repeater field', 'accordions-wp' ); ?></option>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="_tcacc_src_post_type"><?php esc_html_e( 'Post type', 'accordions-wp' ); ?></label></th>
			<td>
				<select name="_tcacc_src_post_type" id="_tcacc_src_post_type">
					<?php foreach ( $this->get_post_type_choices() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $get( '_tcacc_src_post_type', 'post' ), $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Used when the source is Posts. Each post becomes one item (title = post title).', 'accordions-wp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="_tcacc_src_taxonomy"><?php esc_html_e( 'Filter by taxonomy', 'accordions-wp' ); ?></label></th>
			<td>
				<select name="_tcacc_src_taxonomy" id="_tcacc_src_taxonomy">
					<option value=""><?php esc_html_e( '— No filter —', 'accordions-wp' ); ?></option>
					<?php foreach ( $this->get_taxonomy_choices() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $get( '_tcacc_src_taxonomy', '' ), $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="_tcacc_src_terms" class="regular-text" value="<?php echo esc_attr( $get( '_tcacc_src_terms', '' ) ); ?>" placeholder="<?php esc_attr_e( 'term-slug-1, term-slug-2', 'accordions-wp' ); ?>" />
				<p class="description"><?php esc_html_e( 'Only posts in these terms (comma-separated slugs) are listed.', 'accordions-wp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="_tcacc_src_count"><?php esc_html_e( 'Number of items', 'accordions-wp' ); ?></label></th>
			<td>
				<input type="number" name="_tcacc_src_count" id="_tcacc_src_count" min="1" max="100" class="small-text" value="<?php echo esc_attr( $get( '_tcacc_src_count', '10' ) ); ?>" />
				<select name="_tcacc_src_orderby">
					<?php
					$orderby_choices = array(
						'date'       => __( 'Date', 'accordions-wp' ),
						'title'      => __( 'Title', 'accordions-wp' ),
						'menu_order' => __( 'Menu order', 'accordions-wp' ),
						'modified'   => __( 'Last modified', 'accordions-wp' ),
						'rand'       => __( 'Random', 'accordions-wp' ),
					);
					foreach ( $orderby_choices as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $get( '_tcacc_src_orderby', 'date' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="_tcacc_src_order">
					<option value="DESC" <?php selected( $get( '_tcacc_src_order', 'DESC' ), 'DESC' ); ?>><?php esc_html_e( 'Newest / Z-A first', 'accordions-wp' ); ?></option>
					<option value="ASC" <?php selected( $get( '_tcacc_src_order', 'DESC' ), 'ASC' ); ?>><?php esc_html_e( 'Oldest / A-Z first', 'accordions-wp' ); ?></option>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="_tcacc_src_content"><?php esc_html_e( 'Item content', 'accordions-wp' ); ?></label></th>
			<td>
				<select name="_tcacc_src_content" id="_tcacc_src_content">
					<option value="excerpt" <?php selected( $get( '_tcacc_src_content', 'excerpt' ), 'excerpt' ); ?>><?php esc_html_e( 'Excerpt', 'accordions-wp' ); ?></option>
					<option value="full" <?php selected( $get( '_tcacc_src_content', 'excerpt' ), 'full' ); ?>><?php esc_html_e( 'Full content', 'accordions-wp' ); ?></option>
				</select>
				<select name="_tcacc_src_readmore">
					<option value="no" <?php selected( $get( '_tcacc_src_readmore', 'no' ), 'no' ); ?>><?php esc_html_e( 'No "Read more" link', 'accordions-wp' ); ?></option>
					<option value="yes" <?php selected( $get( '_tcacc_src_readmore', 'no' ), 'yes' ); ?>><?php esc_html_e( 'Add "Read more" link', 'accordions-wp' ); ?></option>
				</select>
				<select name="_tcacc_src_group_by_term">
					<option value="no" <?php selected( $get( '_tcacc_src_group_by_term', 'no' ), 'no' ); ?>><?php esc_html_e( 'No groups', 'accordions-wp' ); ?></option>
					<option value="yes" <?php selected( $get( '_tcacc_src_group_by_term', 'no' ), 'yes' ); ?>><?php esc_html_e( 'Group by first term of the taxonomy', 'accordions-wp' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Shortcodes in post content are removed. Password-protected posts are never listed. Use "Group by" together with Group filter buttons.', 'accordions-wp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="_tcacc_acf_field"><?php esc_html_e( 'ACF repeater', 'accordions-wp' ); ?></label></th>
			<td>
				<input type="text" name="_tcacc_acf_field" id="_tcacc_acf_field" class="regular-text" value="<?php echo esc_attr( $get( '_tcacc_acf_field', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Repeater field name, e.g. faq_items', 'accordions-wp' ); ?>" />
				<input type="text" name="_tcacc_acf_title" class="regular-text" value="<?php echo esc_attr( $get( '_tcacc_acf_title', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Title sub-field name', 'accordions-wp' ); ?>" />
				<input type="text" name="_tcacc_acf_content" class="regular-text" value="<?php echo esc_attr( $get( '_tcacc_acf_content', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Content sub-field name', 'accordions-wp' ); ?>" />
				<select name="_tcacc_acf_location">
					<option value="current" <?php selected( $get( '_tcacc_acf_location', 'current' ), 'current' ); ?>><?php esc_html_e( 'Read from the page showing the accordion', 'accordions-wp' ); ?></option>
					<option value="option" <?php selected( $get( '_tcacc_acf_location', 'current' ), 'option' ); ?>><?php esc_html_e( 'Read from an ACF options page', 'accordions-wp' ); ?></option>
				</select>
				<p class="description">
					<?php
					echo function_exists( 'get_field' )
						? esc_html__( 'Used when the source is ACF repeater field.', 'accordions-wp' )
						: esc_html__( 'Advanced Custom Fields is not active. Until it is, the manual items are shown.', 'accordions-wp' );
					?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the dynamic content settings. The nonce and permissions are checked by the free plugin first.
	 *
	 * @param int $post_id Accordion ID.
	 * @return void
	 */
	public function save_settings( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$text_fields = array(
			'_tcacc_src_terms'   => 200,
			'_tcacc_acf_field'   => 60,
			'_tcacc_acf_title'   => 60,
			'_tcacc_acf_content' => 60,
		);

		foreach ( $text_fields as $key => $max ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, mb_substr( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ), 0, $max ) );
			}
		}

		$choices = array(
			'_tcacc_source'            => array( 'manual', 'posts', 'acf' ),
			'_tcacc_src_orderby'       => array( 'date', 'title', 'menu_order', 'modified', 'rand' ),
			'_tcacc_src_order'         => array( 'DESC', 'ASC' ),
			'_tcacc_src_content'       => array( 'excerpt', 'full' ),
			'_tcacc_src_readmore'      => array( 'no', 'yes' ),
			'_tcacc_src_group_by_term' => array( 'no', 'yes' ),
			'_tcacc_acf_location'      => array( 'current', 'option' ),
		);

		foreach ( $choices as $key => $allowed ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				update_post_meta( $post_id, $key, in_array( $value, $allowed, true ) ? $value : $allowed[0] );
			}
		}

		if ( isset( $_POST['_tcacc_src_post_type'] ) ) {
			$type = sanitize_key( wp_unslash( $_POST['_tcacc_src_post_type'] ) );
			update_post_meta( $post_id, '_tcacc_src_post_type', array_key_exists( $type, $this->get_post_type_choices() ) ? $type : 'post' );
		}

		if ( isset( $_POST['_tcacc_src_taxonomy'] ) ) {
			$taxonomy = sanitize_key( wp_unslash( $_POST['_tcacc_src_taxonomy'] ) );
			update_post_meta( $post_id, '_tcacc_src_taxonomy', ( '' === $taxonomy || array_key_exists( $taxonomy, $this->get_taxonomy_choices() ) ) ? $taxonomy : '' );
		}

		if ( isset( $_POST['_tcacc_src_count'] ) ) {
			$count = absint( wp_unslash( $_POST['_tcacc_src_count'] ) );
			update_post_meta( $post_id, '_tcacc_src_count', max( 1, min( 100, $count ? $count : 10 ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}

new TCAccordion_Pro_Dynamic();
