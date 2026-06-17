<?php
/**
 * Homepage section visibility helpers.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a homepage section should render.
 *
 * @param string $key Section key.
 * @return bool
 */
function jwellery_home_section_enabled( $key ) {
	$map = array(
		'trust_strip'      => 'jwellery_home_enable_trust_strip',
		'owner'            => 'jwellery_owner_enable',
		'budget'           => 'jwellery_home_enable_budget',
		'top_categories'   => 'jwellery_home_enable_top_categories',
		'category_browse'  => 'jwellery_home_enable_category_browse',
		'handmade'         => 'jwellery_home_enable_handmade',
		'steal_deals'      => 'jwellery_home_enable_steal_deals',
		'new_collection'   => 'jwellery_home_enable_new_collection',
		'product_of_day'   => 'jwellery_home_enable_product_of_day',
		'follow_journey'   => 'jwellery_home_enable_follow_journey',
		'instagram'        => 'jwellery_home_enable_instagram',
		'all_products'     => 'jwellery_home_enable_all_products',
		'testimonials'     => 'jwellery_home_enable_testimonials',
		'faq'              => 'jwellery_home_enable_faq',
	);

	if ( ! isset( $map[ $key ] ) ) {
		return true;
	}

	return (bool) get_theme_mod( $map[ $key ], jwellery_home_section_default( $key ) );
}

/**
 * Default on/off per section.
 *
 * @param string $key Section key.
 * @return bool
 */
function jwellery_home_section_default( $key ) {
	$defaults = array(
		'trust_strip'      => true,
		'owner'            => true,
		'budget'           => true,
		'top_categories'   => false,
		'category_browse'  => true,
		'handmade'         => true,
		'steal_deals'      => true,
		'new_collection'   => true,
		'product_of_day'   => true,
		'follow_journey'   => true,
		'instagram'        => true,
		'all_products'     => true,
		'testimonials'     => true,
		'faq'              => true,
	);
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : true;
}

/**
 * Category has published in-stock products.
 *
 * @param string $slug Category slug.
 * @return bool
 */
function jwellery_category_has_products( $slug ) {
	if ( ! taxonomy_exists( 'product_cat' ) || ! function_exists( 'wc_get_products' ) ) {
		return false;
	}
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) {
		return false;
	}
	$products = function_exists( 'jwellery_get_products_for_display' )
		? jwellery_get_products_for_display(
			array(
				'limit'        => 1,
				'status'       => 'publish',
				'stock_status' => 'instock',
				'category'     => array( $slug ),
			),
			1,
			1
		)
		: wc_get_products(
			array(
				'limit'        => 1,
				'status'       => 'publish',
				'stock_status' => 'instock',
				'category'     => array( $slug ),
			)
		);
	return ! empty( $products );
}
