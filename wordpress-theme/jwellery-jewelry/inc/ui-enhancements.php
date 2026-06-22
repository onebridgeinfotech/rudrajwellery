<?php
/**
 * UI enhancements: homepage stock filter, cart banner, product trust, WhatsApp, footer brand.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default in-stock filter for homepage product queries.
 *
 * @param array $args wc_get_products args.
 * @return array
 */
function jwellery_home_product_args( $args ) {
	$args['stock_status'] = 'instock';
	return $args;
}

/**
 * Desktop product columns for homepage grids.
 *
 * @return int
 */
function jwellery_home_grid_columns() {
	return 4;
}

/**
 * Full rows shown per homepage product section.
 *
 * @return int
 */
function jwellery_home_grid_rows() {
	return 2;
}

/**
 * Whether a product can be sold on the storefront (published, priced, purchasable, with image).
 *
 * @param WC_Product|int $product Product or ID.
 * @return bool
 */
function jwellery_product_is_storefront_ready( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}
	if ( ! $product instanceof WC_Product ) {
		return false;
	}
	if ( 'publish' !== $product->get_status() || ! $product->is_purchasable() ) {
		return false;
	}
	if ( function_exists( 'jwellery_product_has_image' ) && ! jwellery_product_has_image( $product ) ) {
		return false;
	}
	return true;
}

/**
 * Keep only products that have a real featured image.
 *
 * @param WC_Product[] $products Products.
 * @return WC_Product[]
 */
function jwellery_filter_products_with_images( $products ) {
	if ( empty( $products ) || ! function_exists( 'jwellery_product_has_image' ) ) {
		return is_array( $products ) ? $products : array();
	}
	return array_values(
		array_filter(
			$products,
			static function ( $product ) {
				return jwellery_product_has_image( $product );
			}
		)
	);
}

/**
 * Registry so homepage sections do not repeat the same product/photo.
 *
 * @return array{ids: array<int, bool>, images: array<string, bool>}
 */
function jwellery_homepage_display_registry() {
	static $registry = null;
	if ( null === $registry ) {
		$registry = array(
			'ids'    => array(),
			'images' => array(),
		);
	}
	return $registry;
}

/**
 * Reset homepage duplicate tracking (call once per front page).
 */
function jwellery_reset_homepage_display_registry() {
	$registry = &jwellery_homepage_display_registry();
	$registry['ids']    = array();
	$registry['images'] = array();
}

/**
 * Filter products already shown on the homepage (by ID and image fingerprint).
 *
 * @param WC_Product[] $products Products.
 * @param array        $opts     exclude_shown, exclude_images, register.
 * @return WC_Product[]
 */
function jwellery_filter_unique_display_products( $products, $opts = array() ) {
	$exclude_shown  = array_key_exists( 'exclude_shown', $opts ) ? (bool) $opts['exclude_shown'] : true;
	$exclude_images = array_key_exists( 'exclude_images', $opts ) ? (bool) $opts['exclude_images'] : true;
	$register       = ! empty( $opts['register'] );

	$registry = &jwellery_homepage_display_registry();
	$out      = array();

	foreach ( (array) $products as $product ) {
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$pid = (int) $product->get_id();
		if ( $exclude_shown && isset( $registry['ids'][ $pid ] ) ) {
			continue;
		}

		$img_key = function_exists( 'jwellery_product_image_fingerprint' )
			? jwellery_product_image_fingerprint( $pid )
			: '';
		if ( $exclude_images && $img_key && isset( $registry['images'][ $img_key ] ) ) {
			continue;
		}

		$out[] = $product;
		if ( $register ) {
			$registry['ids'][ $pid ] = true;
			if ( $img_key ) {
				$registry['images'][ $img_key ] = true;
			}
		}
	}

	return $out;
}

/**
 * Mark products as already displayed on the homepage.
 *
 * @param WC_Product[] $products Products.
 */
function jwellery_mark_homepage_products_shown( $products ) {
	jwellery_filter_unique_display_products(
		$products,
		array(
			'exclude_shown'  => false,
			'exclude_images' => false,
			'register'       => true,
		)
	);
}

/**
 * Remove duplicate image fingerprints from a product list.
 *
 * @param WC_Product[] $products Products.
 * @return WC_Product[]
 */
