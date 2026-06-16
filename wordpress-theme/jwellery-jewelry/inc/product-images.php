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
 * Local image path for SKU if bundled in theme.
 *
 * @param string $sku Product SKU.
 * @return string|false
 */
function jwellery_get_bundled_image_path( $sku ) {
	$dir = JWELLERY_THEME_DIR . '/assets/demo-products/';
	foreach ( array( 'jpg', 'jpeg', 'png', 'webp' ) as $ext ) {
		$path = $dir . $sku . '.' . $ext;
		if ( is_readable( $path ) ) {
			return $path;
		}
	}
	return false;
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
 * Set product featured image from bundle or remote URL.
 *
 * @param int    $product_id Product ID.
 * @param string $sku        Product SKU.
 * @return bool
 */
function jwellery_attach_demo_product_image( $product_id, $sku ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 || has_post_thumbnail( $product_id ) ) {
		return (bool) has_post_thumbnail( $product_id );
	}

	$attach_id = false;
	$path      = jwellery_get_bundled_image_path( $sku );
	if ( $path ) {
		$attach_id = jwellery_upload_image_from_path( $path, $sku, $product_id );
	}

	if ( ! $attach_id ) {
		$map = jwellery_get_product_image_map();
		if ( ! empty( $map[ $sku ]['image'] ) ) {
			$attach_id = jwellery_sideload_image_from_url( $map[ $sku ]['image'], $sku, $product_id );
		}
	}

	if ( $attach_id ) {
		set_post_thumbnail( $product_id, $attach_id );
		return true;
	}

	return false;
}

/**
 * Attach images to all demo SKUs (existing or new).
 *
 * @return int Number of products that received an image.
 */
function jwellery_import_demo_product_images() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$count = 0;
	foreach ( jwellery_get_demo_products() as $row ) {
		$sku = $row[0];
		$id  = wc_get_product_id_by_sku( $sku );
		if ( ! $id ) {
			continue;
		}
		if ( jwellery_attach_demo_product_image( $id, $sku ) ) {
			++$count;
		}
	}

	wc_delete_product_transients();
	return $count;
}
