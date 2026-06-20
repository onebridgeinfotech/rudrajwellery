<?php
/**
 * Sync bundled catalog products to WooCommerce (deploy + admin).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether this product was edited in wp-admin (preserve name, price, image, categories).
 *
 * @param int $product_id Product ID.
 * @return bool
 */
/**
 * Default demo placeholder title from bundled catalog (not a real storefront name).
 *
 * @param string $name Product title.
 * @return bool
 */
function jwellery_catalog_is_generic_product_name( $name ) {
	return (bool) preg_match( '/^Jewelry item \d+$/i', trim( (string) $name ) );
}

/**
 * When false (default), deploy sync only creates missing SKUs and missing images.
 *
 * @return bool
 */
function jwellery_catalog_sync_is_destructive() {
	return (bool) apply_filters( 'jwellery_catalog_sync_destructive', false );
}

/**
 * Whether deploy/sync may overwrite an existing product's name, price, or categories.
 * Default false — wp-admin is always the source of truth for live products.
 *
 * @param int $product_id Product ID (0 = new product).
 * @return bool
 */
function jwellery_catalog_may_overwrite_product_data( $product_id = 0 ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return true;
	}

	return (bool) apply_filters( 'jwellery_catalog_overwrite_product_data', false, $product_id );
}

function jwellery_product_is_admin_managed( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return false;
	}

	return (bool) get_post_meta( $product_id, '_jwellery_admin_managed', true );
}

/**
 * Mark product as manually managed in wp-admin.
 *
 * @param int $product_id Product ID.
 */
function jwellery_mark_product_admin_managed( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return;
	}

	update_post_meta( $product_id, '_jwellery_admin_managed', current_time( 'mysql' ) );
}

/**
 * Track admin product saves so deploy sync does not overwrite storefront data.
 *
 * @param int $product_id Product ID.
 */
function jwellery_on_product_admin_save( $product_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( defined( 'JWELLERY_CATALOG_SYNC' ) && JWELLERY_CATALOG_SYNC ) {
		return;
	}
	if ( ! is_admin() || ! current_user_can( 'edit_product', $product_id ) ) {
		return;
	}

	jwellery_mark_product_admin_managed( $product_id );
}
add_action( 'woocommerce_update_product', 'jwellery_on_product_admin_save', 20, 1 );
add_action( 'woocommerce_new_product', 'jwellery_on_product_admin_save', 20, 1 );


/**
 * Mark every live catalog product as admin-managed so deploy never overwrites wp-admin data.
 */
function jwellery_bootstrap_admin_managed_catalog() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return;
	}

	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft', 'pending', 'private' ) ) ) as $product ) {
		jwellery_mark_product_admin_managed( (int) $product->get_id() );
	}
}

/**
 * How many days back to treat WhatsApp uploads as "current" for the storefront.
 *
 * @return int
 */
function jwellery_whatsapp_catalog_recent_days() {
	return 2;
}

/**
 * Earliest WhatsApp stamp date to keep (excludes older demo uploads).
 *
 * @return string Y-m-d
 */
function jwellery_whatsapp_catalog_min_date() {
	return apply_filters( 'jwellery_whatsapp_catalog_min_date', '2026-06-15' );
}

/**
 * Whether a WhatsApp stamp falls on or after a date.
 *
 * @param string $stamp  Stamp from whatsapp-catalog.json.
 * @param string $min_date Y-m-d.
 * @return bool
 */
function jwellery_whatsapp_stamp_on_or_after( $stamp, $min_date ) {
	$stamp = (string) $stamp;
	if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2})/', $stamp, $matches ) ) {
		return false;
	}

	return strtotime( $matches[1] . ' 00:00:00' ) >= strtotime( $min_date . ' 00:00:00' );
}

/**
 * Whether a WhatsApp filename stamp is within the rolling recent-day window.
 *
 * @param string $stamp Stamp from whatsapp-catalog.json (e.g. 2026-06-16_at_2.27.56_PM).
 * @return bool
 */
