<?php
/**
 * Shop UX — quick view, mega menu, mini cart drawer + free shipping bar.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Disable page caching for all pages that show WooCommerce products.
 * Uses LiteSpeed Cache plugin API + standard headers.
 */
function jwellery_no_cache_woo_pages() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}

	$is_woo_page = is_woocommerce() || is_cart() || is_checkout() || is_account_page()
		|| is_front_page() || is_home();

	if ( $is_woo_page ) {
		// LiteSpeed Cache plugin API — most reliable way on Hostinger.
		do_action( 'litespeed_control_set_nocache', 'woocommerce_page' );
		// HTTP header fallback.
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: Thu, 01 Jan 1970 00:00:00 GMT' );
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
}
add_action( 'template_redirect', 'jwellery_no_cache_woo_pages', 1 );

/**
 * When a product is saved or trashed, purge the homepage, shop page,
 * and the product URL from LiteSpeed cache so new prices/status show immediately.
 */
function jwellery_purge_product_cache( $post_id ) {
	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	// Clear WooCommerce internal transients.
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $post_id );
	}
	delete_transient( 'wc_products_onsale' );
	delete_post_meta( $post_id, '_price_html' );

	// Purge via LiteSpeed Cache plugin API (works on Hostinger).
	do_action( 'litespeed_purge_all' );
}
add_action( 'save_post', 'jwellery_purge_product_cache' );
add_action( 'wp_trash_post', 'jwellery_purge_product_cache' );
add_action( 'untrash_post', 'jwellery_purge_product_cache' );
add_action( 'delete_post', 'jwellery_purge_product_cache' );

/**
 * Free shipping threshold (INR). 0 = free shipping on every order.
 *
 * @return float
 */
function jwellery_free_shipping_threshold() {
	return max( 0, (float) get_theme_mod( 'jwellery_free_shipping_min', 0 ) );
}

/**
 * Whether the store offers free shipping on all orders (no minimum).
 *
 * @return bool
 */
function jwellery_free_shipping_on_all_orders() {
	return jwellery_free_shipping_threshold() <= 0;
}

/**
 * Cart subtotal for progress bar.
 *
 * @return float
 */
function jwellery_cart_subtotal_amount() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}
	return (float) WC()->cart->get_subtotal();
}

/**
 * Free shipping progress data.
 *
 * @return array{percent: int, remaining: float, qualified: bool, threshold: float}
 */
function jwellery_free_shipping_progress() {
	$threshold = jwellery_free_shipping_threshold();
	$subtotal  = jwellery_cart_subtotal_amount();

	if ( jwellery_free_shipping_on_all_orders() ) {
		return array(
			'percent'   => 100,
			'remaining' => 0,
			'qualified' => true,
			'threshold' => 0,
		);
	}

	$remaining = max( 0, $threshold - $subtotal );
	$percent   = $threshold > 0 ? min( 100, (int) round( ( $subtotal / $threshold ) * 100 ) ) : 100;

	return array(
		'percent'   => $percent,
		'remaining' => $remaining,
		'qualified' => $subtotal >= $threshold,
		'threshold' => $threshold,
	);
}

/**
 * Shipping progress bar HTML.
 *
 * @return string
 */
function jwellery_shipping_progress_html() {
	$data     = jwellery_free_shipping_progress();
	$subtotal = jwellery_cart_subtotal_amount();

	if ( ! jwellery_free_shipping_on_all_orders() && $subtotal <= 0 ) {
		return '';
	}

	ob_start();
	?>
	<div class="jwellery-shipping-progress" data-shipping-progress>
		<?php if ( jwellery_free_shipping_on_all_orders() || $data['qualified'] ) : ?>
			<p class="jwellery-shipping-progress-msg jwellery-shipping-progress-msg--success">
				<?php
				if ( jwellery_free_shipping_on_all_orders() ) {
					esc_html_e( 'Free shipping on all orders across India', 'jwellery-jewelry' );
				} else {
					esc_html_e( 'You qualify for FREE shipping!', 'jwellery-jewelry' );
				}
				?>
			</p>
		<?php else : ?>
			<p class="jwellery-shipping-progress-msg">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: amount remaining */
						__( 'Add %s more for <strong>FREE shipping</strong>', 'jwellery-jewelry' ),
						wc_price( $data['remaining'] )
					)
				);
				?>
			</p>
		<?php endif; ?>
		<div class="jwellery-shipping-progress-track" aria-hidden="true">
			<span class="jwellery-shipping-progress-fill" style="width:<?php echo (int) $data['percent']; ?>%"></span>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Zero shipping cost at checkout when free shipping applies to all orders.
 *
 * @param array $rates   Package rates.
 * @param array $package Package.
 * @return array
 */
