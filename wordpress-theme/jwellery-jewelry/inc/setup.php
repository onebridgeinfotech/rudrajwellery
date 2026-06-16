<?php
/**
 * Theme activation: pages, menus, front page.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/* Theme activation handled in reference-setup.php */

/**
 * Create essential pages.
 */
function jwellery_create_pages() {
	$pages = array(
		'home'             => array( 'title' => 'Home', 'content' => '' ),
		'shop'             => array( 'title' => 'Shop', 'content' => '' ),
		'about'            => array( 'title' => 'About', 'slug' => 'about' ),
		'contact'          => array( 'title' => 'Contact', 'slug' => 'contact' ),
		'track-order'      => array( 'title' => 'Track Order', 'slug' => 'track-order' ),
		'privacy-policy'   => array( 'title' => 'Privacy Policy', 'slug' => 'privacy-policy' ),
		'refund-policy'    => array( 'title' => 'Refund Policy', 'slug' => 'refund-policy' ),
		'shipping-policy'  => array( 'title' => 'Shipping Policy', 'slug' => 'shipping-policy' ),
		'terms-of-service' => array( 'title' => 'Terms of Service', 'slug' => 'terms-of-service' ),
	);

	foreach ( $pages as $key => $page ) {
		$slug = isset( $page['slug'] ) ? $page['slug'] : sanitize_title( $page['title'] );
		$content = isset( $page['content'] ) ? $page['content'] : jwellery_get_default_page_content( $key );

		$existing = function_exists( 'jwellery_find_store_page' ) ? jwellery_find_store_page( $key ) : get_page_by_path( $slug );
		if ( $existing ) {
			update_option( 'jwellery_page_' . $key, $existing->ID );
			if ( $content && function_exists( 'jwellery_should_refresh_page_content' ) && jwellery_should_refresh_page_content( $existing, $key ) ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $content,
					)
				);
			}
			continue;
		}

		$id = wp_insert_post(
			array(
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'jwellery_page_' . $key, $id );
		}
	}
}

/**
 * Create navigation menus.
 */
function jwellery_create_menus() {
	$menu_name = 'Primary Navigation';
	$menu_id   = wp_create_nav_menu( $menu_name );

	if ( is_wp_error( $menu_id ) ) {
		$menu = wp_get_nav_menu_object( $menu_name );
		$menu_id = $menu ? $menu->term_id : 0;
	}

	if ( ! $menu_id ) {
		return;
	}

	$home_id = (int) get_option( 'jwellery_page_home' );
	$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
	if ( ! $shop_id || $shop_id < 0 ) {
		$shop_id = (int) get_option( 'jwellery_page_shop' );
	}

	$items = array(
		array( 'title' => 'Home', 'url' => home_url( '/' ), 'page' => $home_id ),
		array( 'title' => 'About', 'page' => (int) get_option( 'jwellery_page_about' ) ),
		array( 'title' => 'Shop', 'url' => $shop_id ? get_permalink( $shop_id ) : home_url( '/shop/' ) ),
		array( 'title' => 'Track Order', 'page' => (int) get_option( 'jwellery_page_track-order' ) ),
		array( 'title' => 'Contact', 'page' => (int) get_option( 'jwellery_page_contact' ) ),
	);

	foreach ( $items as $item ) {
		if ( ! empty( $item['page'] ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $item['title'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $item['page'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		} elseif ( ! empty( $item['url'] ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $item['title'],
					'menu-item-url'    => $item['url'],
					'menu-item-type'   => 'custom',
					'menu-item-status' => 'publish',
				)
			);
		}
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Set static front page.
 */
function jwellery_set_front_page() {
	$home_id = (int) get_option( 'jwellery_page_home' );
	if ( $home_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
	}
}

/**
 * Admin notice: install WooCommerce + plugin.
 */
function jwellery_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-warning"><p><strong>Jwellery Jewelry:</strong> Please install and activate <a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search' ) ) . '">WooCommerce</a>.</p></div>';
		return;
	}
	if ( ! class_exists( 'JUS_Gateway', false ) ) {
		echo '<div class="notice notice-error"><p><strong>Jwellery Jewelry:</strong> ';
		echo esc_html__( 'Checkout will fail until you upload and activate the Jewelry UPI Store plugin.', 'jwellery-jewelry' );
		echo ' <a href="' . esc_url( admin_url( 'plugin-install.php' ) ) . '">' . esc_html__( 'Install plugin', 'jwellery-jewelry' ) . '</a></p></div>';
		return;
	}

	$wc = function_exists( 'WC' ) ? WC() : null;
	if ( $wc && isset( $wc->payment_gateways ) && is_object( $wc->payment_gateways ) ) {
		$gateways = $wc->payment_gateways()->payment_gateways();
		if ( ! isset( $gateways['jus_manual_upi'] ) || 'yes' !== $gateways['jus_manual_upi']->enabled ) {
			$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jus_manual_upi' );
			echo '<div class="notice notice-error"><p><strong>Jwellery Jewelry:</strong> ';
			echo esc_html__( 'UPI payment is disabled. Customers cannot complete checkout.', 'jwellery-jewelry' );
			echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Enable UPI now', 'jwellery-jewelry' ) . '</a></p></div>';
		}
	}
}
add_action( 'admin_notices', 'jwellery_admin_notices' );

/**
 * Footer menu fallback.
 */
function jwellery_footer_fallback() {
	$pages = array( 'privacy-policy', 'refund-policy', 'shipping-policy', 'terms-of-service', 'contact', 'track-order' );
	echo '<ul class="jwellery-footer-menu">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'jwellery-jewelry' ) . '</a></li>';
	foreach ( $pages as $slug ) {
		$url = function_exists( 'jwellery_get_store_page_url' ) ? jwellery_get_store_page_url( $slug ) : home_url( '/' . $slug . '/' );
		$page = function_exists( 'jwellery_find_store_page' ) ? jwellery_find_store_page( $slug ) : get_page_by_path( $slug );
		$label = $page ? get_the_title( $page ) : ucwords( str_replace( '-', ' ', $slug ) );
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}