function jwellery_whatsapp_stamp_is_recent( $stamp ) {
	$stamp = (string) $stamp;
	if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2})/', $stamp, $matches ) ) {
		return false;
	}

	$days   = max( 1, (int) jwellery_whatsapp_catalog_recent_days() );
	$cutoff = strtotime( '-' . $days . ' days', strtotime( wp_date( 'Y-m-d' ) . ' 00:00:00' ) );
	$item   = strtotime( $matches[1] . ' 00:00:00' );

	return $item >= $cutoff;
}

/**
 * Whether a WhatsApp upload belongs on the storefront (recent window or June batch).
 *
 * @param string $stamp Stamp from whatsapp-catalog.json.
 * @return bool
 */
function jwellery_whatsapp_stamp_in_storefront( $stamp ) {
	if ( jwellery_whatsapp_stamp_is_recent( $stamp ) ) {
		return true;
	}

	return jwellery_whatsapp_stamp_on_or_after( $stamp, jwellery_whatsapp_catalog_min_date() );
}

/**
 * SKUs from whatsapp-catalog.json within the last N days (storefront window).
 *
 * @param int|null $days Days back; defaults to jwellery_whatsapp_catalog_recent_days().
 * @return string[]
 */
function jwellery_get_whatsapp_skus_within_days( $days = null ) {
	$days = null === $days ? (int) jwellery_whatsapp_catalog_recent_days() : max( 1, (int) $days );
	$cutoff = strtotime( '-' . $days . ' days', strtotime( wp_date( 'Y-m-d' ) . ' 00:00:00' ) );
	$skus   = array();

	foreach ( jwellery_load_whatsapp_catalog_json() as $item ) {
		if ( empty( $item['sku'] ) || empty( $item['stamp'] ) ) {
			continue;
		}
		if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2})/', (string) $item['stamp'], $matches ) ) {
			continue;
		}
		if ( strtotime( $matches[1] . ' 00:00:00' ) >= $cutoff ) {
			$skus[] = (string) $item['sku'];
		}
	}

	return array_values( array_unique( $skus ) );
}

/**
 * Raw rows from whatsapp-catalog.json.
 *
 * @return array<int, array<string, mixed>>
 */
function jwellery_load_whatsapp_catalog_json() {
	static $items = null;
	if ( null !== $items ) {
		return $items;
	}

	$items = array();
	$file  = JWELLERY_THEME_DIR . '/assets/demo-products/whatsapp-catalog.json';
	if ( ! is_readable( $file ) ) {
		return $items;
	}

	$decoded = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $decoded ) ) {
		return $items;
	}

	return $decoded;
}

/**
 * All product IDs with a given SKU (handles duplicate rows in postmeta).
 *
 * @param string $sku Product SKU.
 * @return int[]
 */
function jwellery_get_product_ids_by_sku( $sku ) {
	global $wpdb;

	$sku = (string) $sku;
	if ( '' === $sku ) {
		return array();
	}

	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT pm.post_id FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_sku' AND pm.meta_value = %s
			AND p.post_type = 'product'
			AND p.post_status NOT IN ('trash','auto-draft')",
			$sku
		)
	);

	return array_values( array_unique( array_map( 'intval', (array) $ids ) ) );
}

/**
 * Fingerprint featured image bytes (detect duplicate photos across SKUs).
 *
 * @param int $product_id Product ID.
 * @return string
 */
function jwellery_product_image_fingerprint( $product_id ) {
	$product_id = (int) $product_id;
	$image_id   = (int) get_post_thumbnail_id( $product_id );
	if ( $image_id <= 0 ) {
		return '';
	}

	$file = get_attached_file( $image_id );
	if ( $file && is_readable( $file ) ) {
		$hash = @md5_file( $file );
		return $hash ? (string) $hash : '';
	}

	return 'attachment-' . $image_id;
}