function jwellery_apply_free_shipping_rates( $rates, $package ) {
	unset( $package );
	if ( ! jwellery_free_shipping_on_all_orders() || empty( $rates ) ) {
		return $rates;
	}

	foreach ( $rates as $rate_id => $rate ) {
		if ( is_object( $rate ) && method_exists( $rate, 'set_cost' ) ) {
			$rate->set_cost( 0 );
			$rates[ $rate_id ] = $rate;
		}
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'jwellery_apply_free_shipping_rates', 100, 2 );

/**
 * One-time: switch storefront to free shipping on all orders (removes old ₹999 minimum).
 */
function jwellery_bootstrap_free_shipping_all_orders() {
	$done = (string) get_option( 'jwellery_free_shipping_all_orders_ver', '' );
	if ( $done === JWELLERY_THEME_VERSION ) {
		return;
	}

	set_theme_mod( 'jwellery_free_shipping_min', 0 );
	update_option( 'jwellery_free_shipping_all_orders_ver', JWELLERY_THEME_VERSION, false );
}
add_action( 'after_setup_theme', 'jwellery_bootstrap_free_shipping_all_orders', 28 );

/**
 * Product categories for Shop mega menu (all top-level, preferred order).
 *
 * @return WP_Term[]
 */
function jwellery_get_shop_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$preferred = array(
		'ear-rings',
		'studs',
		'necklaces',
		'chockers',
		'bangles',
		'rings',
		'long-harams',
		'handmade-collection',
		'instagram-collection',
		'latest-collection',
		'combo',
	);

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'parent'     => 0,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
	}

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$terms = array_values(
		array_filter(
			$terms,
			static function ( $term ) {
				return $term instanceof WP_Term && 'uncategorized' !== $term->slug;
			}
		)
	);

	usort(
		$terms,
		static function ( $a, $b ) use ( $preferred ) {
			$pos_a = array_search( $a->slug, $preferred, true );
			$pos_b = array_search( $b->slug, $preferred, true );
			$pos_a = false === $pos_a ? 999 : (int) $pos_a;
			$pos_b = false === $pos_b ? 999 : (int) $pos_b;
			if ( $pos_a === $pos_b ) {
				return strcasecmp( $a->name, $b->name );
			}
			return $pos_a - $pos_b;
		}
	);

	return $terms;
}

/**
 * Mega menu panel HTML.
 *
 * @return string
 */
function jwellery_shop_uses_mega_menu() {
	return class_exists( 'WooCommerce' ) && (bool) get_theme_mod( 'jwellery_enable_mega_menu', false );
}

/**
 * Classic Shop dropdown links (matches reference site).
 *
 * @return string
 */
