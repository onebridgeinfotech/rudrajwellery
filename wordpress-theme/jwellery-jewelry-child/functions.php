<?php
/**
 * Jwellery Jewelry Child Theme — Kadence child.
 *
 * @package JwelleryJewelryChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue parent and child styles.
 */
function jwellery_child_enqueue_styles() {
	$parent = 'kadence';
	wp_enqueue_style( $parent . '-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme( $parent )->get( 'Version' ) );
	wp_enqueue_style(
		'jwellery-jewelry-child',
		get_stylesheet_uri(),
		array( $parent . '-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'jwellery_child_enqueue_styles', 20 );

/**
 * WooCommerce: products per row on shop.
 *
 * @return int
 */
function jwellery_products_per_row() {
	return 4;
}
add_filter( 'loop_shop_columns', 'jwellery_products_per_row' );