function jwellery_dedupe_products_by_image( $products ) {
	$seen = array();
	$out  = array();
	foreach ( (array) $products as $product ) {
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$key = function_exists( 'jwellery_product_image_fingerprint' )
			? jwellery_product_image_fingerprint( (int) $product->get_id() )
			: 'id-' . $product->get_id();
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$out[]        = $product;
	}
	return $out;
}

/**
 * Trim a product list to complete grid rows (no empty slots on the last row).
 *
 * @param WC_Product[] $products Products.
 * @param int|null     $cols     Columns per row.
 * @param int|null     $rows     Row count cap.
 * @return WC_Product[]
 */
function jwellery_trim_products_to_full_rows( $products, $cols = null, $rows = null ) {
	$cols = $cols ? (int) $cols : jwellery_home_grid_columns();
	$rows = $rows ? (int) $rows : jwellery_home_grid_rows();
	$max  = $cols * $rows;

	$products = array_slice( $products, 0, $max );
	$count    = count( $products );
	if ( $count <= $cols ) {
		return $count === $cols ? $products : array();
	}

	$full = (int) floor( $count / $cols ) * $cols;
	return array_slice( $products, 0, $full );
}

/**
 * Fetch in-stock products with images for homepage grids (full rows only).
 *
 * @param array    $args  wc_get_products args (limit optional).
 * @param int|null $cols  Columns per row.
 * @param int|null $rows  Max rows.
 * @return WC_Product[]
 */
function jwellery_get_products_for_display( $args = array(), $cols = null, $rows = null ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$cols = $cols ? (int) $cols : jwellery_home_grid_columns();
	$rows = $rows ? (int) $rows : jwellery_home_grid_rows();
	$need = $cols * $rows;

	$base = array_merge(
		array(
			'status'       => 'publish',
			'stock_status' => 'instock',
		),
		$args
	);

	$exclude = array();
	if ( ! empty( $base['exclude'] ) && is_array( $base['exclude'] ) ) {
		$exclude = array_flip( array_map( 'intval', $base['exclude'] ) );
		unset( $base['exclude'] );
	}

	$active_ids = function_exists( 'jwellery_get_active_catalog_product_ids' )
		? jwellery_get_active_catalog_product_ids()
		: array();
	if ( $active_ids ) {
		$base['include'] = $active_ids;
	}

	$offset = 0;
	$batch  = max( $need * 3, 24 );
	$picked = array();
	$seen   = array();

	while ( count( $picked ) < $need && $offset < 120 ) {
		$query = array_merge(
			$base,
			array(
				'limit'  => $batch,
				'offset' => $offset,
			)
		);
		$batch_products = wc_get_products( $query );
		if ( empty( $batch_products ) ) {
			break;
		}

		foreach ( $batch_products as $product ) {
			$pid = $product->get_id();
			if ( isset( $seen[ $pid ] ) || isset( $exclude[ $pid ] ) ) {
				continue;
			}
			$seen[ $pid ] = true;
			if ( function_exists( 'jwellery_product_is_storefront_ready' ) && ! jwellery_product_is_storefront_ready( $product ) ) {
				continue;
			}
			if ( jwellery_product_has_image( $product ) ) {
				$picked[] = $product;
				if ( count( $picked ) >= $need ) {
					break 2;
				}
			}
		}

		$offset += $batch;
		if ( count( $batch_products ) < $batch ) {
			break;
		}
	}

	$picked = jwellery_trim_products_to_full_rows( $picked, $cols, $rows );
	if ( function_exists( 'jwellery_dedupe_products_by_image' ) ) {
		$picked = jwellery_dedupe_products_by_image( $picked );
	}

	return $picked;
}

/**
 * Top up a product list to full grid rows using additional catalog items.
 *
 * @param WC_Product[] $products   Existing products.
 * @param int|null     $cols       Columns per row.
 * @param int|null     $rows       Row count.
 * @param array        $extra_args Extra wc_get_products args for supplements.
 * @return WC_Product[]
 */