/**
 * Rank products when picking the single canonical row for a SKU/image.
 *
 * @param int $product_id Product ID.
 * @return float
 */
function jwellery_score_catalog_product( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return 0;
	}

	$score = 0;
	if ( 'publish' === $product->get_status() ) {
		$score += 100;
	}
	if ( function_exists( 'jwellery_product_is_admin_managed' ) && jwellery_product_is_admin_managed( $product_id ) ) {
		$score += 500;
	}
	if ( ! jwellery_catalog_is_generic_product_name( $product->get_name() ) ) {
		$score += 1000;
	}
	if ( function_exists( 'jwellery_product_has_image' ) && jwellery_product_has_image( $product ) ) {
		$score += 30;
	}

	$allowed = array_flip( jwellery_get_active_catalog_skus() );
	$sku     = (string) $product->get_sku();
	if ( $sku && isset( $allowed[ $sku ] ) ) {
		$score += 20;
	}

	$modified = $product->get_date_modified();
	if ( $modified ) {
		$score += (float) strtotime( $modified->date( 'c' ) ) / 1000000;
	}

	return $score;
}

/**
 * Pick the best product ID to keep from duplicates.
 *
 * @param int[] $ids Product IDs.
 * @return int
 */
function jwellery_pick_canonical_product_id( $ids ) {
	$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
	if ( empty( $ids ) ) {
		return 0;
	}
	if ( 1 === count( $ids ) ) {
		return $ids[0];
	}

	$best_id = $ids[0];
	$best    = -1;
	foreach ( $ids as $id ) {
		$score = jwellery_score_catalog_product( $id );
		if ( $score > $best ) {
			$best    = $score;
			$best_id = $id;
		}
	}

	return (int) $best_id;
}

/**
 * Move duplicate product posts to trash.
 *
 * @param int[] $ids     Product IDs.
 * @param int   $keep_id ID to keep.
 * @return int
 */
function jwellery_trash_catalog_product_ids( $ids, $keep_id ) {
	$keep_id = (int) $keep_id;
	$count   = 0;

	foreach ( (array) $ids as $id ) {
		$id = (int) $id;
		if ( $id <= 0 || $id === $keep_id ) {
			continue;
		}
		if ( 'trash' === get_post_status( $id ) ) {
			continue;
		}
		$product = wc_get_product( $id );
		if ( $product && ! jwellery_catalog_is_generic_product_name( $product->get_name() ) ) {
			continue;
		}
		if ( wp_trash_post( $id ) ) {
			++$count;
		}
	}

	return $count;
}

/**
 * Catalog row for a SKU from bundled JSON.
 *
 * @param string $sku Product SKU.
 * @return array|null
 */
function jwellery_get_catalog_row_by_sku( $sku ) {
	foreach ( jwellery_get_bundled_catalog_rows() as $row ) {
		if ( $row[0] === $sku ) {
			return $row;
		}
	}
	return null;
}

/**
 * Remove duplicate SKUs / duplicate images (never changes names or prices).
 *
 * @return array{trashed: int, prices: int, names: int}
 */
