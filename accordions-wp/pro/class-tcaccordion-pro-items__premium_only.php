<?php
/**
 * Pro: per-item icons and badges, and groups / category filter tabs.
 *
 * The "__premium_only" part of the file name makes Freemius remove it from the free version.
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Item level Pro features.
 */
class TCAccordion_Pro_Items {

	/**
	 * Font Awesome icons that can be chosen for an item (all are in the free Font Awesome set).
	 *
	 * @return array Icon class => label.
	 */
	public static function get_icons() {
		return array(
			'fa-star'            => __( 'Star', 'accordions-wp' ),
			'fa-fire'            => __( 'Fire', 'accordions-wp' ),
			'fa-bolt'            => __( 'Bolt', 'accordions-wp' ),
			'fa-circle-question' => __( 'Question', 'accordions-wp' ),
			'fa-circle-info'     => __( 'Info', 'accordions-wp' ),
			'fa-lightbulb'       => __( 'Lightbulb', 'accordions-wp' ),
			'fa-gift'            => __( 'Gift', 'accordions-wp' ),
			'fa-truck'           => __( 'Delivery truck', 'accordions-wp' ),
			'fa-box'             => __( 'Box', 'accordions-wp' ),
			'fa-rotate-left'     => __( 'Return', 'accordions-wp' ),
			'fa-credit-card'     => __( 'Credit card', 'accordions-wp' ),
			'fa-percent'         => __( 'Percent', 'accordions-wp' ),
			'fa-tag'             => __( 'Tag', 'accordions-wp' ),
			'fa-shield-halved'   => __( 'Shield', 'accordions-wp' ),
			'fa-lock'            => __( 'Lock', 'accordions-wp' ),
			'fa-circle-check'    => __( 'Check', 'accordions-wp' ),
			'fa-heart'           => __( 'Heart', 'accordions-wp' ),
			'fa-bell'            => __( 'Bell', 'accordions-wp' ),
			'fa-user'            => __( 'User', 'accordions-wp' ),
			'fa-gear'            => __( 'Gear', 'accordions-wp' ),
			'fa-book'            => __( 'Book', 'accordions-wp' ),
			'fa-envelope'        => __( 'Envelope', 'accordions-wp' ),
			'fa-phone'           => __( 'Phone', 'accordions-wp' ),
			'fa-clock'           => __( 'Clock', 'accordions-wp' ),
			'fa-globe'           => __( 'Globe', 'accordions-wp' ),
		);
	}

	/**
	 * Badge colors.
	 *
	 * @return array Slug => label.
	 */
	public static function get_badge_colors() {
		return array(
			'blue'   => __( 'Blue', 'accordions-wp' ),
			'green'  => __( 'Green', 'accordions-wp' ),
			'orange' => __( 'Orange', 'accordions-wp' ),
			'red'    => __( 'Red', 'accordions-wp' ),
			'purple' => __( 'Purple', 'accordions-wp' ),
			'gray'   => __( 'Gray', 'accordions-wp' ),
		);
	}

