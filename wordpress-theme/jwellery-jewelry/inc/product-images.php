<?php
/**
 * Attach demo product images from theme bundle or reference CDN.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Image URL map (SKU => image URL) from bundled JSON.
 *
 * @return array<string, array{handle?:string,title?:string,image:string}>
 */
function jwellery_get_product_image_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}

	$map  = array();
	$file = JWELLERY_THEME_DIR . '/assets/demo-products/images-map.json';
	if ( ! is_readable( $file ) ) {
		return $map;
	}

	$decoded = json_decode( (string) file_get_contents( $file ), true );
	if ( is_array( $decoded ) ) {
		$map = $decoded;
	}

	return $map;
}

/**
 * Bundled image paths for a SKU (primary first, then gallery -2, -3, …).
 *
 * @param string $sku Product SKU.
 * @return string[]
 */
function jwellery_get_bundled_image_paths( $sku ) {
	$paths = array();
	$dir   = JWELLERY_THEME_DIR . '/assets/demo-products/';

	foreach ( array( 'jpg', 'jpeg', 'png', 'webp' ) as $ext ) {
		$path = $dir . $sku . '.' . $ext;
		if ( is_readable( $path ) ) {
			$paths[] = $path;
			break;
		}
	}

	for ( $i = 2; $i <= 5; $i++ ) {
		foreach ( array( 'jpg', 'jpeg', 'png', 'webp' ) as $ext ) {
			$path = $dir . $sku . '-' . $i . '.' . $ext;
			if ( is_readable( $path ) ) {
				$paths[] = $path;
				break;
			}
		}
	}

	return $paths;
}

/**
 * Local image path for SKU if bundled in theme.
 *
 * @param string $sku Product SKU.
 * @return string|false
 */
function jwellery_get_bundled_image_path( $sku ) {
	$paths = jwellery_get_bundled_image_paths( $sku );
	return $paths ? $paths[0] : false;
}

/**
 * Remove product featured image and gallery attachments.
 *
 * @param int $product_id Product ID.
 */
function jwellery_clear_product_images( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return;
	}

	$thumb_id = (int) get_post_thumbnail_id( $product_id );
	if ( $thumb_id ) {
		wp_delete_attachment( $thumb_id, true );
		delete_post_thumbnail( $product_id );
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return;
	}

	$gallery = $product->get_gallery_image_ids();
	foreach ( $gallery as $attach_id ) {
		wp_delete_attachment( (int) $attach_id, true );
	}
	$product->set_gallery_image_ids( array() );
	$product->save();
}

/**
 * Upload file from disk into media library.
 *
 * @param string $path    Absolute file path.
 * @param string $sku     SKU for attachment title.
 * @param int    $post_id Parent post ID.
 * @return int|false Attachment ID.
 */
function jwellery_upload_image_from_path( $path, $sku, $post_id = 0 ) {
	if ( ! is_readable( $path ) ) {
		return false;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$filename = basename( $path );
	$upload   = wp_upload_bits( $filename, null, (string) file_get_contents( $path ) );
	if ( ! empty( $upload['error'] ) ) {
		return false;
	}

	$wp_filetype = wp_check_filetype( $filename, null );
	$attachment  = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title'     => sanitize_file_name( $sku ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);
	$attach_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return false;
	}

	wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $upload['file'] ) );
	return (int) $attach_id;
}

/**
 * Sideload image from URL into media library.
 *
 * @param string $url     Image URL.
 * @param string $sku     SKU for description.
 * @param int    $post_id Parent post ID.
 * @return int|false Attachment ID.
 */
function jwellery_sideload_image_from_url( $url, $sku, $post_id = 0 ) {
	if ( ! $url || ! wp_http_validate_url( $url ) ) {
		return false;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 30 );
	if ( is_wp_error( $tmp ) ) {
		return false;
	}

	$file_array = array(
		'name'     => sanitize_file_name( $sku . '.jpg' ),
		'tmp_name' => $tmp,
	);
	$attach_id = media_handle_sideload( $file_array, $post_id, $sku );
	if ( is_wp_error( $attach_id ) ) {
		@unlink( $tmp );
		return false;
	}

	return (int) $attach_id;
}

/**
 * Whether a product has a real featured image (not WooCommerce placeholder).
 *
 * @param WC_Product|int $product Product object or ID.
 * @return bool
 */
function jwellery_product_has_image( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return false;
	}
	$image_id = (int) $product->get_image_id();
	if ( $image_id <= 0 ) {
		return false;
	}
	return ! jwellery_attachment_is_invalid_product_image( $image_id );
}