function jwellery_deduplicate_and_sync_catalog_products() {
	$result = array(
		'trashed' => 0,
		'prices'  => 0,
		'names'   => 0,
	);

	if ( ! class_exists( 'WooCommerce' ) || ! jwellery_catalog_sync_is_destructive() ) {
		return $result;
	}

	$allowed = array_flip( jwellery_get_active_catalog_skus() );
	$by_sku  = array();

	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft', 'pending', 'private' ) ) ) as $product ) {
		$sku = (string) $product->get_sku();
		if ( $sku ) {
			$by_sku[ $sku ][] = (int) $product->get_id();
		}
	}

	foreach ( $by_sku as $sku => $ids ) {
		if ( count( $ids ) <= 1 ) {
			continue;
		}
		$keep = jwellery_pick_canonical_product_id( $ids );
		$result['trashed'] += jwellery_trash_catalog_product_ids( $ids, $keep );
	}

	$by_image = array();
	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft' ) ) ) as $product ) {
		$id = (int) $product->get_id();
		$fp = jwellery_product_image_fingerprint( $id );
		if ( $fp ) {
			$by_image[ $fp ][] = $id;
		}
	}

	foreach ( $by_image as $ids ) {
		if ( count( $ids ) <= 1 ) {
			continue;
		}
		$keep = jwellery_pick_canonical_product_id( $ids );
		$result['trashed'] += jwellery_trash_catalog_product_ids( $ids, $keep );
	}

	foreach ( jwellery_get_active_catalog_skus() as $sku ) {
		$ids = jwellery_get_product_ids_by_sku( $sku );
		if ( empty( $ids ) || count( $ids ) <= 1 ) {
			continue;
		}

		$keep_id = jwellery_pick_canonical_product_id( $ids );
		$result['trashed'] += jwellery_trash_catalog_product_ids( $ids, $keep_id );

		$product = wc_get_product( $keep_id );
		if ( $product && 'publish' !== $product->get_status() ) {
			$product->set_status( 'publish' );
			$product->save();
		}
	}

	wc_delete_product_transients();
	return $result;
}

/**
 * Trash every published product that is not the canonical row for its SKU.
 *
 * @return int
 */
function jwellery_enforce_canonical_storefront() {
	if ( ! class_exists( 'WooCommerce' ) || ! jwellery_catalog_sync_is_destructive() ) {
		return 0;
	}

	$allowed = array_flip( jwellery_get_active_catalog_skus() );
	$retired = array_flip( jwellery_get_retired_catalog_skus() );
	$trashed = 0;

	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft', 'pending', 'private' ) ) ) as $product ) {
		$sku = (string) $product->get_sku();
		$id  = (int) $product->get_id();

		if ( ! $sku || ! isset( $allowed[ $sku ] ) || isset( $retired[ $sku ] ) ) {
			if ( 'trash' !== $product->get_status() ) {
				if ( wp_trash_post( $id ) ) {
					++$trashed;
				}
			}
			continue;
		}

		$ids  = jwellery_get_product_ids_by_sku( $sku );
		$keep = jwellery_pick_canonical_product_id( $ids );
		if ( $keep && $id !== $keep && 'trash' !== $product->get_status() ) {
			if ( ! jwellery_catalog_is_generic_product_name( $product->get_name() ) ) {
				continue;
			}
			if ( wp_trash_post( $id ) ) {
				++$trashed;
			}
		}
	}

	wc_delete_product_transients();
	return $trashed;
}

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
	foreach ( jwellery_load_whatsapp_catalog_json() as $item ) {
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
 * SKUs that should stay visible on shop/homepage (JSON + live wp-admin catalog).
 *
 * @return string[]
 */
function jwellery_get_active_catalog_skus() {
	static $skus = null;
	if ( null !== $skus ) {
		return $skus;
	}

	$skus = array();
	foreach ( jwellery_get_whatsapp_catalog_products() as $row ) {
		$skus[] = (string) $row[0];
	}

	if ( class_exists( 'WooCommerce' ) ) {
		foreach ( wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => -1,
			)
		) as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$sku = (string) $product->get_sku();
			if ( $sku && preg_match( '/^WP-\d+$/', $sku ) ) {
				$skus[] = $sku;
			}
		}
	}

	return array_values( array_unique( $skus ) );
}

/**
 * WooCommerce product IDs for the active catalog SKUs.
 * Only returns IDs for published (not trashed/draft) products.
 *
 * @return int[]
 */