	/**
	 * Register hooks.
	 */
	public function __construct() {
		// Item data: keep, sanitize and edit.
		add_filter( 'tcaccordion_item_data', array( $this, 'keep_item_data' ), 11, 2 );
		add_filter( 'tcaccordion_admin_item', array( $this, 'keep_item_data' ), 11, 2 );
		add_filter( 'tcaccordion_sanitize_item', array( $this, 'sanitize_item' ), 11, 3 );
		add_action( 'tcaccordion_item_extra_fields', array( $this, 'render_item_fields' ), 11, 2 );

		// Front end output.
		add_filter( 'tcaccordion_item_title_html', array( $this, 'title_html' ), 10, 3 );
		add_filter( 'tcaccordion_item_li_attributes', array( $this, 'li_attributes' ), 10, 3 );
		add_filter( 'tcaccordion_before_list', array( $this, 'group_bar' ), 10, 5 );

		// Settings (Settings tab) and saving.
		add_action( 'tcaccordion_pro_settings', array( $this, 'render_settings' ), 10 );
		add_action( 'tcaccordion_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Keep the extra item fields when items are read.
	 *
	 * @param array $item Normalized item.
	 * @param array $raw  Raw item as saved.
	 * @return array
	 */
	public function keep_item_data( $item, $raw ) {
		$item['group']       = isset( $raw['group'] ) ? (string) $raw['group'] : '';
		$item['icon']        = isset( $raw['icon'] ) ? (string) $raw['icon'] : '';
		$item['badge']       = isset( $raw['badge'] ) ? (string) $raw['badge'] : '';
		$item['badge_color'] = isset( $raw['badge_color'] ) ? (string) $raw['badge_color'] : 'blue';

		return $item;
	}

	/**
	 * Sanitize the extra item fields on save.
	 *
	 * @param array $clean   Sanitized item.
	 * @param array $raw     Submitted item.
	 * @param int   $post_id Accordion ID.
	 * @return array
	 */
	public function sanitize_item( $clean, $raw, $post_id ) {
		$icon  = isset( $raw['icon'] ) ? sanitize_text_field( $raw['icon'] ) : '';
		$color = isset( $raw['badge_color'] ) ? sanitize_key( $raw['badge_color'] ) : 'blue';

		$clean['group']       = isset( $raw['group'] ) ? mb_substr( sanitize_text_field( $raw['group'] ), 0, 40 ) : '';
		$clean['icon']        = array_key_exists( $icon, self::get_icons() ) ? $icon : '';
		$clean['badge']       = isset( $raw['badge'] ) ? mb_substr( sanitize_text_field( $raw['badge'] ), 0, 24 ) : '';
		$clean['badge_color'] = array_key_exists( $color, self::get_badge_colors() ) ? $color : 'blue';

		return $clean;
	}

	/**
	 * Group, icon and badge fields shown in every item of the editor.
	 *
	 * @param string|int $index Item index used in field names.
	 * @param array      $item  Item data.
	 * @return void
	 */
	public function render_item_fields( $index, $item ) {
		$name   = TCAccordion_Metabox::META_KEY . '[' . $index . ']';
		$group  = isset( $item['group'] ) ? $item['group'] : '';
		$icon   = isset( $item['icon'] ) ? $item['icon'] : '';
		$badge  = isset( $item['badge'] ) ? $item['badge'] : '';
		$color  = isset( $item['badge_color'] ) ? $item['badge_color'] : 'blue';
		?>
		<div class="tcaccordion-pro-item-fields" style="display:flex;flex-wrap:wrap;gap:12px;margin:0 0 12px;">
			<p style="flex:1 1 160px;margin:0;">
				<label><strong><?php esc_html_e( 'Group (optional)', 'accordions-wp' ); ?></strong></label>
				<input type="text" class="widefat" maxlength="40" name="<?php echo esc_attr( $name ); ?>[group]" value="<?php echo esc_attr( $group ); ?>" placeholder="<?php esc_attr_e( 'e.g. Shipping', 'accordions-wp' ); ?>" />
			</p>
			<p style="flex:1 1 160px;margin:0;">
				<label><strong><?php esc_html_e( 'Icon (optional)', 'accordions-wp' ); ?></strong></label>
				<select class="widefat" name="<?php echo esc_attr( $name ); ?>[icon]">
					<option value=""><?php esc_html_e( '— None —', 'accordions-wp' ); ?></option>
					<?php foreach ( self::get_icons() as $class => $label ) : ?>
						<option value="<?php echo esc_attr( $class ); ?>" <?php selected( $icon, $class ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p style="flex:1 1 160px;margin:0;">
				<label><strong><?php esc_html_e( 'Badge text (optional)', 'accordions-wp' ); ?></strong></label>
				<input type="text" class="widefat" maxlength="24" name="<?php echo esc_attr( $name ); ?>[badge]" value="<?php echo esc_attr( $badge ); ?>" placeholder="<?php esc_attr_e( 'e.g. New', 'accordions-wp' ); ?>" />
			</p>
			<p style="flex:1 1 120px;margin:0;">
				<label><strong><?php esc_html_e( 'Badge color', 'accordions-wp' ); ?></strong></label>
				<select class="widefat" name="<?php echo esc_attr( $name ); ?>[badge_color]">
					<?php foreach ( self::get_badge_colors() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $color, $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
		<?php
	}

	/**
	 * Add the item icon before the title and the badge after it.
	 *
	 * @param string $html    Escaped title HTML.
	 * @param array  $item    Normalized item.
	 * @param array  $context Item context.
	 * @return string
	 */
	public function title_html( $html, $item, $context ) {
		$icons  = self::get_icons();
		$colors = self::get_badge_colors();

		if ( ! empty( $item['icon'] ) && isset( $icons[ $item['icon'] ] ) ) {
			$html = '<span class="tc-acc-item-icon" aria-hidden="true"><i class="fa-solid ' . esc_attr( $item['icon'] ) . ' fa-fw"></i></span>' . $html;
		}

		if ( isset( $item['badge'] ) && '' !== trim( (string) $item['badge'] ) ) {
			$color = ( ! empty( $item['badge_color'] ) && isset( $colors[ $item['badge_color'] ] ) ) ? $item['badge_color'] : 'blue';
			$html .= '<span class="tc-acc-badge tc-acc-badge--' . esc_attr( $color ) . '">' . esc_html( $item['badge'] ) . '</span>';
		}

		return $html;
	}

	/**
	 * Mark each item with its group so the filter buttons can show and hide it.
	 *
	 * @param string $attributes Attributes so far.
	 * @param array  $item       Normalized item.
	 * @param array  $context    Item context.
	 * @return string
	 */
	public function li_attributes( $attributes, $item, $context ) {
		$slug = $this->group_slug( isset( $item['group'] ) ? $item['group'] : '' );

		if ( '' !== $slug ) {
			$attributes .= ' data-group="' . esc_attr( $slug ) . '"';
		}

		return $attributes;
	}

	/**
	 * Slug used to match an item with its filter button.
	 *
	 * @param string $group Group name.
	 * @return string
	 */
	private function group_slug( $group ) {
		$group = trim( (string) $group );

		return '' === $group ? '' : urldecode( sanitize_title( $group ) );
	}

	/**
	 * Group filter buttons shown above the list.
	 *
	 * @param string $html    HTML so far.
	 * @param array  $items   Items being rendered.
	 * @param array  $styles  Styles map.
	 * @param array  $context Accordion context.
	 * @param TCAccordion_Renderer $renderer Renderer (reads the settings).
	 * @return string
	 */
	public function group_bar( $html, $items, $styles, $context, $renderer ) {
		unset( $styles, $context );

		if ( 'yes' !== $renderer->get_setting( '_tcacc_group_bar', 'no' ) ) {
			return $html;
		}

		$groups = array();

		foreach ( $items as $item ) {
			$label = isset( $item['group'] ) ? trim( (string) $item['group'] ) : '';
			$slug  = $this->group_slug( $label );

			if ( '' !== $slug && ! isset( $groups[ $slug ] ) ) {
				$groups[ $slug ] = $label;
			}
		}

		if ( count( $groups ) < 2 ) {
			return $html;
		}

		$all_label = trim( (string) $renderer->get_setting( '_tcacc_group_all_label', '' ) );
		$all_label = '' !== $all_label ? $all_label : __( 'All', 'accordions-wp' );

		$html .= '<div class="tc-acc-groups" role="group" aria-label="' . esc_attr__( 'Filter by group', 'accordions-wp' ) . '">';
		$html .= '<button type="button" class="tc-acc-group-btn is-active" data-group="" aria-pressed="true">' . esc_html( $all_label ) . '</button>';

		foreach ( $groups as $slug => $label ) {
			$html .= '<button type="button" class="tc-acc-group-btn" data-group="' . esc_attr( $slug ) . '" aria-pressed="false">' . esc_html( $label ) . '</button>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Settings rows for the group bar.
	 *
	 * @param WP_Post $post Accordion post.
	 * @return void
	 */
	public function render_settings( $post ) {
		$bar       = get_post_meta( $post->ID, '_tcacc_group_bar', true );
		$bar       = ( 'yes' === $bar ) ? 'yes' : 'no';
		$all_label = get_post_meta( $post->ID, '_tcacc_group_all_label', true );
		?>
		<tr valign="top">
			<th scope="row" colspan="2">
				<h3 style="margin:18px 0 0;"><?php esc_html_e( 'Groups / category tabs (Pro)', 'accordions-wp' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Give items a group in the Content tab, then show filter buttons above the accordion so visitors can narrow the list.', 'accordions-wp' ); ?></p>
			</th>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="_tcacc_group_bar"><?php esc_html_e( 'Group filter buttons', 'accordions-wp' ); ?></label></th>
			<td>
				<select name="_tcacc_group_bar" id="_tcacc_group_bar">
					<option value="no" <?php selected( $bar, 'no' ); ?>><?php esc_html_e( 'Hide', 'accordions-wp' ); ?></option>
					<option value="yes" <?php selected( $bar, 'yes' ); ?>><?php esc_html_e( 'Show', 'accordions-wp' ); ?></option>
				</select>
				<input type="text" name="_tcacc_group_all_label" class="regular-text" value="<?php echo esc_attr( $all_label ); ?>" placeholder="<?php esc_attr_e( 'All', 'accordions-wp' ); ?>" />
				<p class="description"><?php esc_html_e( 'The second field changes the text of the "show everything" button. Buttons appear only when at least two groups exist.', 'accordions-wp' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the group settings. The nonce and permissions are checked by the free plugin first.
	 *
	 * @param int $post_id Accordion ID.
	 * @return void
	 */
	public function save_settings( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['_tcacc_group_bar'] ) ) {
			$bar = sanitize_text_field( wp_unslash( $_POST['_tcacc_group_bar'] ) );
			update_post_meta( $post_id, '_tcacc_group_bar', ( 'yes' === $bar ) ? 'yes' : 'no' );
		}

		if ( isset( $_POST['_tcacc_group_all_label'] ) ) {
			update_post_meta( $post_id, '_tcacc_group_all_label', mb_substr( sanitize_text_field( wp_unslash( $_POST['_tcacc_group_all_label'] ) ), 0, 30 ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}

new TCAccordion_Pro_Items();
