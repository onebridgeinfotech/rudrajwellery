<?php
/**
 * Sync bundled catalog products to WooCommerce (deploy + admin).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extra products from whatsapp-catalog.json (one image per new WhatsApp stamp).
 *
 * @return array<int, array>
 */
function jwellery_get_whatsapp_catalog_products() {
	static $rows = null;
	if ( null !== $rows ) {
		return $rows;
	}

	$rows = array();
	$file = JWELLERY_THEME_DIR . '/assets/demo-products/whatsapp-catalog.json';
	if ( ! is_readable( $file ) ) {
		return $rows;
	}

	$decoded = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $decoded ) ) {
		return $rows;
	}

	foreach ( $decoded as $item ) {
		if ( empty( $item['sku'] ) || empty( $item['name'] ) ) {
			continue;
		}
		$cats = ! empty( $item['categories'] ) && is_array( $item['categories'] )
			? $item['categories']
			: array( 'latest-collection' );
		$rows[] = array(
			(string) $item['sku'],
			(string) $item['name'],
			isset( $item['price'] ) ? (int) $item['price'] : 399,
			isset( $item['stock'] ) ? (int) $item['stock'] : 10,
			! empty( $item['featured'] ) ? 1 : 0,
			$cats,
			isset( $item['description'] ) ? (string) $item['description'] : '',
		);
	}

	return $rows;
}

/**
 * All demo product rows (static + WhatsApp batch).
 *
 * @return array<int, array>
 */
function jwellery_get_bundled_catalog_rows() {
	return array_merge( jwellery_get_demo_products(), jwellery_get_whatsapp_catalog_products() );
}

/**
 * Create missing products and refresh bundled images.
 *
 * @return array{created: int, images: int}
 */
function jwellery_sync_catalog_products() {
	$result = array(
		'created' => 0,
		'images'  => 0,
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		return $result;
	}

	if ( function_exists( 'jwellery_create_reference_categories' ) ) {
		jwellery_create_reference_categories();
	}

	foreach ( jwellery_get_bundled_catalog_rows() as $row ) {
		$sku    = $row[0];
		$before = wc_get_product_id_by_sku( $sku );
		$id     = jwellery_create_one_demo_product( $row );
		if ( $id && ! $before ) {
			++$result['created'];
		}
	}

	if ( function_exists( 'jwellery_import_all_catalog_images' ) ) {
		$result['images'] = jwellery_import_all_catalog_images( false );
	}

	wc_delete_product_transients();
	return $result;
}

/**
 * Queue catalog sync when theme version changes (HTTP endpoint or Store Setup only).
 */
function jwellery_schedule_catalog_sync_if_needed() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$synced_ver = (string) get_option( 'jwellery_catalog_sync_version', '' );
	if ( $synced_ver === JWELLERY_THEME_VERSION ) {
		return;
	}
	$pending = (string) get_option( 'jwellery_catalog_sync_pending', '' );
	if ( $pending !== JWELLERY_THEME_VERSION ) {
		update_option( 'jwellery_catalog_sync_pending', JWELLERY_THEME_VERSION, false );
	}
}
add_action( 'after_setup_theme', 'jwellery_schedule_catalog_sync_if_needed', 25 );

/**
 * Sync one batch of catalog rows (avoids timeouts on shared hosting).
 *
 * @param int $offset Start index.
 * @param int $limit  Max rows per batch.
 * @return array{offset: int, total: int, done: bool, created: int, images: int}
 */
function jwellery_sync_catalog_products_batch( $offset = 0, $limit = 8 ) {
	$result = array(
		'offset'  => (int) $offset,
		'total'   => 0,
		'done'    => false,
		'created' => 0,
		'images'  => 0,
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		return $result;
	}

	$rows           = jwellery_get_bundled_catalog_rows();
	$result['total'] = count( $rows );
	$slice          = array_slice( $rows, $offset, $limit );

	if ( 0 === $offset && function_exists( 'jwellery_create_reference_categories' ) ) {
		jwellery_create_reference_categories();
	}

	foreach ( $slice as $row ) {
		$sku    = $row[0];
		$before = wc_get_product_id_by_sku( $sku );
		$id     = jwellery_create_one_demo_product( $row );
		if ( $id && ! $before ) {
			++$result['created'];
		}
		if ( $id && function_exists( 'jwellery_attach_demo_product_image' ) && ! has_post_thumbnail( $id ) ) {
			if ( jwellery_attach_demo_product_image( $id, $sku, false ) ) {
				++$result['images'];
			}
		}
	}

	$result['offset'] = $offset + count( $slice );
	$result['done']   = $result['offset'] >= $result['total'];

	if ( $result['done'] ) {
		if ( function_exists( 'jwellery_repair_missing_product_images' ) ) {
			jwellery_repair_missing_product_images();
		}
		wc_delete_product_transients();
	}

	return $result;
}

/**
 * HTTP catalog sync (bypasses admin-ajax / page cache). Key file: uploads/jwellery-catalog-sync.key
 */
function jwellery_catalog_sync_http_endpoint() {
	if ( empty( $_GET['jwellery_catalog_sync'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
		define( 'LSCACHE_NO_CACHE', true );
	}
	nocache_headers();

	$key_file = WP_CONTENT_DIR . '/uploads/jwellery-catalog-sync.key';
	if ( ! is_readable( $key_file ) ) {
		wp_die( esc_html__( 'Catalog sync key missing.', 'jwellery-jewelry' ), '', array( 'response' => 403 ) );
	}
	$expected = trim( (string) file_get_contents( $key_file ) );
	$given    = sanitize_text_field( wp_unslash( $_GET['jwellery_catalog_sync'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $expected || ! hash_equals( $expected, $given ) ) {
		wp_die( esc_html__( 'Forbidden.', 'jwellery-jewelry' ), '', array( 'response' => 403 ) );
	}
	@unlink( $key_file );

	@set_time_limit( 120 );
	@ini_set( 'memory_limit', '512M' );

	$offset = isset( $_GET['offset'] ) ? max( 0, (int) $_GET['offset'] ) : (int) get_option( 'jwellery_catalog_sync_offset', 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$batch  = jwellery_sync_catalog_products_batch( $offset, 8 );

	if ( $batch['done'] ) {
		update_option( 'jwellery_catalog_sync_version', JWELLERY_THEME_VERSION, false );
		delete_option( 'jwellery_catalog_sync_pending' );
		delete_option( 'jwellery_catalog_sync_offset' );
		update_option( 'jwellery_theme_deploy_version', JWELLERY_THEME_VERSION, false );
		if ( function_exists( 'jwellery_purge_hosting_cache' ) ) {
			jwellery_purge_hosting_cache();
		}
	} else {
		update_option( 'jwellery_catalog_sync_offset', (int) $batch['offset'], false );
	}

	wp_send_json_success( $batch );
}
add_action( 'template_redirect', 'jwellery_catalog_sync_http_endpoint', 0 );