function jwellery_shop_submenu_html() {
	$shop     = jwellery_get_shop_url();
	$all_url  = function_exists( 'jwellery_all_products_url' ) ? jwellery_all_products_url() : $shop;
	ob_start();
	?>
	<ul class="sub-menu jwellery-shop-submenu">
		<li class="jwellery-shop-submenu__item"><a href="<?php echo esc_url( jwellery_term_link( 'handmade-collection' ) ); ?>"><?php esc_html_e( 'Handmade Collection', 'jwellery-jewelry' ); ?></a></li>
		<li class="jwellery-shop-submenu__item"><a href="<?php echo esc_url( jwellery_term_link( 'instagram-collection' ) ); ?>"><?php esc_html_e( 'Instagram Collection', 'jwellery-jewelry' ); ?></a></li>
		<li class="jwellery-shop-submenu__item"><a href="<?php echo esc_url( add_query_arg( 'featured', '1', $shop ) ); ?>"><?php esc_html_e( 'Best Sellers', 'jwellery-jewelry' ); ?></a></li>
		<li class="jwellery-shop-submenu__item"><a href="<?php echo esc_url( jwellery_term_link( 'latest-collection' ) ); ?>"><?php esc_html_e( 'Latest Collection', 'jwellery-jewelry' ); ?></a></li>
		<li class="jwellery-shop-submenu__item jwellery-shop-submenu__item--divider"><a href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'All Collections', 'jwellery-jewelry' ); ?></a></li>
		<li class="jwellery-shop-submenu__item jwellery-shop-submenu__item--cta"><a href="<?php echo esc_url( $all_url ); ?>"><?php esc_html_e( 'All Products', 'jwellery-jewelry' ); ?></a></li>
	</ul>
	<?php
	return ob_get_clean();
}

/**
 * Mega menu panel HTML.
 *
 * @return string
 */
