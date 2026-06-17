<?php
/**
 * WooCommerce wrapper template.
 *
 * Main shop uses grouped homepage-style sections; categories use the default loop.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'jwellery_is_main_shop_catalog' ) && jwellery_is_main_shop_catalog() ) {
	do_action( 'woocommerce_before_main_content' );
	do_action( 'woocommerce_after_main_content' );
} else {
	?>
	<div class="container jwellery-page-content">
		<?php
		if ( function_exists( 'woocommerce_content' ) ) {
			woocommerce_content();
		} else {
			echo '<p>' . esc_html__( 'Please install and activate WooCommerce.', 'jwellery-jewelry' ) . '</p>';
		}
		?>
	</div>
	<?php
}

get_footer();