function jwellery_get_active_catalog_product_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}

	$ids = array();
	foreach ( jwellery_get_active_catalog_skus() as $sku ) {
		// jwellery_get_product_ids_by_sku already filters out trash/auto-draft via SQL.
		$product_ids = function_exists( 'jwellery_get_product_ids_by_sku' )
			? jwellery_get_product_ids_by_sku( $sku )
			: array();
		if ( empty( $product_ids ) ) {
			// wc_get_product_id_by_sku does NOT filter by post_status, so check explicitly.
			$fallback_id = (int) wc_get_product_id_by_sku( $sku );
			if ( $fallback_id > 0 && 'publish' === get_post_status( $fallback_id ) ) {
				$product_ids = array( $fallback_id );
			}
		}
		if ( empty( $product_ids ) ) {
			continue;
		}
		$id = function_exists( 'jwellery_pick_canonical_product_id' )
			? jwellery_pick_canonical_product_id( $product_ids )
			: (int) $product_ids[0];
		// Final guard: only include published products.
		if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * All demo product rows (static + WhatsApp batch), limited to active SKUs only.
 *
 * @return array<int, array>
 */
function jwellery_get_bundled_catalog_rows() {
	$allowed = array_flip( jwellery_get_active_catalog_skus() );
	$rows    = array_merge( jwellery_get_demo_products(), jwellery_get_whatsapp_catalog_products() );

	return array_values(
		array_filter(
			$rows,
			function ( $row ) use ( $allowed ) {
				return isset( $allowed[ $row[0] ] );
			}
		)
	);
}

/**
 * Create missing products and refresh bundled images.
 *
 * @param array{force_images?: bool} $opts Sync options.
 * @return array{created: int, images: int}
 */
function jwellery_sync_catalog_products( $opts = array() ) {
	$result = array(
		'created'  => 0,
		'images'   => 0,
		'trashed'  => 0,
		'prices'   => 0,
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		return $result;
	}

	if ( function_exists( 'jwellery_deduplicate_and_sync_catalog_products' ) ) {
		$dedupe = jwellery_deduplicate_and_sync_catalog_products();
		$result['trashed'] = (int) $dedupe['trashed'];
		$result['prices']  = (int) $dedupe['prices'];
	}

	if ( function_exists( 'jwellery_create_reference_categories' ) ) {
		jwellery_create_reference_categories();
	}

	foreach ( jwellery_get_bundled_catalog_rows() as $row ) {
		$sku    = $row[0];
		$before = function_exists( 'jwellery_get_product_ids_by_sku' )
			? jwellery_get_product_ids_by_sku( $sku )
			: array( wc_get_product_id_by_sku( $sku ) );
		$before = array_filter( (array) $before );
		$id     = jwellery_create_one_demo_product( $row );
		if ( $id && empty( $before ) ) {
			++$result['created'];
		}
	}

	if ( function_exists( 'jwellery_deduplicate_and_sync_catalog_products' ) && jwellery_catalog_sync_is_destructive() ) {
		$dedupe = jwellery_deduplicate_and_sync_catalog_products();
		$result['trashed'] += (int) $dedupe['trashed'];
		$result['prices']  += (int) $dedupe['prices'];
	}

	if ( jwellery_catalog_sync_is_destructive() && function_exists( 'jwellery_enforce_canonical_storefront' ) ) {
		$result['trashed'] += jwellery_enforce_canonical_storefront();
	}

	if ( jwellery_catalog_sync_is_destructive() ) {
		if ( function_exists( 'jwellery_hide_products_without_bundled_images' ) ) {
			jwellery_hide_products_without_bundled_images();
		}
		if ( function_exists( 'jwellery_cleanup_catalog_storefront' ) ) {
			jwellery_cleanup_catalog_storefront();
		}
	}

	$force_images = ! empty( $opts['force_images'] );
	if ( $force_images && function_exists( 'jwellery_refresh_bundled_catalog_images' ) ) {
		$result['images'] = jwellery_refresh_bundled_catalog_images();
	} elseif ( function_exists( 'jwellery_repair_missing_product_images' ) ) {
		$result['images'] = jwellery_repair_missing_product_images();
	}

	if ( function_exists( 'jwellery_assign_category_thumbnails' ) ) {
		jwellery_assign_category_thumbnails( false );
	}

	if ( function_exists( 'jwellery_bootstrap_admin_managed_catalog' ) ) {
		jwellery_bootstrap_admin_managed_catalog();
	}

	wc_delete_product_transients();
	return $result;
}

/**
 * Record theme version after deploy. Does not queue catalog sync (wp-admin owns product data).
 */
function jwellery_schedule_catalog_sync_if_needed() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$synced_ver = (string) get_option( 'jwellery_catalog_sync_version', '' );
	if ( $synced_ver === JWELLERY_THEME_VERSION ) {
		return;
	}

	update_option( 'jwellery_catalog_sync_version', JWELLERY_THEME_VERSION, false );
	delete_option( 'jwellery_catalog_sync_pending' );
	delete_option( 'jwellery_catalog_sync_offset' );
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
		'trashed' => 0,
		'prices'  => 0,
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		return $result;
	}

	if ( ! defined( 'JWELLERY_CATALOG_SYNC' ) ) {
		define( 'JWELLERY_CATALOG_SYNC', true );
	}

	$rows           = jwellery_get_bundled_catalog_rows();
	$result['total'] = count( $rows );
	$slice          = array_slice( $rows, $offset, $limit );

	if ( 0 === $offset && function_exists( 'jwellery_create_reference_categories' ) ) {
		jwellery_create_reference_categories();
	}

	if ( 0 === $offset && function_exists( 'jwellery_deduplicate_and_sync_catalog_products' ) && jwellery_catalog_sync_is_destructive() ) {
		$dedupe = jwellery_deduplicate_and_sync_catalog_products();
		$result['trashed'] = (int) $dedupe['trashed'];
		$result['prices']  = (int) $dedupe['prices'];
	}

	foreach ( $slice as $row ) {
		$sku    = $row[0];
		$before = function_exists( 'jwellery_get_product_ids_by_sku' )
			? jwellery_get_product_ids_by_sku( $sku )
			: array_filter( array( (int) wc_get_product_id_by_sku( $sku ) ) );
		$id     = jwellery_create_one_demo_product( $row );
		if ( $id && empty( $before ) ) {
			++$result['created'];
		}
		if ( $id && function_exists( 'jwellery_attach_demo_product_image' ) && jwellery_sku_has_bundled_image( $sku ) ) {
			if ( jwellery_product_is_admin_managed( $id ) && has_post_thumbnail( $id ) ) {
				continue;
			}
			if ( jwellery_attach_demo_product_image( $id, $sku, false ) ) {
				++$result['images'];
			}
		}
	}

	$result['offset'] = $offset + count( $slice );
	$result['done']   = $result['offset'] >= $result['total'];

	if ( $result['done'] ) {
		if ( function_exists( 'jwellery_deduplicate_and_sync_catalog_products' ) && jwellery_catalog_sync_is_destructive() ) {
			$dedupe = jwellery_deduplicate_and_sync_catalog_products();
			$result['trashed'] += (int) $dedupe['trashed'];
			$result['prices']  += (int) $dedupe['prices'];
		}
		if ( function_exists( 'jwellery_bootstrap_admin_managed_catalog' ) ) {
			jwellery_bootstrap_admin_managed_catalog();
		}
		if ( jwellery_catalog_sync_is_destructive() && function_exists( 'jwellery_enforce_canonical_storefront' ) ) {
			$result['trashed'] += jwellery_enforce_canonical_storefront();
		}
		if ( jwellery_catalog_sync_is_destructive() ) {
			if ( function_exists( 'jwellery_hide_products_without_bundled_images' ) ) {
				jwellery_hide_products_without_bundled_images();
			}
			if ( function_exists( 'jwellery_cleanup_catalog_storefront' ) ) {
				jwellery_cleanup_catalog_storefront();
			}
		}
		if ( function_exists( 'jwellery_assign_category_thumbnails' ) ) {
			jwellery_assign_category_thumbnails( false );
		}
		wc_delete_product_transients();
	}

	return $result;
}