function jwellery_supplement_products_for_grid( $products, $cols = null, $rows = null, $extra_args = array() ) {
	$cols = $cols ? (int) $cols : jwellery_home_grid_columns();
	$rows = $rows ? (int) $rows : jwellery_home_grid_rows();
	$need = $cols * $rows;

	$products = is_array( $products ) ? $products : array();
	if ( function_exists( 'jwellery_filter_products_with_images' ) ) {
		$products = jwellery_filter_products_with_images( $products );
	}

	if ( count( $products ) >= $need ) {
		return jwellery_trim_products_to_full_rows( $products, $cols, $rows );
	}

	$seen = array();
	foreach ( $products as $product ) {
		$seen[ $product->get_id() ] = true;
	}

	$more = jwellery_get_products_for_display(
		array_merge(
			array(
				'orderby' => 'date',
				'order'   => 'DESC',
			),
			$extra_args
		),
		$cols,
		$rows
	);

	foreach ( $more as $product ) {
		$pid = $product->get_id();
		if ( isset( $seen[ $pid ] ) ) {
			continue;
		}
		$seen[ $pid ] = true;
		$products[]   = $product;
		if ( count( $products ) >= $need ) {
			break;
		}
	}

	return jwellery_trim_products_to_full_rows( $products, $cols, $rows );
}

/**
 * Hide catalog products that have no featured image.
 *
 * @param bool       $visible Visible.
 * @param int        $id      Product ID.
 * @param WC_Product $product Product.
 * @return bool
 */
function jwellery_hide_products_without_images( $visible, $id, $product ) {
	if ( ! $visible || is_admin() ) {
		return $visible;
	}
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return $visible;
	}
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return $visible;
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return $visible;
	}
	if ( function_exists( 'is_product' ) && is_product() && (int) get_queried_object_id() === (int) $id ) {
		return $visible;
	}
	return jwellery_product_has_image( $product );
}
add_filter( 'woocommerce_product_is_visible', 'jwellery_hide_products_without_images', 10, 3 );

/**
 * Hide products outside the recent WhatsApp catalog (old demo / stale uploads).
 *
 * @param bool       $visible Visible.
 * @param int        $id      Product ID.
 * @param WC_Product $product Product.
 * @return bool
 */
function jwellery_hide_inactive_catalog_products( $visible, $id, $product ) {
	if ( ! $visible || is_admin() ) {
		return $visible;
	}
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return $visible;
	}
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return $visible;
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return $visible;
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		return $visible;
	}
	if ( function_exists( 'jwellery_product_is_admin_managed' ) && jwellery_product_is_admin_managed( $id ) ) {
		return $visible;
	}
	if ( ! function_exists( 'jwellery_get_active_catalog_skus' ) ) {
		return $visible;
	}

	$allowed = array_flip( jwellery_get_active_catalog_skus() );
	$sku     = $product->get_sku();
	if ( $sku && ! isset( $allowed[ $sku ] ) ) {
		return false;
	}

	return $visible;
}
add_filter( 'woocommerce_product_is_visible', 'jwellery_hide_inactive_catalog_products', 12, 3 );

/**
 * Shop archive: only list products with a featured image.
 *
 * @param array $meta_query Meta query.
 * @return array
 */
function jwellery_shop_meta_query_require_image( $meta_query ) {
	if ( is_admin() || ! ( is_shop() || is_product_taxonomy() ) ) {
		return $meta_query;
	}
	if ( ! is_array( $meta_query ) ) {
		$meta_query = array();
	}
	$meta_query[] = array(
		'key'     => '_thumbnail_id',
		'value'   => '0',
		'compare' => '>',
		'type'    => 'NUMERIC',
	);
	return $meta_query;
}
add_filter( 'woocommerce_product_query_meta_query', 'jwellery_shop_meta_query_require_image' );

/**
 * Shop / category archives: only show recent WhatsApp catalog products.
 *
 * @param WC_Query $query Product query.
 */
function jwellery_shop_query_active_catalog_only( $query ) {
	if ( is_admin() ) {
		return;
	}
	if ( ! function_exists( 'jwellery_get_active_catalog_product_ids' ) ) {
		return;
	}

	$ids = jwellery_get_active_catalog_product_ids();
	if ( empty( $ids ) ) {
		return;
	}

	$query->set( 'post__in', $ids );
}
add_action( 'woocommerce_product_query', 'jwellery_shop_query_active_catalog_only', 20 );

/**
 * WhatsApp URL (wa.me).
 *
 * @return string
 */
