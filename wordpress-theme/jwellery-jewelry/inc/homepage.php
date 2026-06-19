<?php
/**
 * Homepage sections — ramyanagendra.com layout.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Safe product category link.
 *
 * @param string $slug Category slug.
 * @return string
 */
function jwellery_term_link( $slug ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return jwellery_get_shop_url();
	}
	$link = get_term_link( $slug, 'product_cat' );
	if ( is_wp_error( $link ) ) {
		return jwellery_get_shop_url();
	}
	return $link;
}

/**
 * Render product card.
 *
 * @param WC_Product $product Product.
 * @param string     $extra_class Optional extra class on the li.
 */
function jwellery_render_product_card( $product, $extra_class = '' ) {
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	if ( function_exists( 'jwellery_product_has_image' ) && ! jwellery_product_has_image( $product ) ) {
		return;
	}
	$out_of_stock = ! $product->is_in_stock();
	$li_class     = 'product' . ( $out_of_stock ? ' is-sold-out' : '' );
	if ( $extra_class ) {
		$li_class .= ' ' . $extra_class;
	}
	?>
	<li class="<?php echo esc_attr( $li_class ); ?>">
		<span class="product-image-wrap">
			<?php
			if ( function_exists( 'jwellery_wishlist_button' ) ) {
				jwellery_wishlist_button( $product, 'loop' );
			}
			?>
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="jwellery-product-card-image" tabindex="-1" aria-hidden="true">
				<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore ?>
				<?php
				if ( function_exists( 'jwellery_product_sale_badge_html' ) ) {
					echo jwellery_product_sale_badge_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<?php if ( $out_of_stock ) : ?>
					<span class="badge-sold-out"><?php esc_html_e( 'Sold out', 'jwellery-jewelry' ); ?></span>
				<?php endif; ?>
			</a>
			<?php if ( function_exists( 'jwellery_quick_view_button' ) ) : ?>
				<span class="jwellery-card-hover-actions">
					<?php jwellery_quick_view_button( $product ); ?>
				</span>
			<?php endif; ?>
		</span>
		<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="jwellery-product-card">
			<h3 class="woocommerce-loop-product__title jwellery-product-title"><?php echo esc_html( $product->get_name() ); ?></h3>
			<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
		</a>
		<?php if ( ! $out_of_stock ) : ?>
			<div class="jwellery-loop-actions">
				<?php
				$aria = method_exists( $product, 'add_to_cart_description' )
					? $product->add_to_cart_description()
					: $product->add_to_cart_text();
				?>
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="button add_to_cart_button ajax_add_to_cart product_type_<?php echo esc_attr( $product->get_type() ); ?>" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php echo esc_attr( $aria ); ?>"><?php echo esc_html( $product->add_to_cart_text() ); ?></a>
			</div>
		<?php endif; ?>
	</li>
	<?php
}

/**
 * Product carousel section.
 *
 * @param string $title Section title.
 * @param array  $args  wc_get_products args.
 * @param string $link  View all URL.
 */
function jwellery_home_section( $title, $args, $link = '' ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return;
	}

	$base = array(
		'status'       => 'publish',
		'stock_status' => 'instock',
	);
	$products = function_exists( 'jwellery_get_products_for_display' )
		? jwellery_get_products_for_display( array_merge( $base, $args ), 4, 2 )
		: wc_get_products( array_merge( array( 'limit' => 8 ), $base, $args ) );
	if ( function_exists( 'jwellery_filter_unique_display_products' ) && is_front_page() ) {
		$products = jwellery_filter_unique_display_products(
			$products,
			array(
				'exclude_shown'  => true,
				'exclude_images' => true,
				'register'       => true,
			)
		);
	}
	if ( empty( $products ) ) {
		return;
	}

	$link   = $link ? $link : jwellery_get_shop_url();
	$uid    = 'carousel-' . sanitize_title( $title );
	$total  = count( $products );
	?>
	<section class="jwellery-home-section jwellery-home-section--carousel <?php echo esc_attr( function_exists( 'jwellery_section_class' ) ? jwellery_section_class( $title ) : '' ); ?>">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					$title,
					array(
						'center'    => true,
						'link'      => $link,
						'link_text' => __( 'View all', 'jwellery-jewelry' ),
						'eyebrow'   => __( 'Curated for you', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<div class="jwellery-carousel" data-carousel="<?php echo esc_attr( $uid ); ?>" data-animate="carousel">
				<button type="button" class="carousel-btn carousel-prev" aria-label="<?php esc_attr_e( 'Previous', 'jwellery-jewelry' ); ?>">‹</button>
				<div class="jwellery-carousel-track" id="<?php echo esc_attr( $uid ); ?>">
					<ul class="products jwellery-product-grid">
						<?php
						foreach ( $products as $product ) {
							jwellery_render_product_card( $product );
						}
						?>
					</ul>
				</div>
				<button type="button" class="carousel-btn carousel-next" aria-label="<?php esc_attr_e( 'Next', 'jwellery-jewelry' ); ?>">›</button>
			</div>
			<div class="carousel-dots" role="tablist" aria-label="<?php echo esc_attr( $title ); ?>">
				<?php for ( $d = 0; $d < $total; $d++ ) : ?>
					<button type="button" class="carousel-dot<?php echo 0 === $d ? ' is-active' : ''; ?>" data-index="<?php echo (int) $d; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Slide %d', 'jwellery-jewelry' ), $d + 1 ) ); ?>"></button>
				<?php endfor; ?>
			</div>
			<p class="carousel-counter"><span class="carousel-current">1</span> / <?php echo (int) $total; ?></p>
		</div>
	</section>
	<?php
}

/**
 * Top categories with descriptions.
 */
function jwellery_home_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$slugs = array( 'ear-rings', 'studs', 'necklaces', 'chockers', 'bangles', 'rings', 'long-harams' );
	$terms = array();
	foreach ( $slugs as $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $t && ! is_wp_error( $t ) ) {
			$terms[] = $t;
		}
	}

	if ( empty( $terms ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'number'     => 5,
				'hide_empty' => false,
				'parent'     => 0,
			)
		);
	}

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return;
	}

	$total = count( $terms );
	?>
	<section class="jwellery-home-section jwellery-categories jwellery-home-section--top-categories">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'Top Categories', 'jwellery-jewelry' ),
					array(
						'center'    => true,
						'link'      => jwellery_get_shop_url(),
						'link_text' => __( 'View all', 'jwellery-jewelry' ),
						'eyebrow'   => __( 'Shop by style', 'jwellery-jewelry' ),
						'subtitle'  => __( 'Earrings, necklaces, bangles & more', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<div class="jwellery-carousel jwellery-carousel--categories" data-carousel="categories" data-animate="carousel">
				<button type="button" class="carousel-btn carousel-prev" aria-label="<?php esc_attr_e( 'Previous', 'jwellery-jewelry' ); ?>">‹</button>
				<div class="jwellery-carousel-track">
					<div class="category-grid">
						<?php foreach ( $terms as $term ) : ?>
							<a class="category-card" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
								<?php echo jwellery_category_card_image_html( $term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<h3><?php echo esc_html( $term->name ); ?></h3>
								<?php if ( $term->description ) : ?>
									<p class="category-desc"><?php echo esc_html( wp_trim_words( $term->description, 18 ) ); ?></p>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
				<button type="button" class="carousel-btn carousel-next" aria-label="<?php esc_attr_e( 'Next', 'jwellery-jewelry' ); ?>">›</button>
			</div>
			<p class="carousel-counter">1 / <?php echo (int) $total; ?></p>
		</div>
	</section>
	<?php
}

/**
 * Product of the day (latest featured or newest product).
 */
function jwellery_home_product_of_day() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return;
	}

	$products = function_exists( 'jwellery_get_products_for_display' )
		? jwellery_get_products_for_display(
			array(
				'featured'     => true,
				'status'       => 'publish',
				'stock_status' => 'instock',
			),
			1,
			1
		)
		: array();

	if ( empty( $products ) ) {
		$products = function_exists( 'jwellery_get_products_for_display' )
			? jwellery_get_products_for_display(
				array(
					'status'       => 'publish',
					'stock_status' => 'instock',
					'orderby'      => 'date',
					'order'        => 'DESC',
				),
				1,
				1
			)
			: array();
	}

	if ( empty( $products ) ) {
		return;
	}

	$product = $products[0];
	?>
	<section class="jwellery-home-section jwellery-product-of-day jwellery-home-section--product-of-the-day">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'Product of the Day', 'jwellery-jewelry' ),
					array(
						'center'   => true,
						'eyebrow'  => __( "Today's pick", 'jwellery-jewelry' ),
						'subtitle' => __( 'Handpicked piece at a special value', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<div class="product-of-day-spotlight" data-animate="carousel">
				<a class="product-of-day-spotlight-media" href="<?php echo esc_url( $product->get_permalink() ); ?>">
					<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore ?>
					<?php if ( ! $product->is_in_stock() ) : ?>
						<span class="product-of-day-sold"><?php esc_html_e( 'Sold out', 'jwellery-jewelry' ); ?></span>
					<?php endif; ?>
				</a>
				<div class="product-of-day-spotlight-body">
					<span class="jwellery-section-eyebrow"><?php esc_html_e( "Today's deal", 'jwellery-jewelry' ); ?></span>
					<h3 class="product-of-day-spotlight-title">
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
					</h3>
					<p class="price product-of-day-spotlight-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
					<div class="product-of-day-actions">
						<?php if ( $product->is_in_stock() ) : ?>
							<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="jwellery-btn jwellery-btn-primary add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"><?php esc_html_e( 'Add to cart', 'jwellery-jewelry' ); ?></a>
						<?php else : ?>
							<span class="badge-sold-out product-of-day-actions__sold"><?php esc_html_e( 'Sold out', 'jwellery-jewelry' ); ?></span>
						<?php endif; ?>
						<a class="jwellery-btn jwellery-btn-outline product-of-day-view" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php esc_html_e( 'View details', 'jwellery-jewelry' ); ?></a>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Display name (hide Hostinger temp URL).
 *
 * @return string
 */
function jwellery_brand_name() {
	$custom = get_theme_mod( 'jwellery_brand_name', '' );
	if ( $custom ) {
		return $custom;
	}
	$name = get_bloginfo( 'name' );
	if ( false !== strpos( $name, 'hostingersite.com' ) ) {
		return __( 'Rudra Jewellery', 'jwellery-jewelry' );
	}
	return $name;
}

/**
 * Hero banner.
 */
function jwellery_home_hero() {
	$tagline = get_bloginfo( 'description' );
	if ( empty( $tagline ) || false !== strpos( $tagline, 'hostingersite' ) ) {
		$tagline = __( 'Handmade & traditional imitation jewelry', 'jwellery-jewelry' );
	}
	$slides = function_exists( 'jwellery_hero_slides' ) ? jwellery_hero_slides() : array();
	?>
	<?php $slide_count = count( $slides ); ?>
	<section class="jwellery-hero jwellery-hero--slider" data-hero-slider>
		<div class="jwellery-hero-slides">
			<?php foreach ( $slides as $i => $slide_url ) : ?>
				<div class="jwellery-hero-slide<?php echo 0 === $i ? ' is-active' : ''; ?>" style="background-image:url(<?php echo esc_url( $slide_url ); ?>)" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Hero image %d', 'jwellery-jewelry' ), $i + 1 ) ); ?>"></div>
			<?php endforeach; ?>
		</div>
		<div class="jwellery-hero-overlay"></div>
		<?php if ( $slide_count > 1 ) : ?>
			<button type="button" class="jwellery-hero-nav jwellery-hero-prev" aria-label="<?php esc_attr_e( 'Previous slide', 'jwellery-jewelry' ); ?>">‹</button>
			<button type="button" class="jwellery-hero-nav jwellery-hero-next" aria-label="<?php esc_attr_e( 'Next slide', 'jwellery-jewelry' ); ?>">›</button>
		<?php endif; ?>
		<div class="container jwellery-hero-inner">
			<?php
			$from_price = function_exists( 'jwellery_hero_from_price_html' ) ? jwellery_hero_from_price_html() : '';
			if ( $from_price ) :
				?>
				<p class="jwellery-hero-from jwellery-hero-reveal">
					<?php
					/* translators: %s: starting price */
					echo wp_kses_post( sprintf( __( 'from %s', 'jwellery-jewelry' ), $from_price ) );
					?>
				</p>
			<?php endif; ?>
			<p class="jwellery-hero-tag jwellery-hero-reveal"><?php esc_html_e( 'Imitation Jewelry', 'jwellery-jewelry' ); ?></p>
			<h1 class="jwellery-hero-reveal"><?php echo esc_html( jwellery_brand_name() ); ?></h1>
			<p class="jwellery-hero-desc jwellery-hero-reveal"><?php echo esc_html( $tagline ); ?></p>
			<div class="jwellery-hero-actions jwellery-hero-reveal">
				<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( function_exists( 'jwellery_all_products_url' ) ? jwellery_all_products_url() : jwellery_get_shop_url() ); ?>"><?php esc_html_e( 'Shop Now', 'jwellery-jewelry' ); ?></a>
				<a class="jwellery-btn jwellery-btn-hero-secondary" href="<?php echo esc_url( function_exists( 'jwellery_all_products_url' ) ? jwellery_all_products_url() : jwellery_get_shop_url() ); ?>"><?php esc_html_e( 'All Products', 'jwellery-jewelry' ); ?></a>
			</div>
		</div>
		<?php if ( $slide_count > 1 ) : ?>
			<div class="jwellery-hero-dots">
				<?php foreach ( $slides as $i => $slide_url ) : ?>
					<button type="button" class="jwellery-hero-dot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-slide="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Hero slide %d', 'jwellery-jewelry' ), $i + 1 ) ); ?>"></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Featured category banner — disabled (removed from homepage).
 */
function jwellery_home_featured_category() {
	return;
}