/**
 * Untrash all WooCommerce products removed by catalog dedupe.
 *
 * @return int
 */
function jwellery_restore_trashed_catalog_products() {
	global $wpdb;

	$ids = $wpdb->get_col(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'trash'"
	);

	$restored = 0;
	foreach ( (array) $ids as $id ) {
		$id = (int) $id;
		if ( $id > 0 && wp_untrash_post( $id ) ) {
			jwellery_mark_product_admin_managed( $id );
			++$restored;
		}
	}

	return $restored;
}

/**
 * Keep one row per WP-* SKU; trash generic placeholder duplicates only.
 *
 * @return int
 */
function jwellery_resolve_catalog_duplicate_skus() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$by_sku  = array();
	$trashed = 0;

	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft', 'pending', 'private' ) ) ) as $product ) {
		$sku = (string) $product->get_sku();
		if ( $sku && preg_match( '/^WP-\d+$/', $sku ) ) {
			$by_sku[ $sku ][] = (int) $product->get_id();
		}
	}

	foreach ( $by_sku as $ids ) {
		if ( count( $ids ) <= 1 ) {
			continue;
		}
		$keep    = jwellery_pick_canonical_product_id( $ids );
		$trashed += jwellery_trash_catalog_product_ids( $ids, $keep );
	}

	return $trashed;
}