function jwellery_mega_menu_html() {
	if ( ! jwellery_shop_uses_mega_menu() ) {
		return '';
	}

	$cats = jwellery_get_shop_categories();

	$featured = array();
	if ( function_exists( 'wc_get_products' ) ) {
		$featured = function_exists( 'jwellery_get_products_for_display' )
			? jwellery_get_products_for_display(
				array(
					'status'       => 'publish',
					'stock_status' => 'instock',
					'featured'     => true,
				),
				2,
				1
			)
			: array();
		if ( count( $featured ) < 2 ) {
			$featured = function_exists( 'jwellery_get_products_for_display' )
				? jwellery_get_products_for_display(
					array(
						'status'       => 'publish',
						'stock_status' => 'instock',
						'orderby'      => 'date',
						'order'        => 'DESC',
					),
					2,
					1
				)
				: array();
		}
	}

	ob_start();
	?>
	<div class="jwellery-mega-menu" id="jwellery-mega-menu">
		<div class="jwellery-mega-menu-inner">
			<div class="jwellery-mega-col jwellery-mega-col--cats">
				<p class="jwellery-mega-label"><?php esc_html_e( 'Categories', 'jwellery-jewelry' ); ?></p>
				<ul class="jwellery-mega-cats">
					<?php foreach ( $cats as $term ) : ?>
						<li>
							<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
								<?php echo esc_html( $term->name ); ?>
								<span class="jwellery-mega-count"><?php echo (int) $term->count; ?></span>
							</a>
						</li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( function_exists( 'jwellery_all_products_url' ) ? jwellery_all_products_url() : jwellery_get_shop_url() ); ?>"><?php esc_html_e( 'All Products', 'jwellery-jewelry' ); ?></a></li>
				</ul>
			</div>
			<div class="jwellery-mega-col jwellery-mega-col--links">
				<p class="jwellery-mega-label"><?php esc_html_e( 'Collections', 'jwellery-jewelry' ); ?></p>
				<ul class="jwellery-mega-links">
					<li><a href="<?php echo esc_url( jwellery_term_link( 'handmade-collection' ) ); ?>"><?php esc_html_e( 'Handmade Collection', 'jwellery-jewelry' ); ?></a></li>
					<li><a href="<?php echo esc_url( jwellery_term_link( 'instagram-collection' ) ); ?>"><?php esc_html_e( 'Instagram Collection', 'jwellery-jewelry' ); ?></a></li>
					<li><a href="<?php echo esc_url( add_query_arg( 'featured', '1', jwellery_get_shop_url() ) ); ?>"><?php esc_html_e( 'Best Sellers', 'jwellery-jewelry' ); ?></a></li>
					<?php if ( function_exists( 'jwellery_budget_shop_url' ) ) : ?>
						<li><a href="<?php echo esc_url( jwellery_budget_shop_url( 499 ) ); ?>"><?php esc_html_e( 'Under ₹499', 'jwellery-jewelry' ); ?></a></li>
					<?php endif; ?>
				</ul>
			</div>
			<?php if ( ! empty( $featured ) ) : ?>
				<div class="jwellery-mega-col jwellery-mega-col--products">
					<p class="jwellery-mega-label"><?php esc_html_e( 'Featured', 'jwellery-jewelry' ); ?></p>
					<div class="jwellery-mega-products">
						<?php foreach ( $featured as $product ) : ?>
							<a class="jwellery-mega-product" href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<?php echo $product->get_image( 'thumbnail' ); // phpcs:ignore ?>
								<span class="jwellery-mega-product-name"><?php echo esc_html( $product->get_name() ); ?></span>
								<span class="jwellery-mega-product-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Mark Shop menu items for mega menu.
 *
 * @param array $items Menu items.
 * @return array
 */
function jwellery_nav_menu_mega_class( $items ) {
	if ( ! jwellery_shop_uses_mega_menu() ) {
		return $items;
	}
	foreach ( $items as $item ) {
		if ( 0 === (int) $item->menu_item_parent && ( false !== stripos( $item->title, 'shop' ) || in_array( 'shop', (array) $item->classes, true ) ) ) {
			$item->classes[] = 'menu-item-has-mega';
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'jwellery_nav_menu_mega_class' );

/**
 * Style class for Shop dropdown panel (WP menu + fallback).
 *
 * @param string[] $classes CSS classes.
 * @param stdClass $args    Menu args.
 * @param int      $depth   Parent depth.
 * @return string[]
 */
function jwellery_shop_submenu_panel_class( $classes, $args, $depth ) {
	unset( $args );
	if ( 0 === (int) $depth ) {
		$classes[] = 'jwellery-shop-submenu';
	}
	return $classes;
}
add_filter( 'nav_menu_submenu_css_class', 'jwellery_shop_submenu_panel_class', 10, 3 );

/**
 * Highlight All Products / divider rows in Shop submenu.
 *
 * @param string[] $classes CSS classes.
 * @param WP_Post  $item    Menu item.
 * @param stdClass $args    Menu args.
 * @param int      $depth   Depth.
 * @return string[]
 */
function jwellery_shop_submenu_item_class( $classes, $item, $args, $depth ) {
	unset( $args );
	if ( 1 !== (int) $depth || 0 === (int) $item->menu_item_parent ) {
		return $classes;
	}

	$classes[] = 'jwellery-shop-submenu__item';
	$title     = strtolower( trim( (string) $item->title ) );

	if ( false !== strpos( $title, 'all product' ) ) {
		$classes[] = 'jwellery-shop-submenu__item--cta';
	} elseif ( false !== strpos( $title, 'all collection' ) ) {
		$classes[] = 'jwellery-shop-submenu__item--divider';
	}

	return $classes;
}
add_filter( 'nav_menu_css_class', 'jwellery_shop_submenu_item_class', 10, 4 );

/**
 * Append mega menu to Shop nav item.
 *
 * @param string $output Item HTML.
 * @param object $item   Menu item.
 * @param int    $depth  Depth.
 * @param object $args   Args.
 * @return string
 */
function jwellery_nav_menu_append_mega( $output, $item, $depth, $args ) {
	if ( 0 !== (int) $depth || ! in_array( 'menu-item-has-mega', (array) $item->classes, true ) ) {
		return $output;
	}
	$mega = jwellery_mega_menu_html();
	if ( $mega && false !== strpos( $output, '</li>' ) ) {
		$output = str_replace( '</li>', $mega . '</li>', $output );
	}
	return $output;
}
add_filter( 'walker_nav_menu_start_el', 'jwellery_nav_menu_append_mega', 10, 4 );

/**
 * Quick view button on product cards.
 *
 * @param WC_Product $product Product.
 */
function jwellery_quick_view_button( $product ) {
	if ( ! get_theme_mod( 'jwellery_enable_quick_view', true ) || ! $product ) {
		return;
	}
	printf(
		'<button type="button" class="jwellery-quick-view-btn" data-product-id="%d" aria-label="%s">%s</button>',
		(int) $product->get_id(),
		esc_attr( sprintf( /* translators: %s: product name */ __( 'Quick view %s', 'jwellery-jewelry' ), $product->get_name() ) ),
		esc_html__( 'Quick view', 'jwellery-jewelry' )
	);
}

/**
 * AJAX quick view content.
 */
function jwellery_ajax_quick_view() {
	check_ajax_referer( 'jwellery_shop', 'nonce' );

	$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
	$product    = $product_id ? wc_get_product( $product_id ) : null;

	if ( ! $product || 'publish' !== $product->get_status() ) {
		wp_send_json_error( array( 'message' => __( 'Product not found.', 'jwellery-jewelry' ) ), 404 );
	}

	ob_start();
	?>
	<div class="jwellery-qv-gallery">
		<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<?php echo $product->get_image( 'woocommerce_single' ); // phpcs:ignore ?>
		</a>
		<?php
		if ( function_exists( 'jwellery_product_sale_badge_html' ) ) {
			echo jwellery_product_sale_badge_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
	<div class="jwellery-qv-body">
		<h2 id="jwellery-qv-title" class="jwellery-qv-title"><?php echo esc_html( $product->get_name() ); ?></h2>
		<p class="jwellery-qv-price price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
		<?php if ( $product->get_short_description() ) : ?>
			<div class="jwellery-qv-desc"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
		<?php endif; ?>
		<div class="jwellery-qv-actions">
			<?php if ( $product->is_in_stock() && $product->is_purchasable() ) : ?>
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="jwellery-btn jwellery-btn-primary add_to_cart_button ajax_add_to_cart product_type_<?php echo esc_attr( $product->get_type() ); ?>" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>">
					<?php echo esc_html( $product->add_to_cart_text() ); ?>
				</a>
			<?php else : ?>
				<span class="badge-sold-out"><?php esc_html_e( 'Sold out', 'jwellery-jewelry' ); ?></span>
			<?php endif; ?>
			<a class="jwellery-btn jwellery-btn-outline jwellery-qv-full" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php esc_html_e( 'View full details', 'jwellery-jewelry' ); ?></a>
		</div>
	</div>
	<?php
	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}
add_action( 'wp_ajax_jwellery_quick_view', 'jwellery_ajax_quick_view' );
add_action( 'wp_ajax_nopriv_jwellery_quick_view', 'jwellery_ajax_quick_view' );

/**
 * Quick view modal shell.
 */
function jwellery_quick_view_modal() {
	if ( ! class_exists( 'WooCommerce' ) || ! get_theme_mod( 'jwellery_enable_quick_view', true ) ) {
		return;
	}
	?>
	<div id="jwellery-quick-view" class="jwellery-quick-view" role="dialog" aria-modal="true" aria-labelledby="jwellery-qv-title" hidden>
		<div class="jwellery-quick-view-backdrop" data-qv-close></div>
		<div class="jwellery-quick-view-panel">
			<button type="button" class="jwellery-quick-view-close" data-qv-close aria-label="<?php esc_attr_e( 'Close', 'jwellery-jewelry' ); ?>">&times;</button>
			<div class="jwellery-quick-view-content jwellery-quick-view-inner"></div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'jwellery_quick_view_modal', 8 );

/**
 * Mini cart drawer inner HTML.
 *
 * @return string
 */
function jwellery_cart_drawer_inner_html() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}

	ob_start();
	echo jwellery_shipping_progress_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	if ( WC()->cart->is_empty() ) {
		?>
		<p class="jwellery-cart-drawer-empty"><?php esc_html_e( 'Your cart is empty.', 'jwellery-jewelry' ); ?></p>
		<a class="jwellery-btn jwellery-btn-primary jwellery-cart-drawer-shop" href="<?php echo esc_url( function_exists( 'jwellery_all_products_url' ) ? jwellery_all_products_url() : jwellery_get_shop_url() ); ?>"><?php esc_html_e( 'Start shopping', 'jwellery-jewelry' ); ?></a>
		<?php
		return ob_get_clean();
	}
	?>
	<ul class="jwellery-cart-drawer-items">
		<?php
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				continue;
			}
			$remove_label = sprintf(
				/* translators: %s: product name */
				__( 'Remove %s from cart', 'jwellery-jewelry' ),
				wp_strip_all_tags( $product->get_name() )
			);
			?>
			<li class="jwellery-cart-drawer-item">
				<span class="jwellery-cart-drawer-thumb jwellery-cart-thumb-fallback" aria-hidden="true">&#9679;</span>
				<div class="jwellery-cart-drawer-item-body">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
					<span class="jwellery-cart-drawer-qty"><?php echo esc_html( sprintf( /* translators: %d: quantity */ __( 'Qty: %d', 'jwellery-jewelry' ), (int) $cart_item['quantity'] ) ); ?></span>
					<span class="jwellery-cart-drawer-line-price"><?php echo wp_kses_post( WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ) ); ?></span>
					<?php
					echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'woocommerce_cart_item_remove_link',
						sprintf(
							'<a role="button" href="%s" class="remove remove_from_cart_button jwellery-cart-drawer-remove" aria-label="%s" data-product_id="%s" data-product_sku="%s" data-cart_item_key="%s">%s</a>',
							esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
							esc_attr( $remove_label ),
							esc_attr( (string) $product->get_id() ),
							esc_attr( (string) $product->get_sku() ),
							esc_attr( $cart_item_key ),
							esc_html__( 'Remove', 'jwellery-jewelry' )
						),
						$cart_item_key
					);
					?>
				</div>
			</li>
			<?php
		}
		?>
	</ul>
	<div class="jwellery-cart-drawer-footer">
		<p class="jwellery-cart-drawer-subtotal">
			<strong><?php esc_html_e( 'Subtotal', 'jwellery-jewelry' ); ?></strong>
			<span><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></span>
		</p>
		<div class="jwellery-cart-drawer-actions">
			<a class="jwellery-btn jwellery-btn-primary jwellery-cart-drawer-checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Proceed to checkout', 'jwellery-jewelry' ); ?></a>
			<a class="jwellery-btn jwellery-btn-outline jwellery-cart-drawer-view" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'jwellery-jewelry' ); ?></a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Mini cart drawer shell.
 */
