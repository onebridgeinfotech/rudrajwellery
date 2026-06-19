<?php
/**
 * Create demo products (like ramyanagendra.com catalog).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo product rows: sku, name, price, stock, featured, categories (slugs), description.
 *
 * @return array
 */
function jwellery_get_demo_products() {
	return array(
		array( 'ER-001', 'Changeable studs', 399, 50, 1, array( 'ear-rings' ), 'Stylish changeable stud earrings for daily wear.' ),
		array( 'ER-002', 'Flower Stud with string earrings', 399, 50, 1, array( 'ear-rings' ), 'Flower stud with matching string earrings.' ),
		array( 'ER-003', 'Panchaloham ear rings butta', 1000, 20, 0, array( 'ear-rings' ), 'Traditional Panchaloham ear rings butta with ruby and pearl detailing.' ),
		array( 'ST-001', 'Panchaloham j studs', 350, 30, 0, array( 'studs' ), 'Panchaloham J-line stud earrings with sparkling stone settings.' ),
		array( 'NK-001', '5 lines Chandraharam', 699, 30, 1, array( 'necklaces' ), 'Traditional five-line Chandraharam necklace.' ),
		array( 'NK-002', 'Pendant with Chandraharam & black beads', 799, 25, 1, array( 'necklaces' ), 'Pendant set with Chandraharam and black beads.' ),
		array( 'NK-003', 'Short black beads', 499, 0, 1, array( 'necklaces' ), 'Short black bead necklace — classic style.' ),
		array( 'NK-004', '2lines long black beads', 799, 40, 0, array( 'necklaces' ), 'Two-line long black beads necklace.' ),
		array( 'NK-005', 'Laxmi kasulu short necklace', 799, 20, 0, array( 'necklaces' ), 'Traditional Laxmi kasulu short necklace with matching earrings — green stone and pearl drops.' ),
		array( 'CK-001', 'Mini chocker', 399, 0, 0, array( 'chockers' ), 'Antique finish mini choker.' ),
		array( 'BG-001', 'Gold kada', 399, 60, 1, array( 'bangles' ), 'Gold-tone kada bangle.' ),
		array( 'LH-001', 'Thali chain (GJ-1)', 399, 35, 1, array( 'long-harams' ), 'Traditional thali chain design GJ-1.' ),
		array( 'HM-001', 'Offer champaswaralu', 399, 45, 0, array( 'handmade-collection', 'ear-rings' ), 'Handmade champaswaralu — limited offer.' ),
		array( 'HM-002', '3lines earchains', 399, 50, 0, array( 'handmade-collection', 'ear-rings' ), 'Handcrafted three-line ear chains.' ),
		array( 'HM-003', 'Gold matilu', 399, 40, 0, array( 'handmade-collection', 'ear-rings' ), 'Gold-tone matilu ear jewelry.' ),
		array( 'HM-004', 'Pink antique pendant chain', 399, 30, 0, array( 'handmade-collection', 'necklaces' ), 'Pink stone antique pendant with chain.' ),
		array( 'HM-005', 'Chain with pearls', 299, 55, 0, array( 'handmade-collection', 'necklaces' ), 'Delicate chain with pearl accents.' ),
		array( 'LC-001', '5 ball black beads', 500, 20, 0, array( 'latest-collection' ), 'Latest five-ball black beads design.' ),
		array( 'LC-002', 'Pink stone Laxmi BB', 500, 15, 0, array( 'latest-collection' ), 'Pink stone Laxmi black beads.' ),
		array( 'IG-001', 'Nakshi kada', 399, 0, 0, array( 'instagram-collection', 'bangles' ), 'As seen on Instagram — Nakshi kada.' ),
		array( 'IG-002', 'Green butta', 399, 0, 0, array( 'instagram-collection', 'ear-rings' ), 'Green butta earrings from Instagram collection.' ),
		array( 'CB-001', 'BB COMBO', 699, 25, 1, array( 'necklaces', 'handmade-collection' ), 'Best-selling BB combo set.' ),
		array( 'CB-002', 'Laxmi kasulu short + bottu mala', 1300, 15, 0, array( 'combo' ), 'Laxmi kasulu short necklace with bottu mala and matching earrings — complete festive combo set.' ),
	);
}

