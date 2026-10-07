<?php
/**
 * Admin Support Page Template View.
 *
 * @package     TCAccordion
 * @subpackage  TCAccordion/Admin/Views
 * @version     3.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="wrap tcaccordion-admin-wrap">
	<h1 class="wp-heading-inline team-manager-admin-support">
		<?php esc_html_e( 'Support', 'accordions-wp' ); ?>
	</h1>

	<div class="postbox team-manager-admin-area" style="padding: 20px; margin-top: 15px;">
		<h2 class="eddpcs-admin-hthree"><?php esc_html_e( 'Support Forum', 'accordions-wp' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: 1: WordPress.org support URL, 2: Themepoints Q&A URL */
				esc_html__( 'If you need any help, please don\'t hesitate to post it on %1$s or %2$s.', 'accordions-wp' ),
				'<a href="' . esc_url( 'https://wordpress.org/support/plugin/accordions-wp' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'WordPress.org', 'accordions-wp' ) . '</a>',
				'<a href="' . esc_url( 'https://themepoints.com/questions-answer/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Themepoints.com', 'accordions-wp' ) . '</a>'
			);
			?>
		</p>
		<br />

		<h2><?php esc_html_e( 'Submit a Review', 'accordions-wp' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: 1: WordPress.org plugin review URL, 2: Contact form URL */
				esc_html__( 'We spend plenty of time developing this plugin and give it freely to make your life easier. If you like this plugin, please %1$s. If you have any problems with the plugin, please %2$s before leaving a review.', 'accordions-wp' ),
				'<a href="' . esc_url( 'https://wordpress.org/plugins/accordions-wp/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'rate it 5 stars', 'accordions-wp' ) . '</a>',
				'<a href="' . esc_url( 'https://themepoints.com/contact/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'let us know', 'accordions-wp' ) . '</a>'
			);
			?>
		</p>
		<br />

		<h2><?php esc_html_e( 'Unlock More Features', 'accordions-wp' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: 1: Premium version URL */
				esc_html__( 'Upgrading to the %s would unlock more amazing features of this plugin.', 'accordions-wp' ),
				'<a href="' . esc_url( 'https://themepoints.com/wp-accordions' ) . '" target="_blank" rel="noopener noreferrer"><span style="color: red; text-decoration: none;">' . esc_html__( 'Premium Version', 'accordions-wp' ) . '</span></a>'
			);
			?>
		</p>
	</div>
</div>