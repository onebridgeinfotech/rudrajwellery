<?php
/**
 * Shop archive — grouped catalog on main shop; default loop on categories/filters.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

if ( function_exists( 'jwellery_is_main_shop_catalog' ) && jwellery_is_main_shop_catalog() ) {
	// Intro, category browse, and grouped sections render on woocommerce_before_main_content.
} elseif ( woocommerce_product_loop() ) {
	do_action( 'woocommerce_before_shop_loop' );
	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'total' ) ) {
		while ( have_posts() ) {
			the_post();
			do_action( 'woocommerce_shop_loop' );
			wc_get_template_part( 'content', 'product' );
		}
	}

	woocommerce_product_loop_end();
	do_action( 'woocommerce_after_shop_loop' );
} else {
	do_action( 'woocommerce_no_products_found' );
}

do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
