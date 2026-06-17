<?php
/**
 * Homepage-first catalog + polished main shop page (Option 3).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether storefront uses homepage as primary catalog.
 *
 * @return bool
 */
function jwellery_homepage_first_storefront() {
	return (bool) get_theme_mod( 'jwellery_homepage_first_storefront', true );
}

/**
 * URL for "All Products" — homepage anchor when enabled, else shop page.
 *
 * @return string
 */
function jwellery_all_products_url() {
	if ( jwellery_homepage_first_storefront() && 'page' === get_option( 'show_on_front' ) ) {
		return home_url( '/#all-products' );
	}
	return jwellery_get_shop_url();
}

/**
 * Main shop page without filters (grouped catalog layout).
 *
 * @return bool
 */
function jwellery_is_main_shop_catalog() {
	if ( ! function_exists( 'is_shop' ) || ! is_shop() ) {
		return false;
	}
	if ( is_product_category() || is_product_tag() || is_search() ) {
		return false;
	}
	if ( ! (bool) get_theme_mod( 'jwellery_shop_grouped_catalog', true ) ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$get = wp_unslash( $_GET );
	if ( ! empty( $get['min_price'] ) || ! empty( $get['max_price'] ) || ! empty( $get['featured'] ) ) {
		return false;
	}
	if ( ! empty( $get['orderby'] ) && 'menu_order' !== $get['orderby'] ) {
		return false;
	}
	return true;
}

/**
 * All published catalog products with images.
 *
 * @param bool $instock_only Only in-stock items.
 * @return WC_Product[]
 */
function jwellery_get_all_catalog_products( $instock_only = false ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$args = array(
		'status'  => 'publish',
		'limit'   => -1,
		'orderby' => 'menu_order',
		'order'   => 'ASC',
	);
	if ( $instock_only ) {
		$args['stock_status'] = 'instock';
	}

	$products = wc_get_products( $args );
	if ( function_exists( 'jwellery_filter_products_with_images' ) ) {
		$products = jwellery_filter_products_with_images( $products );
	}
	return is_array( $products ) ? $products : array();
}

/**
 * Homepage — full catalog grid with optional load more.
 */
function jwellery_home_all_products() {
	if ( ! function_exists( 'jwellery_render_product_card' ) ) {
		return;
	}

	$products = jwellery_get_all_catalog_products( false );
	if ( empty( $products ) ) {
		return;
	}

	$per_page = max( 4, (int) get_theme_mod( 'jwellery_all_products_per_page', 12 ) );
	$per_page = (int) floor( $per_page / jwellery_home_grid_columns() ) * jwellery_home_grid_columns();
	$total    = count( $products );
	$shop_url = jwellery_get_shop_url();
	?>
	<section id="all-products" class="jwellery-home-section jwellery-home-section--all-products jwellery-catalog-section">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'All Products', 'jwellery-jewelry' ),
					array(
						'center'    => true,
						'eyebrow'   => __( 'Shop the full catalog', 'jwellery-jewelry' ),
						'subtitle'  => __( 'Add to cart directly — no need to visit the shop page', 'jwellery-jewelry' ),
						'link'      => $shop_url,
						'link_text' => __( 'Browse on shop page', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<ul class="products jwellery-product-grid jwellery-product-grid--static jwellery-product-grid--cols-4 jwellery-all-products-grid" data-all-products-grid>
				<?php
				foreach ( $products as $index => $product ) {
					$extra = $index >= $per_page ? 'is-catalog-hidden' : '';
					jwellery_render_product_card( $product, $extra );
				}
				?>
			</ul>
			<?php if ( $total > $per_page ) : ?>
				<p class="jwellery-catalog-load-more-wrap">
					<button type="button" class="jwellery-btn jwellery-btn-outline" data-all-products-load-more data-hidden-count="<?php echo (int) ( $total - $per_page ); ?>">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of hidden products */
								__( 'Load more (%d)', 'jwellery-jewelry' ),
								$total - $per_page
							)
						);
						?>
					</button>
				</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Shop page intro banner.
 */
function jwellery_shop_catalog_intro() {
	if ( ! jwellery_is_main_shop_catalog() ) {
		return;
	}
	?>
	<section class="jwellery-shop-catalog-intro">
		<div class="container">
			<p class="jwellery-shop-catalog-eyebrow"><?php esc_html_e( 'Our collection', 'jwellery-jewelry' ); ?></p>
			<h1 class="jwellery-shop-catalog-title"><?php esc_html_e( 'Shop All Jewelry', 'jwellery-jewelry' ); ?></h1>
			<p class="jwellery-shop-catalog-desc">
				<?php esc_html_e( 'Browse every design by category. Add to cart, quick view, or save to wishlist — same experience as our homepage.', 'jwellery-jewelry' ); ?>
			</p>
			<?php if ( jwellery_homepage_first_storefront() ) : ?>
				<p class="jwellery-shop-catalog-home-link">
					<a href="<?php echo esc_url( jwellery_all_products_url() ); ?>"><?php esc_html_e( 'Shop on homepage', 'jwellery-jewelry' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Shop page — category browse + grouped grids (popular tabs stay on homepage only).
 */
function jwellery_shop_catalog_highlights() {
	if ( ! jwellery_is_main_shop_catalog() ) {
		return;
	}

	if ( function_exists( 'jwellery_home_category_stats' ) ) {
		jwellery_home_category_stats();
	}
}

/**
 * Trim products for a shop/home section grid (4 cols, full rows, max 2 rows).
 *
 * @param WC_Product[] $products Products.
 * @param int|null     $rows     Max rows.
 * @return WC_Product[]
 */
function jwellery_shop_section_products( $products, $rows = null ) {
	if ( ! is_array( $products ) || empty( $products ) ) {
		return array();
	}
	if ( ! function_exists( 'jwellery_trim_products_to_full_rows' ) ) {
		return $products;
	}
	$rows = $rows ? (int) $rows : ( function_exists( 'jwellery_home_grid_rows' ) ? jwellery_home_grid_rows() : 2 );
	return jwellery_trim_products_to_full_rows( $products, jwellery_home_grid_columns(), $rows );
}

/**
 * Products grouped by top-level category for main shop page.
 *
 * @return array<int, array{term: WP_Term|null, title: string, products: WC_Product[]}>
 */
function jwellery_get_products_grouped_by_category() {
	$groups   = array();
	$assigned = array();

	if ( ! function_exists( 'jwellery_get_shop_categories' ) ) {
		return $groups;
	}

	$terms = jwellery_get_shop_categories();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$raw = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => -1,
				'category' => array( $term->slug ),
				'orderby'  => 'menu_order',
				'order'    => 'ASC',
			)
		);

		$products = array();
		foreach ( $raw as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			if ( function_exists( 'jwellery_product_has_image' ) && ! jwellery_product_has_image( $product ) ) {
				continue;
			}
			$pid = $product->get_id();
			if ( isset( $assigned[ $pid ] ) ) {
				continue;
			}
			$assigned[ $pid ] = true;
			$products[]       = $product;
		}

		if ( ! empty( $products ) ) {
			$products = jwellery_shop_section_products( $products );
			if ( count( $products ) < jwellery_home_grid_columns() ) {
				continue;
			}
			$groups[] = array(
				'term'     => $term,
				'title'    => $term->name,
				'products' => $products,
			);
		}
	}

	return $groups;
}

