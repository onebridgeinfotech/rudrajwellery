<?php
/**
 * Reference navigation fallback + social links.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reference site menu (Home, About, Shop submenu, Track Order, Contact).
 */
function jwellery_reference_menu_fallback() {
	$shop       = jwellery_get_shop_url();
	$use_mega   = function_exists( 'jwellery_shop_uses_mega_menu' ) && jwellery_shop_uses_mega_menu();
	$shop_class = 'menu-item-has-children' . ( $use_mega ? ' menu-item-has-mega' : '' );
	$pages      = array(
		'about'       => __( 'About', 'jwellery-jewelry' ),
		'track-order' => __( 'Track Order', 'jwellery-jewelry' ),
		'contact'     => __( 'Contact', 'jwellery-jewelry' ),
	);
	?>
	<ul class="jwellery-menu">
		<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'jwellery-jewelry' ); ?></a></li>
		<?php
		$about_url = function_exists( 'jwellery_get_store_page_url' ) ? jwellery_get_store_page_url( 'about' ) : home_url( '/about/' );
		printf(
			'<li><a href="%s">%s</a></li>',
			esc_url( $about_url ),
			esc_html( $pages['about'] )
		);
		?>
		<li class="<?php echo esc_attr( $shop_class ); ?>">
			<a href="<?php echo esc_url( $shop ); ?>" aria-expanded="false"><?php esc_html_e( 'Shop', 'jwellery-jewelry' ); ?></a>
			<?php
			if ( $use_mega && function_exists( 'jwellery_mega_menu_html' ) ) {
				echo jwellery_mega_menu_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} elseif ( function_exists( 'jwellery_shop_submenu_html' ) ) {
				echo jwellery_shop_submenu_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</li>
		<?php
		foreach ( array( 'track-order', 'contact' ) as $slug ) {
			$url = function_exists( 'jwellery_get_store_page_url' ) ? jwellery_get_store_page_url( $slug ) : home_url( '/' . $slug . '/' );
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( $url ),
				esc_html( $pages[ $slug ] )
			);
		}
		?>
	</ul>
	<?php
}

/**
 * Legacy fallback for wp_nav_menu.
 */
function jwellery_fallback_menu() {
	jwellery_reference_menu_fallback();
}
