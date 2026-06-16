<?php
/**
 * Store page UI — hero, images, structured sections.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hero / section icon per store page (no product photos).
 *
 * @return array<string, string>
 */
function jwellery_store_page_icon_map() {
	return array(
		'about'            => 'quality',
		'contact'          => 'support',
		'track-order'      => 'truck',
		'privacy-policy'   => 'lock',
		'refund-policy'    => 'cart',
		'shipping-policy'  => 'truck',
		'terms-of-service' => 'lock',
	);
}

/**
 * Detect store page key for the current query.
 *
 * @param int|null $post_id Post ID.
 * @return string Empty if not a store page.
 */
function jwellery_current_store_page_key( $post_id = null ) {
	if ( ! is_page() && ! $post_id ) {
		return '';
	}

	$post = $post_id ? get_post( $post_id ) : get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	foreach ( jwellery_store_content_pages() as $key => $slug ) {
		if ( $post->post_name === $slug ) {
			return $key;
		}
	}

	if ( function_exists( 'jwellery_find_store_page' ) ) {
		foreach ( jwellery_store_content_pages() as $key => $slug ) {
			$found = jwellery_find_store_page( $key );
			if ( $found && (int) $found->ID === (int) $post->ID ) {
				return $key;
			}
		}
	}

	return '';
}

/**
 * Hero icon name for a store page.
 *
 * @param string $key Page key.
 * @return string
 */
function jwellery_page_hero_icon_name( $key ) {
	$map = jwellery_store_page_icon_map();
	return isset( $map[ $key ] ) ? $map[ $key ] : 'quality';
}

/**
 * Large hero icon markup.
 *
 * @param string $key Page key.
 * @param int    $size Pixel size.
 * @return string
 */
function jwellery_page_hero_icon_html( $key, $size = 52 ) {
	return jwellery_page_icon_html( jwellery_page_hero_icon_name( $key ), $size );
}

/**
 * Section icon badge (medium).
 *
 * @param string $icon Icon key.
 * @param string $class Extra class.
 * @return string
 */
function jwellery_page_section_icon_html( $icon, $class = '' ) {
	return sprintf(
		'<div class="jwellery-page-section-icon %s">%s</div>',
		esc_attr( trim( $class ) ),
		jwellery_page_icon_html( $icon, 36 )
	);
}

/**
 * Short hero subtitle per page type.
 *
 * @param string $key Page key.
 * @return string
 */
function jwellery_page_hero_subtitle( $key ) {
	$brand = function_exists( 'jwellery_brand_name' ) ? jwellery_brand_name() : get_bloginfo( 'name' );

	$map = array(
		'about'            => __( 'Crafted elegance for every occasion — tradition meets everyday style.', 'jwellery-jewelry' ),
		'contact'          => __( 'We are here to help with orders, styling, and custom requests.', 'jwellery-jewelry' ),
		'track-order'      => __( 'Follow your order from payment verification to doorstep delivery.', 'jwellery-jewelry' ),
		'privacy-policy'   => __( 'How we protect your data when you shop with us.', 'jwellery-jewelry' ),
		'refund-policy'    => __( 'Clear guidelines for returns, cancellations, and UPI refunds.', 'jwellery-jewelry' ),
		'shipping-policy'  => __( 'All-India delivery with secure packaging and tracking.', 'jwellery-jewelry' ),
		'terms-of-service' => sprintf( __( 'Shopping terms for %s — please read before ordering.', 'jwellery-jewelry' ), $brand ),
	);

	return isset( $map[ $key ] ) ? $map[ $key ] : '';
}

/**
 * Inline icon for page cards.
 *
 * @param string $name Icon key.
 * @param int    $size Size.
 * @return string
 */
function jwellery_page_icon_html( $name, $size = 24 ) {
	return function_exists( 'jwellery_icon_svg' ) ? jwellery_icon_svg( $name, $size ) : '';
}

/**
 * Offer card for About page (icon only).
 *
 * @param string $icon  Icon key.
 * @param string $title Title.
 * @param string $desc  Description.
 * @return string
 */
function jwellery_page_offer_card_html( $icon, $title, $desc ) {
	return sprintf(
		'<article class="jwellery-page-offer-card jwellery-animate-item"><div class="jwellery-page-offer-icon">%s</div><h4>%s</h4><p>%s</p></article>',
		jwellery_page_icon_html( $icon, 28 ),
		esc_html( $title ),
		esc_html( $desc )
	);
}

/**
 * Track step with icon.
 *
 * @param string $num   Step number label.
 * @param string $icon  Icon key.
 * @param string $title Step title.
 * @param string $desc  Step description.
 * @return string
 */
function jwellery_page_track_step_html( $num, $icon, $title, $desc ) {
	return sprintf(
		'<div class="jwellery-track-step jwellery-animate-item"><span class="jwellery-track-step-icon">%s</span><span class="jwellery-track-step-num">%s</span><strong>%s</strong><span>%s</span></div>',
		jwellery_page_icon_html( $icon, 24 ),
		esc_html( $num ),
		esc_html( $title ),
		esc_html( $desc )
	);
}

/**
 * Policy section card.
 *
 * @param string $title   Heading.
 * @param string $body    HTML body.
 * @param string $icon    Icon name.
 * @return string
 */
function jwellery_page_policy_card_html( $title, $body, $icon = 'quality' ) {
	return sprintf(
		'<section class="jwellery-page-card jwellery-animate-item"><div class="jwellery-page-card-icon">%s</div><h3>%s</h3><div class="jwellery-page-card-body">%s</div></section>',
		jwellery_page_icon_html( $icon, 26 ),
		esc_html( $title ),
		$body
	);
}

/**
 * Body classes for store pages.
 *
 * @param array $classes Classes.
 * @return array
 */
function jwellery_store_page_body_classes( $classes ) {
	$key = jwellery_current_store_page_key();
	if ( $key ) {
		$classes[] = 'jwellery-store-page';
		$classes[] = 'jwellery-store-page--' . sanitize_html_class( $key );
	}
	return $classes;
}
add_filter( 'body_class', 'jwellery_store_page_body_classes' );

/**
 * Enqueue page design assets.
 */
function jwellery_enqueue_page_design_assets() {
	if ( ! is_page() ) {
		return;
	}
	$ver = function_exists( 'jwellery_asset_version' ) ? jwellery_asset_version() : JWELLERY_THEME_VERSION;
	wp_enqueue_style( 'jwellery-page-design', JWELLERY_THEME_URI . '/assets/css/page-design.css', array( 'jwellery-buttons' ), $ver );
}
add_action( 'wp_enqueue_scripts', 'jwellery_enqueue_page_design_assets', 30 );