function jwellery_whatsapp_url() {
	$phone = preg_replace( '/\D+/', '', (string) get_theme_mod( 'jwellery_whatsapp', '7730817950' ) );
	if ( ! $phone ) {
		$phone = '7730817950';
	}
	if ( strlen( $phone ) < 10 ) {
		return '';
	}
	if ( strlen( $phone ) === 10 ) {
		$phone = '91' . $phone;
	}
	return 'https://wa.me/' . $phone;
}

/**
 * Footer display name (never raw Hostinger URL).
 *
 * @return string
 */
function jwellery_footer_brand() {
	return function_exists( 'jwellery_brand_name' ) ? jwellery_brand_name() : get_bloginfo( 'name' );
}

/**
 * Account link label.
 *
 * @return string
 */
function jwellery_account_label() {
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$name = $user->first_name ? $user->first_name : $user->display_name;
		if ( $name ) {
			return sprintf(
				/* translators: %s: customer first name */
				__( 'Hi, %s', 'jwellery-jewelry' ),
				$name
			);
		}
		return __( 'My account', 'jwellery-jewelry' );
	}
	return __( 'Log in', 'jwellery-jewelry' );
}

/**
 * Cart icon markup with count badge.
 */
function jwellery_cart_icon_link() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$count = WC()->cart->get_cart_contents_count();
	$icon  = function_exists( 'jwellery_icon_svg' ) ? jwellery_icon_svg( 'cart', 22 ) : '';
	printf(
		'<a class="jwellery-header-icon jwellery-cart-link jwellery-cart-icon" href="%s" title="%s"><span class="cart-icon" aria-hidden="true">%s</span><span class="cart-count-badge">%d</span></a>',
		esc_url( wc_get_cart_url() ),
		esc_attr__( 'View cart', 'jwellery-jewelry' ),
		$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		(int) $count
	);
}

/**
 * Rotating announcement messages.
 *
 * @return array
 */
function jwellery_announcement_messages() {
	$raw = (string) get_theme_mod( 'jwellery_announcements', '' );
	if ( $raw ) {
		$raw = str_ireplace(
			array(
				'FREE Shipping on Orders Above ₹999',
				'Free shipping on orders above ₹999',
				'Free Shipping Across India on Orders Above ₹999',
				'All India Shipping',
			),
			'Free Shipping All Over India',
			$raw
		);
		$lines = array_filter( array_map( 'trim', explode( '|', $raw ) ) );
		if ( $lines ) {
			foreach ( $lines as $idx => $line ) {
				$line = trim( (string) $line );
				if ( false !== stripos( $line, 'shipping' ) && false !== stripos( $line, 'india' ) ) {
					$lines[ $idx ] = 'Free Shipping All Over India';
				}
			}
			return $lines;
		}
	}
	return array(
		__( 'Free Shipping All Over India', 'jwellery-jewelry' ),
		__( 'Elegant Artificial Jewellery', 'jwellery-jewelry' ),
		__( 'New Arrivals Now Live', 'jwellery-jewelry' ),
		__( 'Perfect Jewellery for Every Occasion', 'jwellery-jewelry' ),
	);
}

/**
 * Handle footer email subscribe (stores intent via email to admin).
 */