/**
 * Snapshot-backed names/prices recovered from older live HTML exports.
 *
 * @return array<string, array{name?: string, price?: string}>
 */
function jwellery_get_recovered_catalog_overrides() {
	static $rows = null;
	if ( null !== $rows ) {
		return $rows;
	}

	$path = get_template_directory() . '/assets/demo-products/recovered-catalog-overrides.json';
	if ( ! is_readable( $path ) ) {
		$rows = array();
		return $rows;
	}

	$raw = (string) file_get_contents( $path );
	if ( strncmp( $raw, "\xFF\xFE", 2 ) === 0 ) {
		$raw = mb_convert_encoding( $raw, 'UTF-8', 'UTF-16LE' );
	} elseif ( strncmp( $raw, "\xFE\xFF", 2 ) === 0 ) {
		$raw = mb_convert_encoding( $raw, 'UTF-8', 'UTF-16BE' );
	}

	$decoded = json_decode( $raw, true );
	$rows    = is_array( $decoded ) ? $decoded : array();
	return $rows;
}

/**
 * Re-apply recovered catalog names/prices (manual Store Setup only — never on deploy).
 *
 * @return array{names: int, prices: int}
 */
function jwellery_apply_recovered_catalog_overrides() {
	$result = array(
		'names'  => 0,
		'prices' => 0,
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		return $result;
	}

	foreach ( jwellery_get_recovered_catalog_overrides() as $sku => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$ids = jwellery_get_product_ids_by_sku( (string) $sku );
		if ( empty( $ids ) ) {
			continue;
		}

		$product_id = jwellery_pick_canonical_product_id( $ids );
		$product    = wc_get_product( $product_id );
		if ( ! $product ) {
			continue;
		}

		$changed = false;
		$name    = isset( $row['name'] ) ? (string) $row['name'] : '';
		$price   = isset( $row['price'] ) ? (string) $row['price'] : '';

		if ( $name && jwellery_catalog_is_generic_product_name( $product->get_name() ) ) {
			$product->set_name( $name );
			$changed = true;
			++$result['names'];
		}

		if ( $price && ( jwellery_catalog_is_generic_product_name( $product->get_name() ) || '399' === (string) $product->get_regular_price() ) && (string) $price !== (string) $product->get_regular_price() ) {
			$product->set_regular_price( $price );
			$changed = true;
			++$result['prices'];
		}

		if ( $changed ) {
			$product->save();
			jwellery_mark_product_admin_managed( $product_id );
		}
	}

	return $result;
}