/**
 * Render grouped category sections on main shop page.
 */
function jwellery_render_grouped_shop_catalog() {
	if ( ! jwellery_is_main_shop_catalog() ) {
		return;
	}

	$groups = jwellery_get_products_grouped_by_category();
	if ( empty( $groups ) ) {
		return;
	}
	?>
	<div class="jwellery-shop-grouped-catalog">
		<?php foreach ( $groups as $group ) : ?>
			<?php
			$term  = $group['term'];
			$link  = ( $term instanceof WP_Term ) ? get_term_link( $term ) : '';
			$count = count( $group['products'] );
			?>
			<section class="jwellery-home-section jwellery-home-section--grid jwellery-shop-category-group jwellery-catalog-section" id="<?php echo esc_attr( 'shop-cat-' . $term->slug ); ?>">
				<div class="container">
					<?php
					if ( function_exists( 'jwellery_section_header' ) ) {
						$header_args = array(
							'center'   => true,
							'eyebrow'  => __( 'Collection', 'jwellery-jewelry' ),
							'subtitle' => sprintf(
								/* translators: %d: product count */
								_n( '%d design', '%d designs', $count, 'jwellery-jewelry' ),
								$count
							),
						);
						if ( $link && ! is_wp_error( $link ) ) {
							$header_args['link']      = $link;
							$header_args['link_text'] = __( 'View category', 'jwellery-jewelry' );
						}
						jwellery_section_header( $group['title'], $header_args );
					}
					?>
					<ul class="products jwellery-product-grid jwellery-product-grid--static jwellery-product-grid--cols-4" data-animate="carousel">
						<?php
						foreach ( $group['products'] as $product ) {
							jwellery_render_product_card( $product );
						}
						?>
					</ul>
				</div>
			</section>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Hide default shop title when custom intro is shown.
 *
 * @param bool $show Show title.
 * @return bool
 */
function jwellery_shop_catalog_show_page_title( $show ) {
	if ( jwellery_is_main_shop_catalog() ) {
		return false;
	}
	return $show;
}

/**
 * Body class for shop catalog layout CSS.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function jwellery_shop_catalog_body_class( $classes ) {
	if ( jwellery_is_main_shop_catalog() ) {
		$classes[] = 'jwellery-shop-catalog-page';
	}
	return $classes;
}
add_filter( 'body_class', 'jwellery_shop_catalog_body_class' );

/**
 * Hide default "no products" message on grouped shop (custom sections render instead).
 */
function jwellery_shop_catalog_hide_no_products() {
	if ( jwellery_is_main_shop_catalog() ) {
		remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
	}
}
add_action( 'woocommerce_before_main_content', 'jwellery_shop_catalog_hide_no_products', 5 );

/**
 * Register shop catalog hooks.
 */
function jwellery_shop_catalog_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_filter( 'woocommerce_show_page_title', 'jwellery_shop_catalog_show_page_title' );
	add_action( 'woocommerce_before_main_content', 'jwellery_shop_catalog_intro', 12 );
	add_action( 'woocommerce_before_main_content', 'jwellery_shop_catalog_highlights', 18 );
	add_action( 'woocommerce_before_main_content', 'jwellery_render_grouped_shop_catalog', 22 );
}
add_action( 'woocommerce_init', 'jwellery_shop_catalog_init', 25 );