function jwellery_handle_subscribe() {
	if ( ! isset( $_POST['jwellery_subscribe_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jwellery_subscribe_nonce'] ) ), 'jwellery_subscribe' ) ) {
		wp_safe_redirect( home_url( '/?subscribe=invalid' ) );
		exit;
	}

	if ( ! function_exists( 'jwellery_security_rate_limit_allow' ) || ! jwellery_security_rate_limit_allow( 'subscribe', 3, 3600 ) ) {
		wp_safe_redirect( home_url( '/?subscribe=invalid' ) );
		exit;
	}

	$email = isset( $_POST['jwellery_email'] ) ? sanitize_email( wp_unslash( $_POST['jwellery_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( home_url( '/?subscribe=invalid' ) );
		exit;
	}
	$admin = class_exists( 'JUS_Notifications' ) ? JUS_Notifications::store_email() : get_option( 'admin_email' );
	/* translators: %s: subscriber email */
	wp_mail( $admin, __( 'New newsletter subscriber', 'jwellery-jewelry' ), sprintf( __( 'Subscribe request: %s', 'jwellery-jewelry' ), $email ) );
	wp_safe_redirect( home_url( '/?subscribe=thanks' ) );
	exit;
}
add_action( 'admin_post_nopriv_jwellery_subscribe', 'jwellery_handle_subscribe' );
add_action( 'admin_post_jwellery_subscribe', 'jwellery_handle_subscribe' );

/**
 * Sale badge HTML for product cards (Gemstone-style % off).
 *
 * @param WC_Product $product Product.
 * @return string
 */
function jwellery_product_sale_badge_html( $product ) {
	if ( ! $product || ! $product->is_on_sale() ) {
		return '';
	}

	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();

	if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
		$pct = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
		if ( $pct > 0 ) {
			return '<span class="jwellery-sale-badge">' . esc_html( sprintf( /* translators: %d: discount percent */ __( 'Sale %d%%', 'jwellery-jewelry' ), $pct ) ) . '</span>';
		}
	}

	return '<span class="jwellery-sale-badge">' . esc_html__( 'Sale', 'jwellery-jewelry' ) . '</span>';
}

/**
 * Lowest in-stock product price for hero "from" label.
 *
 * @return string Formatted price or empty.
 */
function jwellery_hero_from_price_html() {
	$custom = get_theme_mod( 'jwellery_hero_from_price', '' );
	if ( $custom ) {
		return wp_kses_post( wc_price( (float) $custom ) );
	}

	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}

	$products = wc_get_products(
		array(
			'limit'        => 1,
			'status'       => 'publish',
			'stock_status' => 'instock',
			'orderby'      => 'price',
			'order'        => 'ASC',
		)
	);

	if ( empty( $products ) ) {
		return '';
	}

	return wp_kses_post( $products[0]->get_price_html() );
}

/**
 * Default hero slides — long haram & necklace catalog photos.
 *
 * @return array<int, string>
 */
function jwellery_default_hero_slide_files() {
	return array(
		'hero-slide-1.png', // Kasulaperu haram set.
		'hero-slide-2.jpg', // Laxmi kasulu short necklace.
		'hero-slide-3.png', // Laxmi pendant pearl necklace.
		'hero-slide-4.png', // Black beads kasu necklace.
		'hero-slide-5.jpg', // Laxmi kasulu + bottu mala combo.
	);
}

/**
 * Hero slide image URLs (Customizer, then auto-pulls from Long Haram & Necklace products).
 *
 * @return array<int, string>
 */
function jwellery_hero_slides() {
	$slides = array();

	// 1. Customizer-set images take priority.
	foreach ( array( 'jwellery_hero_image_1', 'jwellery_hero_image_2', 'jwellery_hero_image_3', 'jwellery_hero_image_4', 'jwellery_hero_image_5' ) as $key ) {
		$val = get_theme_mod( $key, '' );
		if ( ! $val ) {
			continue;
		}
		if ( is_numeric( $val ) ) {
			$url = wp_get_attachment_image_url( (int) $val, 'large' );
		} else {
			$url = (string) $val;
		}
		if ( $url ) {
			$slides[] = $url;
		}
	}

	// 2. Auto-pull product images from Long Haram and Necklace categories.
	if ( empty( $slides ) && function_exists( 'wc_get_products' ) ) {
		$category_slugs = array( 'long-harams', 'necklaces', 'long-haram', 'necklace' );
		foreach ( $category_slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}
			$products = wc_get_products( array(
				'status'     => 'publish',
				'limit'      => 3,
				'category'   => array( $slug ),
				'orderby'    => 'date',
				'order'      => 'DESC',
			) );
			foreach ( $products as $product ) {
				$img = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
				if ( $img && ! in_array( $img, $slides, true ) ) {
					$slides[] = $img;
				}
				if ( count( $slides ) >= 5 ) {
					break 2;
				}
			}
		}
	}

	// 3. Fallback to static theme images.
	if ( empty( $slides ) ) {
		foreach ( jwellery_default_hero_slide_files() as $file ) {
			$path = JWELLERY_THEME_DIR . '/assets/images/' . $file;
			if ( is_readable( $path ) ) {
				$slides[] = JWELLERY_THEME_URI . '/assets/images/' . $file;
			}
		}
	}

	if ( empty( $slides ) ) {
		$slides[] = JWELLERY_THEME_URI . '/assets/category-images/long-harams.png';
		$slides[] = JWELLERY_THEME_URI . '/assets/category-images/necklaces.jpg';
	}

	return $slides;
}