/**
 * Restore wp-admin catalog edits from trash and resolve duplicate SKUs safely.
 *
 * @return array{restored: int, trashed_dupes: int, marked: int}
 */
function jwellery_run_catalog_restore() {
	$result = array(
		'restored'      => 0,
		'trashed_dupes' => 0,
		'marked'        => 0,
		'names'         => 0,
		'prices'        => 0,
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		return $result;
	}

	if ( ! defined( 'JWELLERY_CATALOG_SYNC' ) ) {
		define( 'JWELLERY_CATALOG_SYNC', true );
	}

	$result['restored']      = jwellery_restore_trashed_catalog_products();
	$result['trashed_dupes'] = jwellery_resolve_catalog_duplicate_skus();

	if ( function_exists( 'jwellery_bootstrap_admin_managed_catalog' ) ) {
		jwellery_bootstrap_admin_managed_catalog();
		$result['marked'] = (int) wp_count_posts( 'product' )->publish + (int) wp_count_posts( 'product' )->draft;
	}

	wc_delete_product_transients();

	if ( function_exists( 'jwellery_purge_hosting_cache' ) ) {
		jwellery_purge_hosting_cache();
	}

	return $result;
}

/**
 * HTTP catalog restore (same key file as sync). Key file: uploads/jwellery-catalog-sync.key
 */
function jwellery_catalog_restore_http_endpoint() {
	if ( empty( $_GET['jwellery_catalog_restore'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		wp_die( esc_html__( 'Catalog restore key missing.', 'jwellery-jewelry' ), '', array( 'response' => 403 ) );
	}
	$expected = trim( (string) file_get_contents( $key_file ) );
	$given    = sanitize_text_field( wp_unslash( $_GET['jwellery_catalog_restore'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $expected || ! hash_equals( $expected, $given ) ) {
		wp_die( esc_html__( 'Forbidden.', 'jwellery-jewelry' ), '', array( 'response' => 403 ) );
	}
	@unlink( $key_file );

	@set_time_limit( 120 );
	@ini_set( 'memory_limit', '512M' );

	wp_send_json_success( jwellery_run_catalog_restore() );
}
add_action( 'template_redirect', 'jwellery_catalog_restore_http_endpoint', 0 );

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
		if ( function_exists( 'jwellery_bootstrap_admin_managed_catalog' ) ) {
			jwellery_bootstrap_admin_managed_catalog();
		}
		update_option( 'jwellery_catalog_sync_version', JWELLERY_THEME_VERSION, false );
		delete_option( 'jwellery_catalog_sync_pending' );
		delete_option( 'jwellery_catalog_sync_offset' );
		update_option( 'jwellery_theme_deploy_version', JWELLERY_THEME_VERSION, false );
		if ( function_exists( 'jwellery_enforce_canonical_storefront' ) && jwellery_catalog_sync_is_destructive() ) {
			jwellery_enforce_canonical_storefront();
		}
		if ( jwellery_catalog_sync_is_destructive() ) {
			if ( function_exists( 'jwellery_hide_products_without_bundled_images' ) ) {
				jwellery_hide_products_without_bundled_images();
			}
			if ( function_exists( 'jwellery_cleanup_catalog_storefront' ) ) {
				jwellery_cleanup_catalog_storefront();
			}
		}
		if ( function_exists( 'jwellery_assign_category_thumbnails' ) ) {
			jwellery_assign_category_thumbnails( false );
		}
		if ( function_exists( 'jwellery_purge_hosting_cache' ) ) {
			jwellery_purge_hosting_cache();
		}
	} else {
		update_option( 'jwellery_catalog_sync_offset', (int) $batch['offset'], false );
	}

	wp_send_json_success( $batch );
}
add_action( 'template_redirect', 'jwellery_catalog_sync_http_endpoint', 0 );