/**
 * Assign WooCommerce categories to a product by slug list.
 *
 * @param int          $product_id Product ID.
 * @param string[]     $cats       Category slugs.
 * @param bool         $replace    Replace existing categories when true.
 */
function jwellery_assign_product_categories( $product_id, $cats, $replace = true ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 || empty( $cats ) || ! is_array( $cats ) ) {
		return;
	}

	$term_ids = array();
	foreach ( $cats as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$term_ids[] = (int) $term->term_id;
		}
	}
	if ( ! $term_ids ) {
		return;
	}

	if ( $replace ) {
		wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
		return;
	}

	$existing = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $existing ) ) {
		$existing = array();
	}
	wp_set_object_terms( $product_id, array_unique( array_merge( $existing, $term_ids ) ), 'product_cat' );
}

/**
 * Create one WooCommerce simple product.
 *
 * @param array $row Product row.
 * @return int|false Product ID.
 */
function jwellery_create_one_demo_product( $row ) {
	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return false;
	}

	list( $sku, $name, $price, $stock, $featured, $cats, $desc ) = $row;

	$existing_ids = function_exists( 'jwellery_get_product_ids_by_sku' )
		? jwellery_get_product_ids_by_sku( $sku )
		: array_filter( array( (int) wc_get_product_id_by_sku( $sku ) ) );

	if ( ! empty( $existing_ids ) ) {
		$existing_id = function_exists( 'jwellery_pick_canonical_product_id' )
			? jwellery_pick_canonical_product_id( $existing_ids )
			: (int) $existing_ids[0];

		if (
			function_exists( 'jwellery_trash_catalog_product_ids' )
			&& count( $existing_ids ) > 1
			&& function_exists( 'jwellery_catalog_sync_is_destructive' )
			&& jwellery_catalog_sync_is_destructive()
		) {
			jwellery_trash_catalog_product_ids( $existing_ids, $existing_id );
		}

		$product = wc_get_product( $existing_id );
		if ( $product ) {
			$changed = false;
			if ( 'publish' !== $product->get_status() ) {
				$product->set_status( 'publish' );
				$changed = true;
			}

			$safe_sync = function_exists( 'jwellery_catalog_sync_is_destructive' ) && ! jwellery_catalog_sync_is_destructive();
			if ( $safe_sync ) {
				if ( $changed ) {
					$product->save();
				}
			} elseif ( ! function_exists( 'jwellery_product_is_admin_managed' ) || ! jwellery_product_is_admin_managed( $existing_id ) ) {
				jwellery_assign_product_categories( $existing_id, $cats, true );
				if ( (string) $product->get_regular_price() !== (string) $price ) {
					$product->set_regular_price( (string) $price );
					$changed = true;
				}
				if ( (string) $product->get_name() !== (string) $name ) {
					$product->set_name( (string) $name );
					$changed = true;
				}
				if ( $changed ) {
					$product->save();
				}
			} elseif ( $changed ) {
				$product->save();
			}
		}

		if ( function_exists( 'jwellery_attach_demo_product_image' ) && ! has_post_thumbnail( $existing_id ) ) {
			jwellery_attach_demo_product_image( $existing_id, $sku, false );
		}
		return $existing_id;
	}

	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_sku( $sku );
	$product->set_regular_price( (string) $price );
	$product->set_description( $desc );
	$product->set_short_description( $desc );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( (int) $stock );
	$product->set_stock_status( $stock > 0 ? 'instock' : 'outofstock' );
	$product->set_featured( (bool) $featured );

	$product_id = $product->save();

	if ( $product_id && ! empty( $cats ) ) {
		jwellery_assign_product_categories( $product_id, $cats, true );
	}

	if ( function_exists( 'jwellery_attach_demo_product_image' ) ) {
		jwellery_attach_demo_product_image( $product_id, $sku );
	}

	return $product_id;
}

/**
 * Create all demo products.
 *
 * @return int Number created.
 */
function jwellery_create_demo_products() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$count = 0;
	$rows  = function_exists( 'jwellery_get_bundled_catalog_rows' )
		? jwellery_get_bundled_catalog_rows()
		: jwellery_get_demo_products();
	foreach ( $rows as $row ) {
		$before = wc_get_product_id_by_sku( $row[0] );
		$id     = jwellery_create_one_demo_product( $row );
		if ( $id && ! $before ) {
			++$count;
		}
	}

	wc_delete_product_transients();
	return $count;
}