function jwellery_cart_drawer_shell() {
	if ( ! class_exists( 'WooCommerce' ) || ! get_theme_mod( 'jwellery_enable_cart_drawer', true ) ) {
		return;
	}
	?>
	<div id="jwellery-cart-drawer" class="jwellery-cart-drawer" aria-hidden="true" hidden>
		<div class="jwellery-cart-drawer-backdrop" data-cart-close></div>
		<div class="jwellery-cart-drawer-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'jwellery-jewelry' ); ?>">
			<header class="jwellery-cart-drawer-head">
				<h2><?php esc_html_e( 'Your Cart', 'jwellery-jewelry' ); ?></h2>
				<button type="button" class="jwellery-cart-drawer-close" data-cart-close aria-label="<?php esc_attr_e( 'Close cart', 'jwellery-jewelry' ); ?>">&times;</button>
			</header>
			<div class="jwellery-cart-drawer-inner">
				<?php echo jwellery_cart_drawer_inner_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'jwellery_cart_drawer_shell', 9 );

/**
 * Cart drawer + icon fragments.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function jwellery_cart_drawer_fragments( $fragments ) {
	if ( get_theme_mod( 'jwellery_enable_cart_drawer', true ) ) {
		ob_start();
		echo jwellery_cart_drawer_inner_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$fragments['div.jwellery-cart-drawer-inner'] = '<div class="jwellery-cart-drawer-inner">' . ob_get_clean() . '</div>';
	}
	ob_start();
	jwellery_cart_toggle_button();
	$fragments['button.jwellery-header-cart-toggle'] = ob_get_clean();
	if ( function_exists( 'jwellery_mobile_bar_cart_icon_wrap_html' ) ) {
		$fragments['.jwellery-mobile-bar-cart .jwellery-mobile-bar-icon-wrap'] = jwellery_mobile_bar_cart_icon_wrap_html();
	}
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'jwellery_cart_drawer_fragments', 20 );

/**
 * Shop/archive loops use woocommerce/content-product.php → jwellery_render_product_card().
 * Do not hook before_shop_loop_item_title here — it conflicts with the default WC
 * template and caused fatal errors on /shop/, upsells, and related products.
 */

