<?php
/**
 * My Account — app shell layout.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	return;
}
?>

<div class="jwellery-uda jwellery-uda--member jwellery-uda--animate">
	<nav class="jwellery-uda__crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'jwellery-jewelry' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'jwellery-jewelry' ); ?></a>
		<span aria-hidden="true">/</span>
		<span><?php esc_html_e( 'My Account', 'jwellery-jewelry' ); ?></span>
	</nav>
	<div class="jwellery-uda__shell">
		<?php do_action( 'woocommerce_account_navigation' ); ?>
		<main class="jwellery-uda__main" id="jwellery-account-main">
			<?php
			if ( function_exists( 'jwellery_uda_mobile_nav' ) ) {
				jwellery_uda_mobile_nav();
			}
			?>
			<div class="jwellery-uda__panel woocommerce-MyAccount-content">
				<?php do_action( 'woocommerce_account_content' ); ?>
			</div>
		</main>
	</div>
</div>