/**
 * One-time: reset custom hero uploads so bundled haram/necklace slides show after deploy.
 */
function jwellery_bootstrap_hero_haram_necklace_slides() {
	$done = (string) get_option( 'jwellery_hero_haram_necklace_ver', '' );
	if ( $done === JWELLERY_THEME_VERSION ) {
		return;
	}

	foreach ( array( 'jwellery_hero_image_1', 'jwellery_hero_image_2', 'jwellery_hero_image_3', 'jwellery_hero_image_4', 'jwellery_hero_image_5' ) as $key ) {
		remove_theme_mod( $key );
	}

	update_option( 'jwellery_hero_haram_necklace_ver', JWELLERY_THEME_VERSION, false );
}
add_action( 'after_setup_theme', 'jwellery_bootstrap_hero_haram_necklace_slides', 28 );

/**
 * Login notice on cart for guests.
 */
function jwellery_cart_login_notice() {
	if ( is_user_logged_in() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}
	?>
	<div class="jwellery-cart-login-notice">
		<p><?php esc_html_e( 'Please log in or register to complete checkout.', 'jwellery-jewelry' ); ?></p>
		<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Log in / Register', 'jwellery-jewelry' ); ?></a>
	</div>
	<?php
}
add_action( 'woocommerce_before_cart', 'jwellery_cart_login_notice', 5 );
add_action( 'woocommerce_before_cart_table', 'jwellery_cart_login_notice', 5 );

/**
 * Trust badges on single product.
 */
function jwellery_product_trust_badges() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	?>
	<ul class="jwellery-trust-badges jwellery-trust-badges--product">
		<li><?php esc_html_e( 'Premium imitation jewellery', 'jwellery-jewelry' ); ?></li>
		<li><?php esc_html_e( 'Gold-tone finish — not real gold', 'jwellery-jewelry' ); ?></li>
		<li><?php esc_html_e( 'Secure UPI · All-India shipping', 'jwellery-jewelry' ); ?></li>
	</ul>
	<?php
}
// Priority set in inc/single-product.php.

/**
 * WhatsApp share on product page.
 */
function jwellery_product_whatsapp_share() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$wa = jwellery_whatsapp_url();
	if ( ! $wa ) {
		return;
	}
	$text = rawurlencode( $product->get_name() . ' — ' . $product->get_permalink() );
	?>
	<p class="jwellery-wa-share jwellery-single-product-actions">
		<a class="jwellery-btn jwellery-btn-wa jwellery-single-wa-btn" href="<?php echo esc_url( $wa . '?text=' . $text ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ask on WhatsApp', 'jwellery-jewelry' ); ?></a>
	</p>
	<?php
}
// Priority set in inc/single-product.php.

/**
 * Sticky add to cart bar (mobile).
 */
function jwellery_sticky_add_to_cart() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
		return;
	}
	?>
	<div class="jwellery-sticky-atc" id="jwellery-sticky-atc" hidden>
		<div class="jwellery-sticky-atc-inner container">
			<span class="jwellery-sticky-atc-title"><?php echo esc_html( $product->get_name() ); ?></span>
			<span class="jwellery-sticky-atc-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="jwellery-btn jwellery-btn-primary add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"><?php echo esc_html( $product->add_to_cart_text() ); ?></a>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'jwellery_sticky_add_to_cart' );

/**
 * Floating scroll + WhatsApp buttons (right side, site-wide).
 */
