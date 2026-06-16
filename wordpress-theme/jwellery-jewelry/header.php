<?php
/**
 * Header — icon search, nav, cart (reference-style icons).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'jwellery-jewelry' ); ?></a>

<?php
if ( function_exists( 'jwellery_render_announcement_marquee' ) ) {
	jwellery_render_announcement_marquee();
}
?>

<header class="jwellery-header" role="banner">
	<div class="container jwellery-header-inner">
		<div class="jwellery-branding">
			<?php
			if ( function_exists( 'jwellery_render_site_logo' ) ) {
				jwellery_render_site_logo();
			} elseif ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( function_exists( 'jwellery_brand_name' ) ? jwellery_brand_name() : get_bloginfo( 'name' ) ); ?></a>
				<?php
			}
			?>
		</div>

		<button class="jwellery-nav-toggle" type="button" aria-expanded="false" aria-controls="jwellery-primary-nav" aria-label="<?php esc_attr_e( 'Menu', 'jwellery-jewelry' ); ?>">
			<?php
			if ( function_exists( 'jwellery_icon_svg' ) ) {
				echo jwellery_icon_svg( 'menu', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				?>
				<span></span><span></span><span></span>
				<?php
			}
			?>
		</button>

		<nav id="jwellery-primary-nav" class="jwellery-nav" aria-label="<?php esc_attr_e( 'Primary', 'jwellery-jewelry' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'jwellery-menu',
						'container'      => false,
						'fallback_cb'    => false,
					)
				);
			} else {
				jwellery_reference_menu_fallback();
			}
			?>
		</nav>

		<div class="jwellery-header-actions">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<div class="jwellery-header-icons">
					<?php
					if ( function_exists( 'jwellery_header_search_toggle' ) ) {
						jwellery_header_search_toggle();
					}
					if ( function_exists( 'jwellery_header_wishlist_icon' ) ) {
						jwellery_header_wishlist_icon();
					}
					if ( function_exists( 'jwellery_header_account_icon' ) ) {
						jwellery_header_account_icon();
					}
					if ( function_exists( 'jwellery_cart_toggle_button' ) ) {
						jwellery_cart_toggle_button();
					} elseif ( function_exists( 'jwellery_cart_icon_link' ) ) {
						jwellery_cart_icon_link();
					} else {
						jwellery_cart_link();
					}
					?>
				</div>
				<a class="jwellery-btn jwellery-btn-shop" href="<?php echo esc_url( jwellery_get_shop_url() ); ?>"><?php esc_html_e( 'Shop Now', 'jwellery-jewelry' ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<?php
	if ( function_exists( 'jwellery_header_search_panel' ) ) {
		jwellery_header_search_panel();
	}
	?>
</header>

<main id="primary" class="jwellery-main">
