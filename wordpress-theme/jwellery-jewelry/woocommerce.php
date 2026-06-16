<?php
/**
 * WooCommerce wrapper template.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

get_header();
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
get_footer();
