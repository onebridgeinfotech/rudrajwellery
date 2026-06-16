<?php
/**
 * Mobile & tablet bottom icon bar (professional ecommerce pattern).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Mobile bar cart icon + badge HTML (for AJAX fragments).
 *
 * @return string
 */
function jwellery_mobile_bar_cart_icon_wrap_html() {
	if ( ! function_exists( 'jwellery_icon_svg' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	$count = WC()->cart->get_cart_contents_count();
	$html  = jwellery_icon_svg( 'cart', 22 );
	if ( $count > 0 ) {
		$html .= '<span class="jwellery-mobile-bar-badge">' . (int) $count . '</span>';
	}
	return $html;
}

/**
 * Bottom navigation for touch devices.
 */
function jwellery_mobile_icon_bar() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'jwellery_icon_svg' ) ) {
		return;
	}

	$wa = function_exists( 'jwellery_whatsapp_url' ) ? jwellery_whatsapp_url() : '';
	?>
	<nav class="jwellery-mobile-bar" aria-label="<?php esc_attr_e( 'Store shortcuts', 'jwellery-jewelry' ); ?>">
		<a class="jwellery-mobile-bar-item" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo jwellery_icon_svg( 'home', 22 ); // phpcs:ignore ?>
			<span><?php esc_html_e( 'Home', 'jwellery-jewelry' ); ?></span>
		</a>
		<a class="jwellery-mobile-bar-item" href="<?php echo esc_url( jwellery_get_shop_url() ); ?>">
			<?php echo jwellery_icon_svg( 'shop', 22 ); // phpcs:ignore ?>
			<span><?php esc_html_e( 'Shop', 'jwellery-jewelry' ); ?></span>
		</a>
		<button type="button" class="jwellery-mobile-bar-item jwellery-mobile-bar-search" data-mobile-search aria-label="<?php esc_attr_e( 'Search', 'jwellery-jewelry' ); ?>">
			<?php echo jwellery_icon_svg( 'search', 22 ); // phpcs:ignore ?>
			<span><?php esc_html_e( 'Search', 'jwellery-jewelry' ); ?></span>
		</button>
		<?php if ( $wa ) : ?>
			<a class="jwellery-mobile-bar-item jwellery-mobile-bar-item--wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo jwellery_icon_svg( 'whatsapp', 22 ); // phpcs:ignore ?>
				<span><?php esc_html_e( 'Chat', 'jwellery-jewelry' ); ?></span>
			</a>
		<?php else : ?>
			<a class="jwellery-mobile-bar-item" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
				<?php echo jwellery_icon_svg( 'user', 22 ); // phpcs:ignore ?>
				<span><?php esc_html_e( 'Account', 'jwellery-jewelry' ); ?></span>
			</a>
		<?php endif; ?>
		<button type="button" class="jwellery-mobile-bar-item jwellery-mobile-bar-cart jwellery-cart-toggle" aria-expanded="false" aria-controls="jwellery-cart-drawer" aria-label="<?php esc_attr_e( 'Cart', 'jwellery-jewelry' ); ?>">
			<span class="jwellery-mobile-bar-icon-wrap">
				<?php echo jwellery_mobile_bar_cart_icon_wrap_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<span><?php esc_html_e( 'Cart', 'jwellery-jewelry' ); ?></span>
		</button>
	</nav>
	<?php
}
add_action( 'wp_footer', 'jwellery_mobile_icon_bar', 12 );

/**
 * Body class when mobile bar is active.
 *
 * @param array $classes Classes.
 * @return array
 */
function jwellery_mobile_bar_body_class( $classes ) {
	if ( class_exists( 'WooCommerce' ) ) {
		$classes[] = 'jwellery-has-mobile-bar';
	}
	return $classes;
}
add_filter( 'body_class', 'jwellery_mobile_bar_body_class' );
