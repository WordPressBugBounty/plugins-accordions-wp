<?php
/**
 * Upgrade previews shown in the free version.
 *
 * These are static pictures of the Pro settings with an "Upgrade to Pro" message on top.
 * They contain no working controls and no Pro code. They only help people see what Pro adds.
 * When Pro is active, the Pro plugin code prints the real settings instead.
 *
 * @package TCAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pro feature previews for the accordion editor.
 */
class TCAccordion_Admin_Previews {

	/**
	 * Where visitors go to upgrade.
	 *
	 * @return string
	 */
	public static function upgrade_url() {
		if ( function_exists( 'tc_acc_fs' ) ) {
			$fs = tc_acc_fs();

			if ( is_object( $fs ) && method_exists( $fs, 'get_upgrade_url' ) ) {
				return $fs->get_upgrade_url();
			}
		}

		return 'https://themepoints.com/wp-accordions/';
	}

	/**
	 * Picture of the Pro settings with an upgrade message on top (printed once, in the Settings tab).
	 *
	 * @return void
	 */
	public static function render_pro_teaser() {
		$features = array(
			array( 'dashicons-filter', __( 'Groups / category filter tabs', 'accordions-wp' ), __( 'Let visitors narrow a long FAQ with one click.', 'accordions-wp' ) ),
			array( 'dashicons-database', __( 'Dynamic content', 'accordions-wp' ), __( 'Build items from posts, custom post types or ACF fields.', 'accordions-wp' ) ),
			array( 'dashicons-chart-bar', __( 'Analytics', 'accordions-wp' ), __( 'See which questions are opened most, with Google Analytics events.', 'accordions-wp' ) ),
			array( 'dashicons-star-filled', __( 'Item icons and badges', 'accordions-wp' ), __( 'Highlight "New" or "Popular" questions.', 'accordions-wp' ) ),
			array( 'dashicons-cart', __( 'WooCommerce category rules', 'accordions-wp' ), __( 'Show the right FAQ on the right products.', 'accordions-wp' ) ),
			array( 'dashicons-layout', __( 'Tabs and Horizontal layouts, nested accordions', 'accordions-wp' ), __( 'More ways to present your content.', 'accordions-wp' ) ),
		);
		?>
		<tr valign="top" class="tcacc-pro-teaser-row">
			<td colspan="2" style="padding-left:0;padding-right:0;">
				<div class="tcacc-pro-teaser">

					<!-- A picture of the Pro settings (not interactive) -->
					<div class="tcacc-pro-teaser__preview" aria-hidden="true">
						<div class="tcacc-mock-section">
							<strong><?php esc_html_e( 'Groups / category tabs', 'accordions-wp' ); ?></strong>
							<div class="tcacc-mock-row">
								<span class="tcacc-mock-label"><?php esc_html_e( 'Group filter buttons', 'accordions-wp' ); ?></span>
								<span class="tcacc-mock-control"><?php esc_html_e( 'Show', 'accordions-wp' ); ?></span>
							</div>
							<div class="tcacc-mock-pills">
								<span class="is-active"><?php esc_html_e( 'All', 'accordions-wp' ); ?></span>
								<span><?php esc_html_e( 'Shipping', 'accordions-wp' ); ?></span>
								<span><?php esc_html_e( 'Returns', 'accordions-wp' ); ?></span>
								<span><?php esc_html_e( 'Payments', 'accordions-wp' ); ?></span>
							</div>
						</div>

						<div class="tcacc-mock-section">
							<strong><?php esc_html_e( 'Dynamic content', 'accordions-wp' ); ?></strong>
							<div class="tcacc-mock-row">
								<span class="tcacc-mock-label"><?php esc_html_e( 'Content source', 'accordions-wp' ); ?></span>
								<span class="tcacc-mock-control"><?php esc_html_e( 'Posts / custom post type', 'accordions-wp' ); ?></span>
							</div>
							<div class="tcacc-mock-row">
								<span class="tcacc-mock-label"><?php esc_html_e( 'Number of items', 'accordions-wp' ); ?></span>
								<span class="tcacc-mock-control">10</span>
							</div>
						</div>

						<div class="tcacc-mock-section">
							<strong><?php esc_html_e( 'Analytics', 'accordions-wp' ); ?></strong>
							<div class="tcacc-mock-row">
								<span class="tcacc-mock-label"><?php esc_html_e( 'Most opened items', 'accordions-wp' ); ?></span>
							</div>
							<div class="tcacc-mock-bars">
								<span style="width:92%;"></span>
								<span style="width:64%;"></span>
								<span style="width:38%;"></span>
							</div>
						</div>

						<div class="tcacc-mock-section">
							<strong><?php esc_html_e( 'WooCommerce category rules', 'accordions-wp' ); ?></strong>
							<div class="tcacc-mock-row">
								<span class="tcacc-mock-label"><?php esc_html_e( 'Show only in categories', 'accordions-wp' ); ?></span>
								<span class="tcacc-mock-control"><?php esc_html_e( 'Clothing, Shoes', 'accordions-wp' ); ?></span>
							</div>
						</div>
					</div>

					<!-- The message people actually read -->
					<div class="tcacc-pro-teaser__overlay">
						<div class="tcacc-pro-teaser__card">
							<span class="tcacc-pro-teaser__badge"><?php esc_html_e( 'PRO', 'accordions-wp' ); ?></span>
							<h3><?php esc_html_e( 'Do more with Accordions Pro', 'accordions-wp' ); ?></h3>
							<ul class="tcacc-pro-teaser__list">
								<?php foreach ( $features as $feature ) : ?>
									<li>
										<span class="dashicons <?php echo esc_attr( $feature[0] ); ?>" aria-hidden="true"></span>
										<span><strong><?php echo esc_html( $feature[1] ); ?></strong><br /><?php echo esc_html( $feature[2] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
							<a class="button button-primary button-hero" href="<?php echo esc_url( self::upgrade_url() ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Upgrade to Pro', 'accordions-wp' ); ?>
							</a>
						</div>
					</div>

				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Small locked strip shown inside every item in the free version.
	 *
	 * @return void
	 */
	public static function render_item_teaser() {
		?>
		<div class="tcaccordion-nested-field tcacc-pro-item-teaser">
			<span class="tcacc-pro-item-teaser__fields" aria-hidden="true">
				<span class="tcacc-mock-field"><?php esc_html_e( 'Group', 'accordions-wp' ); ?></span>
				<span class="tcacc-mock-field"><?php esc_html_e( 'Icon', 'accordions-wp' ); ?></span>
				<span class="tcacc-mock-field"><?php esc_html_e( 'Badge', 'accordions-wp' ); ?></span>
				<span class="tcacc-mock-field"><?php esc_html_e( 'Nested accordion', 'accordions-wp' ); ?></span>
			</span>
			<span class="tcacc-pro-item-teaser__text">
				<small class="tc-pro-badge"><?php esc_html_e( 'PRO', 'accordions-wp' ); ?></small>
				<?php esc_html_e( 'Add a group, icon, badge or nested accordion to each item.', 'accordions-wp' ); ?>
				<a href="<?php echo esc_url( self::upgrade_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to Pro', 'accordions-wp' ); ?></a>
			</span>
		</div>
		<?php
	}
}