function jwellery_floating_actions() {
	$wa = jwellery_whatsapp_url();
	?>
	<div class="jwellery-floating-stack" aria-label="<?php esc_attr_e( 'Quick actions', 'jwellery-jewelry' ); ?>">
		<button type="button" class="jwellery-floating-btn jwellery-scroll-btn jwellery-scroll-toggle is-scroll-down" aria-label="<?php esc_attr_e( 'Scroll down', 'jwellery-jewelry' ); ?>">
			<span class="jwellery-scroll-icon jwellery-scroll-icon--down" aria-hidden="true">
				<?php
				if ( function_exists( 'jwellery_icon_svg' ) ) {
					echo jwellery_icon_svg( 'chevron-down', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</span>
			<span class="jwellery-scroll-icon jwellery-scroll-icon--up" aria-hidden="true">
				<?php
				if ( function_exists( 'jwellery_icon_svg' ) ) {
					echo jwellery_icon_svg( 'chevron-up', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</span>
		</button>
		<?php if ( $wa ) : ?>
			<?php
			$label = __( 'Order on WhatsApp', 'jwellery-jewelry' );
			?>
			<a class="jwellery-floating-btn jwellery-floating-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $label ); ?>">
				<span class="jwellery-floating-wa-icon" aria-hidden="true">
					<?php
					if ( function_exists( 'jwellery_icon_svg' ) ) {
						echo jwellery_icon_svg( 'whatsapp', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</span>
				<span class="jwellery-floating-wa-text"><?php echo esc_html( $label ); ?></span>
			</a>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'wp_footer', 'jwellery_floating_actions', 15 );

/**
 * Add to cart toast container.
 */
function jwellery_toast_container() {
	echo '<div id="jwellery-toast" class="jwellery-toast" role="status" aria-live="polite" hidden></div>';
}
add_action( 'wp_footer', 'jwellery_toast_container', 5 );

/**
 * Newsletter / WhatsApp CTA strip.
 */
function jwellery_home_newsletter() {
	$wa   = jwellery_whatsapp_url();
	$text = get_theme_mod( 'jwellery_newsletter_text', __( 'Get new designs & offers on WhatsApp', 'jwellery-jewelry' ) );
	?>
	<section class="jwellery-home-section jwellery-newsletter">
		<div class="container jwellery-newsletter-inner">
			<h2 class="section-title section-title--center"><?php echo esc_html( $text ); ?></h2>
			<?php if ( $wa ) : ?>
				<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Chat on WhatsApp', 'jwellery-jewelry' ); ?></a>
			<?php else : ?>
				<p class="jwellery-newsletter-hint"><?php esc_html_e( 'Add your WhatsApp number in Appearance â†’ Customize â†’ Store UI', 'jwellery-jewelry' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Simple testimonials strip.
 */
function jwellery_home_testimonials() {
	?>
	<section class="jwellery-home-section jwellery-testimonials">
		<div class="container">
			<h2 class="section-title section-title--center"><?php esc_html_e( 'What customers say', 'jwellery-jewelry' ); ?></h2>
			<div class="jwellery-testimonial-grid">
				<blockquote><p><?php esc_html_e( 'Beautiful designs and great quality for the price!', 'jwellery-jewelry' ); ?></p><cite>â€” <?php esc_html_e( 'Happy customer', 'jwellery-jewelry' ); ?></cite></blockquote>
				<blockquote><p><?php esc_html_e( 'Easy UPI checkout and fast delivery.', 'jwellery-jewelry' ); ?></p><cite>â€” <?php esc_html_e( 'Repeat buyer', 'jwellery-jewelry' ); ?></cite></blockquote>
				<blockquote><p><?php esc_html_e( 'Perfect for festive wear â€” looks like real gold!', 'jwellery-jewelry' ); ?></p><cite>â€” <?php esc_html_e( 'Verified buyer', 'jwellery-jewelry' ); ?></cite></blockquote>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Footer trust icons.
 */
function jwellery_footer_trust() {
	?>
	<div class="jwellery-footer-trust">
		<span><?php esc_html_e( 'UPI payments', 'jwellery-jewelry' ); ?></span>
		<span><?php esc_html_e( 'Secure checkout', 'jwellery-jewelry' ); ?></span>
		<span><?php esc_html_e( 'Easy returns', 'jwellery-jewelry' ); ?></span>
	</div>
	<?php
}

/**
 * Legacy cart icon fragment (fallback when drawer disabled).
 *
 * @param array $fragments Fragments.
 * @return array
 */
function jwellery_cart_icon_fragments( $fragments ) {
	if ( get_theme_mod( 'jwellery_enable_cart_drawer', true ) ) {
		return $fragments;
	}
	ob_start();
	jwellery_cart_icon_link();
	$fragments['a.jwellery-cart-icon'] = ob_get_clean();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'jwellery_cart_icon_fragments' );



