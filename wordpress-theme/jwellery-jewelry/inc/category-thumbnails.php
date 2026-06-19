<?php
/**
 * Category card images — recent WhatsApp catalog products + bundled assets.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Category slug => bundled fallback filename.
 *
 * @return array<string, array{file: string}>
 */
function jwellery_category_image_sources() {
	return array(
		'ear-rings'            => array( 'file' => 'ear-rings.png' ),
		'studs'                => array( 'file' => 'studs.png' ),
		'necklaces'            => array( 'file' => 'necklaces.png' ),
		'chockers'             => array( 'file' => 'chockers.png' ),
		'bangles'              => array( 'file' => 'bangles.png' ),
		'long-harams'          => array( 'file' => 'long-harams.png' ),
		'handmade-collection'  => array( 'file' => 'handmade-collection.png' ),
		'instagram-collection' => array( 'file' => 'instagram-collection.png' ),
		'latest-collection'    => array( 'file' => 'latest-collection.png' ),
		'combo'                => array( 'file' => 'combo.png' ),
		'rings'                => array( 'file' => 'rings.png' ),
	);
}

/**
 * Slug aliases when WooCommerce terms use a different slug than our image map.
 *
 * @return array<string, string>
 */
function jwellery_category_image_slug_aliases() {
	return array(
		'earrings'     => 'ear-rings',
		'ear-ring'     => 'ear-rings',
		'ear_rings'    => 'ear-rings',
		'chokers'      => 'chockers',
		'choker'       => 'chockers',
		'long-haram'   => 'long-harams',
		'longharams'   => 'long-harams',
		'long_harams'  => 'long-harams',
	);
}

/**
 * Map a product_cat term to a key in jwellery_category_image_sources().
 *
 * @param WP_Term $term Category term.
 * @return string Canonical slug key or empty.
 */
function jwellery_resolve_category_image_key( $term ) {
	$map = jwellery_category_image_sources();
	$slug = $term->slug;

	if ( isset( $map[ $slug ] ) ) {
		return $slug;
	}

	$aliases = jwellery_category_image_slug_aliases();
	if ( isset( $aliases[ $slug ] ) && isset( $map[ $aliases[ $slug ] ] ) ) {
		return $aliases[ $slug ];
	}

	$name_slug = sanitize_title( $term->name );
	if ( isset( $map[ $name_slug ] ) ) {
		return $name_slug;
	}

	if ( function_exists( 'jwellery_get_reference_categories' ) ) {
		foreach ( jwellery_get_reference_categories() as $key => $data ) {
			if ( strcasecmp( $data['name'], $term->name ) === 0 && isset( $map[ $key ] ) ) {
				return $key;
			}
		}
	}

	return '';
}

/**
 * Product IDs limited to allowed SKUs.
 *
 * @param string[] $skus Allowed SKUs.
 * @return int[]
 */
function jwellery_product_ids_for_skus( $skus ) {
	$ids = array();
	foreach ( $skus as $sku ) {
		$id = wc_get_product_id_by_sku( $sku );
		if ( $id ) {
			$ids[] = (int) $id;
		}
	}
	return $ids;
}

/**
 * Newest published product in a category from an allowed SKU list.
 *
 * @param string   $slug Category slug.
 * @param string[] $skus Allowed SKUs.
 * @return WC_Product|null
 */
function jwellery_get_newest_category_product( $slug, $skus ) {
	if ( ! function_exists( 'wc_get_products' ) || empty( $skus ) ) {
		return null;
	}

	$include = jwellery_product_ids_for_skus( $skus );
	if ( empty( $include ) ) {
		return null;
	}

	$products = wc_get_products(
		array(
			'limit'    => 1,
			'status'   => 'publish',
			'category' => array( $slug ),
			'include'  => $include,
			'orderby'  => 'date',
			'order'    => 'DESC',
		)
	);

	return ! empty( $products[0] ) ? $products[0] : null;
}

/**
 * Pick a cover product for a category — prefer last 2 days, then slightly older WhatsApp batch.
 *
 * @param string $slug Category slug.
 * @return WC_Product|null
 */