/**
 * Cart toggle button (opens drawer).
 */
function jwellery_cart_toggle_button() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$count = WC()->cart->get_cart_contents_count();
	if ( ! get_theme_mod( 'jwellery_enable_cart_drawer', true ) ) {
		jwellery_cart_icon_link();
		return;
	}
	$icon = function_exists( 'jwellery_icon_svg' ) ? jwellery_icon_svg( 'cart', 22 ) : '';
	printf(
		'<button type="button" class="jwellery-header-icon jwellery-cart-icon jwellery-cart-toggle jwellery-header-cart-toggle" aria-expanded="false" aria-controls="jwellery-cart-drawer" title="%s"><span class="screen-reader-text">%s</span><span class="cart-icon" aria-hidden="true">%s</span><span class="cart-count-badge">%d</span></button>',
		esc_attr__( 'View cart', 'jwellery-jewelry' ),
		esc_html__( 'Cart', 'jwellery-jewelry' ),
		$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		(int) $count
	);
}

/**
 * Extend localize script data.
 *
 * @param array $data Data.
 * @return array
 */
function jwellery_shop_experience_script_data( $data ) {
	if ( ! is_array( $data ) ) {
		$data = array();
	}
	$data['ajaxUrl']           = admin_url( 'admin-ajax.php' );
	$data['nonce']             = wp_create_nonce( 'jwellery_shop' );
	$data['quickViewLoading']  = __( 'Loading…', 'jwellery-jewelry' );
	$data['freeShippingQualified'] = __( 'You qualify for FREE shipping!', 'jwellery-jewelry' );
	$data['scrollUp']            = __( 'Scroll to top', 'jwellery-jewelry' );
	$data['scrollDown']          = __( 'Scroll to bottom', 'jwellery-jewelry' );
	return apply_filters( 'jwellery_shop_experience_script_data', $data );
}

