<?php
/**
 * Category card images — bundled assets + CDN fallback + WooCommerce thumbnails.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Category slug => image sources (theme file + CDN fallback from store catalog).
 *
 * @return array<string, array{file: string, cdn: string}>
 */
function jwellery_category_image_sources() {
	return array(
		'ear-rings'    => array(
			'file' => 'ear-rings.jpg',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/IMG-20260523-WA0204_1.jpg?v=1779539903',
		),
		'studs'        => array(
			'file' => '',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/IMG-20260523-WA0204_1.jpg?v=1779539903',
		),
		'necklaces'    => array(
			'file' => 'necklaces.jpg',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/IMG-20260323-WA0035.jpg?v=1774263517',
		),
		'chockers'     => array(
			'file' => 'chockers.jpg',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/IMG_20241002_224030.jpg?v=1727951763',
		),
		'bangles'      => array(
			'file' => 'bangles.jpg',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/WhatsAppImage2026-05-26at6.18.38PM.jpg?v=1779799788',
		),
		'long-harams'  => array(
			'file' => 'long-harams.jpg',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/IMG-20250926-WA0021.jpg?v=1758882462',
		),
		'handmade-collection'     => array(
			'file' => '',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/IMG-20250107-WA0002.jpg?v=1736229320',
		),
		'instagram-collection'    => array(
			'file' => '',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/IMG-20260127-WA0012.jpg?v=1769500948',
		),
		'latest-collection'       => array(
			'file' => '',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/WhatsAppImage2026-05-30at7.12.40PM.jpg?v=1780148591',
		),
		'combo'                   => array(
			'file' => '',
			'cdn'  => 'https://cdn.shopify.com/s/files/1/0701/9030/1416/files/IMG-20260601-WA0087.jpg?v=1780324177',
		),
		'rings'                   => array(
			'file' => 'rings.png',
			'cdn'  => '',
		),
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
 * Public URL for a category image (theme bundle or CDN).
 *
 * @param string $slug Category slug.
 * @return string
 */
function jwellery_get_category_image_url( $slug ) {
	$map = jwellery_category_image_sources();
	if ( empty( $map[ $slug ] ) ) {
		$aliases = jwellery_category_image_slug_aliases();
		if ( ! empty( $aliases[ $slug ] ) ) {
			$slug = $aliases[ $slug ];
		}
	}
	if ( empty( $map[ $slug ] ) ) {
		return '';
	}
	$entry = $map[ $slug ];
	if ( ! empty( $entry['file'] ) ) {
		$path = JWELLERY_THEME_DIR . '/assets/category-images/' . $entry['file'];
		if ( file_exists( $path ) ) {
			return JWELLERY_THEME_URI . '/assets/category-images/' . $entry['file'];
		}
	}
	return ! empty( $entry['cdn'] ) ? $entry['cdn'] : '';
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

	$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( $thumb_id && wp_attachment_is_image( $thumb_id ) ) {
		$html = wp_get_attachment_image(
			$thumb_id,
			'medium',
			false,
			array(
				'class'   => 'category-image',
				'alt'     => esc_attr( $term->name ),
				'loading' => 'lazy',
			)
		);
		if ( $html ) {
			return $html;
		}
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
		$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		if ( $thumb_id && wp_attachment_is_image( $thumb_id ) ) {
			$html = wp_get_attachment_image(
				$thumb_id,
				'medium',
				false,
				array(
					'class'   => 'jwellery-category-browse-img',
					'alt'     => esc_attr( $term->name ),
					'loading' => 'lazy',
				)
			);
			if ( $html ) {
				return $html;
			}
		}
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
 * Assign WooCommerce category thumbnails from products, bundled images, or CDN.
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
			continue;
		}

		if ( function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products(
				array(
					'limit'        => 1,
					'status'       => 'publish',
					'stock_status' => 'instock',
					'category'     => array( $slug ),
				)
			);
			if ( ! empty( $products ) ) {
				$image_id = $products[0]->get_image_id();
				if ( $image_id ) {
					update_term_meta( $term->term_id, 'thumbnail_id', $image_id );
					++$count;
					continue;
				}
			}
		}

		$url = jwellery_get_category_image_url( $slug );
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
	if ( ! current_user_can( 'manage_woocommerce' ) || get_option( 'jwellery_category_images_v3' ) ) {
		return;
	}
	jwellery_assign_category_thumbnails( true );
	update_option( 'jwellery_category_images_v3', 1, false );
}
add_action( 'admin_init', 'jwellery_maybe_assign_category_thumbnails_admin', 20 );