function jwellery_get_category_cover_product( $slug ) {
	if ( ! function_exists( 'jwellery_get_whatsapp_skus_within_days' ) ) {
		return null;
	}

	$windows = array(
		(int) jwellery_whatsapp_catalog_recent_days(),
		4,
		7,
	);

	foreach ( $windows as $days ) {
		$product = jwellery_get_newest_category_product( $slug, jwellery_get_whatsapp_skus_within_days( $days ) );
		if ( $product && $product->get_image_id() ) {
			return $product;
		}
	}

	if ( function_exists( 'jwellery_get_active_catalog_product_ids' ) ) {
		$products = wc_get_products(
			array(
				'limit'    => 1,
				'status'   => 'publish',
				'category' => array( $slug ),
				'include'  => jwellery_get_active_catalog_product_ids(),
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
		if ( ! empty( $products[0] ) && $products[0]->get_image_id() ) {
			return $products[0];
		}
	}

	return null;
}

/**
 * Image URL from the newest recent WhatsApp product in a category.
 *
 * @param string $slug Category slug.
 * @return string
 */
function jwellery_get_category_image_url_from_product( $slug ) {
	$product = jwellery_get_category_cover_product( $slug );
	if ( ! $product ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $product->get_image_id(), 'medium' );
	return $url ? (string) $url : '';
}

/**
 * Bundled theme file URL for a category slug.
 *
 * @param string $slug Category slug.
 * @return string
 */
function jwellery_get_category_bundled_image_url( $slug ) {
	$map = jwellery_category_image_sources();
	if ( empty( $map[ $slug ] ) ) {
		$aliases = jwellery_category_image_slug_aliases();
		if ( ! empty( $aliases[ $slug ] ) ) {
			$slug = $aliases[ $slug ];
		}
	}
	if ( empty( $map[ $slug ]['file'] ) ) {
		return '';
	}

	$entry = $map[ $slug ];
	$best  = array(
		'path' => '',
		'url'  => '',
		'mtime'=> 0,
	);

	foreach ( array( 'png', 'jpg', 'jpeg', 'webp' ) as $ext ) {
		$base = preg_replace( '/\.(png|jpe?g|webp)$/i', '', $entry['file'] );
		$path = JWELLERY_THEME_DIR . '/assets/category-images/' . $base . '.' . $ext;
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$mtime = (int) filemtime( $path );
		if ( $mtime >= $best['mtime'] ) {
			$best = array(
				'path'  => $path,
				'url'   => JWELLERY_THEME_URI . '/assets/category-images/' . $base . '.' . $ext,
				'mtime' => $mtime,
			);
		}
	}

	if ( ! $best['url'] ) {
		return '';
	}

	return add_query_arg( 'v', jwellery_asset_version(), $best['url'] );
}

/**
 * Public URL for a category image (recent product photo preferred).
 *
 * @param string $slug Category slug.
 * @return string
 */
function jwellery_get_category_image_url( $slug ) {
	$url = jwellery_get_category_image_url_from_product( $slug );
	if ( $url ) {
		return $url;
	}

	return jwellery_get_category_bundled_image_url( $slug );
}

/**
 * Markup for category card image on homepage.
 *
 * @param WP_Term $term Category term.
 * @return string
 */
function jwellery_category_card_image_html( $term ) {
	$key = jwellery_resolve_category_image_key( $term );
	$url = $key ? jwellery_get_category_image_url( $key ) : '';

	if ( $url ) {
		return sprintf(
			'<img class="category-image" src="%s" alt="%s" loading="lazy" decoding="async" width="400" height="400" />',
			esc_url( $url ),
			esc_attr( $term->name )
		);
	}

	return '<div class="category-image category-image--placeholder" aria-hidden="true"></div>';
}

/**
 * Category image for Shop by Category browse grid.
 *
 * @param WP_Term $term Category term.
 * @return string
 */
function jwellery_category_browse_image_html( $term ) {
	$key = jwellery_resolve_category_image_key( $term );
	$url = $key ? jwellery_get_category_image_url( $key ) : '';

	if ( ! $url ) {
		return '<span class="jwellery-category-browse-placeholder" aria-hidden="true"></span>';
	}

	return sprintf(
		'<img class="jwellery-category-browse-img" src="%s" alt="%s" loading="lazy" decoding="async" width="400" height="500" />',
		esc_url( $url ),
		esc_attr( $term->name )
	);
}

/**
 * Sideload category thumbnail into WordPress media library.
 *
 * @param WP_Term $term Category term.
 * @param string  $url  Image URL.
 * @return bool
 */
function jwellery_sideload_category_thumbnail( $term, $url ) {
	if ( ! $url || ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	if ( ! function_exists( 'media_sideload_image' ) ) {
		return false;
	}

	$attachment_id = media_sideload_image( $url, 0, $term->name );
	if ( is_wp_error( $attachment_id ) ) {
		return false;
	}

	update_term_meta( $term->term_id, 'thumbnail_id', (int) $attachment_id );
	return true;
}

/**
 * Assign WooCommerce category thumbnails from recent catalog products only.
 *
 * @param bool $force Replace existing thumbnails.
 * @return int Number of categories updated.
 */
function jwellery_assign_category_thumbnails( $force = false ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return 0;
	}

	$count = 0;
	$map   = jwellery_category_image_sources();

	foreach ( array_keys( $map ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			$aliases = jwellery_category_image_slug_aliases();
			foreach ( $aliases as $alt => $canonical ) {
				if ( $canonical === $slug ) {
					$term = get_term_by( 'slug', $alt, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						break;
					}
				}
			}
		}
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		if ( ! $force && get_term_meta( $term->term_id, 'thumbnail_id', true ) ) {
			delete_term_meta( $term->term_id, 'thumbnail_id' );
		}

		$product = jwellery_get_category_cover_product( $slug );
		if ( $product && $product->get_image_id() ) {
			update_term_meta( $term->term_id, 'thumbnail_id', (int) $product->get_image_id() );
			++$count;
			continue;
		}

		$url = jwellery_get_category_bundled_image_url( $slug );
		if ( $url && jwellery_sideload_category_thumbnail( $term, $url ) ) {
			++$count;
		}
	}

	return $count;
}

/**
 * Assign category thumbnails from wp-admin only (never on public page views).
 */
function jwellery_maybe_assign_category_thumbnails_admin() {
	if ( ! current_user_can( 'manage_woocommerce' ) || get_option( 'jwellery_category_images_v5' ) ) {
		return;
	}
	jwellery_assign_category_thumbnails( true );
	update_option( 'jwellery_category_images_v5', 1, false );
}
add_action( 'admin_init', 'jwellery_maybe_assign_category_thumbnails_admin', 20 );