/**
 * WooCommerce cart fragments (remove-from-cart AJAX in drawer).
 */
function jwellery_enqueue_cart_drawer_scripts() {
	if ( ! class_exists( 'WooCommerce' ) || ! get_theme_mod( 'jwellery_enable_cart_drawer', true ) ) {
		return;
	}

	wp_enqueue_script( 'wc-cart-fragments' );
	wp_enqueue_script( 'wc-add-to-cart' );

	// WooCommerce only localizes wc_add_to_cart_params on WooCommerce pages.
	// Manually provide it on all pages so AJAX add-to-cart works on homepage etc.
	if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
		wp_localize_script(
			'wc-add-to-cart',
			'wc_add_to_cart_params',
			array(
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'wc_ajax_url'             => WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'i18n_view_cart'          => esc_attr__( 'View cart', 'woocommerce' ),
				'cart_url'                => apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null ),
				'is_cart'                 => is_cart() ? '1' : '0',
				'cart_redirect_after_add' => get_option( 'woocommerce_cart_redirect_after_add' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'jwellery_enqueue_cart_drawer_scripts', 25 );

/**
 * Hook into localize — replace function in ui-enhancements or add filter.
 */
function jwellery_localize_shop_experience() {
	wp_localize_script(
		'jwellery-theme',
		'jwelleryTheme',
		jwellery_shop_experience_script_data(
			array(
				'announcements' => function_exists( 'jwellery_announcement_messages' ) ? jwellery_announcement_messages() : array(),
				'addedToCart'   => __( 'Added to cart ✓', 'jwellery-jewelry' ),
				'removedFromCart' => __( 'Removed from cart', 'jwellery-jewelry' ),
				'addToCartError'  => __( 'Could not add to cart. Please try another product.', 'jwellery-jewelry' ),
				'carouselAuto'  => (bool) get_theme_mod( 'jwellery_carousel_autoplay', true ),
				'isLoggedIn'    => is_user_logged_in(),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'jwellery_shop' ),
				'quickViewLoading' => __( 'Loading…', 'jwellery-jewelry' ),
				'scrollUp'         => __( 'Scroll to top', 'jwellery-jewelry' ),
				'scrollDown'       => __( 'Scroll to bottom', 'jwellery-jewelry' ),
			)
		)
	);
}
add_action( 'wp_enqueue_scripts', 'jwellery_localize_shop_experience', 35 );