/**
 * Whether an attachment is a logo / placeholder (not a product photo).
 *
 * @param int $attachment_id Attachment ID.
 * @return bool
 */
function jwellery_attachment_is_invalid_product_image( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 ) {
		return true;
	}

	$file = get_attached_file( $attachment_id );
	if ( $file ) {
		$base = strtolower( basename( (string) $file ) );
		if ( false !== strpos( $base, 'rudra-logo' )
			|| false !== strpos( $base, 'woocommerce-placeholder' )
			|| false !== strpos( $base, 'placeholder' )
			|| preg_match( '/^wp-027\.(jpe?g|png|webp)$/i', $base ) ) {
			return true;
		}
	}

	$url = wp_get_attachment_url( $attachment_id );
	if ( $url ) {
		$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		if ( false !== strpos( $path, 'rudra-logo' )
			|| false !== strpos( $path, 'woocommerce-placeholder' )
			|| preg_match( '#/wp-027\.(jpe?g|png|webp)$#i', $path ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Set product featured image (and gallery) from bundle or remote URL.
 *
 * @param int    $product_id Product ID.
 * @param string $sku        Product SKU.
 * @param bool   $force      Replace existing images when true.
 * @return bool
 */
function jwellery_attach_demo_product_image( $product_id, $sku, $force = false ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return false;
	}

	if ( function_exists( 'jwellery_product_is_admin_managed' ) && jwellery_product_is_admin_managed( $product_id ) ) {
		if ( has_post_thumbnail( $product_id ) ) {
			return true;
		}
		$force = false;
	}

	$bundled = jwellery_get_bundled_image_paths( $sku );

	if ( ! $force && has_post_thumbnail( $product_id ) ) {
		return true;
	}

	if ( ! $bundled ) {
		return (bool) has_post_thumbnail( $product_id );
	}

	$attach_ids = array();
	foreach ( $bundled as $index => $path ) {
		$label = $index ? $sku . '-' . ( $index + 1 ) : $sku;
		$id    = jwellery_upload_image_from_path( $path, $label, $product_id );
		if ( $id ) {
			$attach_ids[] = $id;
		}
	}

	if ( ! $attach_ids ) {
		return (bool) has_post_thumbnail( $product_id );
	}

	if ( $force && has_post_thumbnail( $product_id ) ) {
		jwellery_clear_product_images( $product_id );
	}

	set_post_thumbnail( $product_id, $attach_ids[0] );

	$gallery = array_slice( $attach_ids, 1 );
	if ( $gallery ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product->set_gallery_image_ids( $gallery );
			$product->save();
		}
	}

	return true;
}

/**
 * Fill in missing thumbnails from bundle or CDN (no forced replace).
 *
 * @return int Number of products repaired.
 */
function jwellery_repair_missing_product_images() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$count = 0;
	$rows  = function_exists( 'jwellery_get_bundled_catalog_rows' )
		? jwellery_get_bundled_catalog_rows()
		: jwellery_get_demo_products();
	foreach ( $rows as $row ) {
		$sku = $row[0];
		$id  = wc_get_product_id_by_sku( $sku );
		if ( ! $id || has_post_thumbnail( $id ) ) {
			continue;
		}
		if ( jwellery_attach_demo_product_image( $id, $sku, false ) ) {
			++$count;
		}
	}

	return $count;
}

/**
 * Attach images to all demo SKUs (existing or new).
 *
 * @param bool $force Replace existing product images.
 * @return int Number of products that received an image.
 */
function jwellery_import_demo_product_images( $force = false ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$count = 0;
	$rows  = function_exists( 'jwellery_get_bundled_catalog_rows' )
		? jwellery_get_bundled_catalog_rows()
		: jwellery_get_demo_products();
	foreach ( $rows as $row ) {
		$sku = $row[0];
		$id  = wc_get_product_id_by_sku( $sku );
		if ( ! $id ) {
			continue;
		}
		if ( $force && function_exists( 'jwellery_product_is_admin_managed' ) && jwellery_product_is_admin_managed( $id ) && has_post_thumbnail( $id ) ) {
			continue;
		}
		if ( jwellery_attach_demo_product_image( $id, $sku, $force ) ) {
			++$count;
		}
	}

	jwellery_repair_missing_product_images();
	wc_delete_product_transients();
	return $count;
}

/**
 * Whether SKU has a bundled image in the theme (WhatsApp upload).
 *
 * @param string $sku Product SKU.
 * @return bool
 */
function jwellery_sku_has_bundled_image( $sku ) {
	return (bool) jwellery_get_bundled_image_paths( $sku );
}

/**
 * Draft published products outside the active catalog (old demo / stale uploads).
 *
 * @return int Number of products moved to draft.
 */
function jwellery_hide_products_without_bundled_images() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$allowed = function_exists( 'jwellery_get_active_catalog_skus' )
		? array_flip( jwellery_get_active_catalog_skus() )
		: array();

	$count = 0;
	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish' ) ) ) as $product ) {
		$sku = $product->get_sku();
		if ( ! $sku ) {
			$product->set_status( 'draft' );
			$product->save();
			++$count;
			continue;
		}
		if ( $allowed && isset( $allowed[ $sku ] ) ) {
			continue;
		}
		if ( ! $allowed && jwellery_sku_has_bundled_image( $sku ) ) {
			continue;
		}
		$product->set_status( 'draft' );
		$product->save();
		++$count;
	}

	return $count;
}

/**
 * SKUs removed from catalog (duplicate WhatsApp photos).
 *
 * @return string[]
 */
function jwellery_get_retired_catalog_skus() {
	return array( 'WP-005', 'WP-010', 'WP-012', 'WP-027' );
}

/**
 * Draft duplicate published products (same SKU) and invalid storefront items.
 *
 * @return int Number of products moved to draft.
 */
function jwellery_cleanup_catalog_storefront() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$count   = 0;
	$allowed = function_exists( 'jwellery_get_active_catalog_skus' )
		? array_flip( jwellery_get_active_catalog_skus() )
		: array();
	$retired = array_flip( jwellery_get_retired_catalog_skus() );
	$by_sku  = array();

	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft' ) ) ) as $product ) {
		$sku = (string) $product->get_sku();
		if ( $sku ) {
			$by_sku[ $sku ][] = (int) $product->get_id();
		}
	}

	foreach ( $by_sku as $sku => $ids ) {
		if ( count( $ids ) <= 1 ) {
			continue;
		}
		$keep = function_exists( 'jwellery_pick_canonical_product_id' )
			? jwellery_pick_canonical_product_id( $ids )
			: (int) max( $ids );
		if ( function_exists( 'jwellery_trash_catalog_product_ids' ) ) {
			$count += jwellery_trash_catalog_product_ids( $ids, $keep );
		} else {
			rsort( $ids );
			array_shift( $ids );
			foreach ( $ids as $dup_id ) {
				$dup = wc_get_product( $dup_id );
				if ( ! $dup ) {
					continue;
				}
				$dup->set_status( 'draft' );
				$dup->save();
				++$count;
			}
		}
	}

	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish' ) ) ) as $product ) {
		$sku = (string) $product->get_sku();
		$id  = (int) $product->get_id();

		if ( $sku && isset( $retired[ $sku ] ) ) {
			$product->set_status( 'draft' );
			$product->save();
			++$count;
			continue;
		}

		if ( $allowed && $sku && ! isset( $allowed[ $sku ] ) ) {
			$product->set_status( 'draft' );
			$product->save();
			++$count;
			continue;
		}

		if ( function_exists( 'jwellery_product_has_image' ) && ! jwellery_product_has_image( $product ) ) {
			if ( $sku && isset( $allowed[ $sku ] ) && function_exists( 'jwellery_attach_demo_product_image' ) ) {
				if ( jwellery_attach_demo_product_image( $id, $sku, true ) && jwellery_product_has_image( $product ) ) {
					continue;
				}
			}
			$product->set_status( 'draft' );
			$product->save();
			++$count;
		}
	}

	wc_delete_product_transients();
	return $count;
}

/**
 * Re-import bundled images for all catalog SKUs (replaces old CDN thumbnails).
 *
 * @return int
 */
function jwellery_refresh_bundled_catalog_images() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$count = 0;
	$rows  = function_exists( 'jwellery_get_bundled_catalog_rows' )
		? jwellery_get_bundled_catalog_rows()
		: jwellery_get_demo_products();
	foreach ( $rows as $row ) {
		$sku = $row[0];
		if ( ! jwellery_sku_has_bundled_image( $sku ) ) {
			continue;
		}
		$id = wc_get_product_id_by_sku( $sku );
		if ( ! $id ) {
			continue;
		}
		if ( function_exists( 'jwellery_product_is_admin_managed' ) && jwellery_product_is_admin_managed( $id ) && has_post_thumbnail( $id ) ) {
			continue;
		}
		if ( jwellery_attach_demo_product_image( $id, $sku, true ) ) {
			++$count;
		}
	}

	wc_delete_product_transients();
	return $count;
}

/**
 * Alias for catalog sync (all bundled SKUs).
 *
 * @param bool $force Replace existing product images.
 * @return int
 */
function jwellery_import_all_catalog_images( $force = false ) {
	return jwellery_import_demo_product_images( $force );
}
