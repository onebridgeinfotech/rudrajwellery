<?php
/**
 * Homepage sections styled like krishnamaalika.in
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shop URL filtered by max price (WooCommerce).
 *
 * @param int $max Max price INR.
 * @param int $min Min price INR.
 * @return string
 */
function jwellery_budget_shop_url( $max, $min = 0 ) {
	$url = jwellery_get_shop_url();
	return add_query_arg(
		array(
			'min_price' => (string) $min,
			'max_price' => (string) $max,
		),
		$url
	);
}

/**
 * Marquee announcement HTML (continuous scroll).
 */
function jwellery_render_announcement_marquee() {
	$messages = function_exists( 'jwellery_announcement_messages' ) ? jwellery_announcement_messages() : array();
	if ( empty( $messages ) ) {
		$messages = array( __( 'Free Shipping All Over India', 'jwellery-jewelry' ) );
	}
	$text = implode( ' &nbsp;|&nbsp; ', array_map( 'esc_html', $messages ) );
	$dup  = $text . ' &nbsp;|&nbsp; ' . $text;
	?>
	<div class="jwellery-announcement jwellery-announcement--marquee" aria-live="polite">
		<div class="jwellery-marquee">
			<div class="jwellery-marquee-track">
				<span><?php echo $dup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per message. ?></span>
				<span aria-hidden="true"><?php echo $dup; // phpcs:ignore ?></span>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Trust icons strip (below hero).
 */
function jwellery_home_trust_strip() {
	$items = array(
		array(
			'icon'  => 'truck',
			'title' => __( 'Free Shipping Across India', 'jwellery-jewelry' ),
			'desc'  => __( 'Free shipping all over India', 'jwellery-jewelry' ),
		),
		array(
			'icon'  => 'lock',
			'title' => __( 'Secure Payment', 'jwellery-jewelry' ),
			'desc'  => __( 'Safe UPI checkout', 'jwellery-jewelry' ),
		),
		array(
			'icon'  => 'support',
			'title' => __( '24/7 Support', 'jwellery-jewelry' ),
			'desc'  => __( 'WhatsApp & email help', 'jwellery-jewelry' ),
		),
		array(
			'icon'  => 'quality',
			'title' => __( 'Quality Assured', 'jwellery-jewelry' ),
			'desc'  => __( 'Premium jewelry designs', 'jwellery-jewelry' ),
		),
	);
	?>
	<section class="jwellery-trust-strip">
		<div class="container">
			<ul class="jwellery-trust-strip-grid">
				<?php foreach ( $items as $item ) : ?>
					<li>
						<span class="jwellery-trust-icon jwellery-trust-icon-wrap" aria-hidden="true">
							<?php
							if ( function_exists( 'jwellery_icon_svg' ) ) {
								echo jwellery_icon_svg( $item['icon'], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</span>
						<strong><?php echo esc_html( $item['title'] ); ?></strong>
						<span><?php echo esc_html( $item['desc'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}

/**
 * Shop by budget pills.
 */
function jwellery_home_shop_by_budget() {
	$budgets = array( 299, 499, 999, 1999 );
	?>
	<section class="jwellery-home-section jwellery-budget jwellery-home-section--shop-by-budget">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'Shop by Budget', 'jwellery-jewelry' ),
					array(
						'center'   => true,
						'eyebrow'  => __( 'Affordable picks', 'jwellery-jewelry' ),
						'subtitle' => __( 'Find beautiful jewelry within your price range', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<div class="jwellery-budget-grid" data-animate="carousel">
				<?php foreach ( $budgets as $max ) : ?>
					<a class="jwellery-budget-card" href="<?php echo esc_url( jwellery_budget_shop_url( $max ) ); ?>">
						<span class="jwellery-budget-caps"><?php esc_html_e( 'Under', 'jwellery-jewelry' ); ?></span>
						<span class="jwellery-budget-amount"><?php echo esc_html( jwellery_currency_symbol() . number_format_i18n( $max ) ); ?></span>
						<span class="jwellery-budget-arrow" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Product grid section (Best Sellers style â€” krishnamaalika.in).
 *
 * @param string $title Section title.
 * @param array  $args  wc_get_products args.
 * @param string       $link     View all URL.
 * @param WC_Product[] $products Optional pre-selected products.
 */
function jwellery_home_product_grid( $title, $args, $link = '', $products = null ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return;
	}

	$base = array(
		'status'       => 'publish',
		'stock_status' => 'instock',
	);
	if ( null === $products ) {
		$products = function_exists( 'jwellery_get_products_for_display' )
			? jwellery_get_products_for_display( array_merge( $base, $args ), 4, 2 )
			: wc_get_products( array_merge( array( 'limit' => 8 ), $base, $args ) );
	} elseif ( function_exists( 'jwellery_trim_products_to_full_rows' ) ) {
		$products = jwellery_trim_products_to_full_rows(
			function_exists( 'jwellery_filter_products_with_images' )
				? jwellery_filter_products_with_images( $products )
				: $products,
			4,
			2
		);
	}
	if ( empty( $products ) ) {
		return;
	}

	$link      = $link ? $link : jwellery_get_shop_url();
	$is_deals  = false !== stripos( $title, 'steal' );
	$section_class = 'jwellery-home-section--grid';
	if ( function_exists( 'jwellery_section_class' ) ) {
		$section_class .= ' ' . jwellery_section_class( $title );
	}
	if ( $is_deals ) {
		$section_class .= ' jwellery-home-section--steal-deals';
	}
	?>
	<section class="jwellery-home-section <?php echo esc_attr( $section_class ); ?>">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				$eyebrow = __( 'Trending now', 'jwellery-jewelry' );
				if ( false !== stripos( $title, 'steal' ) ) {
					$eyebrow = __( 'Hot deals', 'jwellery-jewelry' );
				} elseif ( false !== stripos( $title, 'best' ) ) {
					$eyebrow = __( 'Customer favorites', 'jwellery-jewelry' );
				}
				jwellery_section_header(
					$title,
					array(
						'center'    => true,
						'link'      => $link,
						'link_text' => __( 'View All', 'jwellery-jewelry' ),
						'eyebrow'   => $eyebrow,
					)
				);
			}
			?>
			<?php if ( $is_deals ) : ?>
				<?php $deals_total = count( $products ); ?>
				<div class="jwellery-carousel jwellery-carousel--deals" data-carousel="steal-deals" data-animate="carousel" data-deals-carousel>
					<button type="button" class="carousel-btn carousel-prev" aria-label="<?php esc_attr_e( 'Previous', 'jwellery-jewelry' ); ?>"><?php echo html_entity_decode( '&#8249;', ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<div class="jwellery-carousel-track" id="carousel-steal-deals">
						<ul class="products jwellery-product-grid jwellery-product-grid--deals">
							<?php
							foreach ( $products as $product ) {
								jwellery_render_product_card( $product );
							}
							?>
						</ul>
					</div>
					<button type="button" class="carousel-btn carousel-next" aria-label="<?php esc_attr_e( 'Next', 'jwellery-jewelry' ); ?>"><?php echo html_entity_decode( '&#8250;', ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
				<div class="carousel-dots jwellery-deals-dots" role="tablist" aria-label="<?php echo esc_attr( $title ); ?>">
					<?php for ( $d = 0; $d < $deals_total; $d++ ) : ?>
						<button type="button" class="carousel-dot<?php echo 0 === $d ? ' is-active' : ''; ?>" data-index="<?php echo (int) $d; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Slide %d', 'jwellery-jewelry' ), $d + 1 ) ); ?>"></button>
					<?php endfor; ?>
				</div>
				<p class="carousel-counter jwellery-deals-counter"><span class="carousel-current">1</span> / <?php echo (int) $deals_total; ?></p>
			<?php else : ?>
			<ul class="products jwellery-product-grid jwellery-product-grid--static jwellery-product-grid--cols-4" data-animate="carousel">
				<?php
				foreach ( $products as $product ) {
					jwellery_render_product_card( $product );
				}
				?>
			</ul>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Popular products with filter tabs (Gemstone-style).
 *
 * @param string $context `home` or `shop`.
 */
function jwellery_home_popular_tabs( $context = 'home' ) {
	if ( ! function_exists( 'wc_get_products' ) || ! function_exists( 'jwellery_render_product_card' ) ) {
		return;
	}

	$tabs = array(
		'featured' => array(
			'label' => __( 'Featured', 'jwellery-jewelry' ),
			'args'  => array( 'featured' => true ),
			'link'  => add_query_arg( 'featured', '1', jwellery_get_shop_url() ),
		),
		'new'      => array(
			'label' => __( 'New Arrivals', 'jwellery-jewelry' ),
			'args'  => array( 'orderby' => 'date', 'order' => 'DESC' ),
			'link'  => jwellery_term_link( 'latest-collection' ),
		),
		'sale'     => array(
			'label' => __( 'On Sale', 'jwellery-jewelry' ),
			'args'  => array( 'on_sale' => true ),
			'link'  => jwellery_get_shop_url(),
		),
	);

	$panels = array();
	$tab_exclude = array();
	foreach ( $tabs as $key => $tab ) {
		$query_args = array_merge(
			array(
				'status'       => 'publish',
				'stock_status' => 'instock',
				'exclude'      => array_keys( $tab_exclude ),
			),
			$tab['args']
		);
		$products = function_exists( 'jwellery_get_products_for_display' )
			? jwellery_get_products_for_display( $query_args, 4, 2 )
			: array();
		if ( empty( $products ) && 'featured' === $key ) {
			$products = function_exists( 'jwellery_get_products_for_display' )
				? jwellery_get_products_for_display(
					array(
						'status'       => 'publish',
						'stock_status' => 'instock',
						'orderby'      => 'date',
						'order'        => 'DESC',
						'exclude'      => array_keys( $tab_exclude ),
					),
					4,
					2
				)
				: array();
		}
		if ( function_exists( 'jwellery_filter_unique_display_products' ) && is_front_page() ) {
			$products = jwellery_filter_unique_display_products(
				$products,
				array(
					'exclude_shown'  => true,
					'exclude_images' => true,
					'register'       => false,
				)
			);
		}
		$fill_args = array(
			'status'       => 'publish',
			'stock_status' => 'instock',
			'orderby'      => 'date',
			'order'        => 'DESC',
			'exclude'      => array_keys( $tab_exclude ),
		);
		$panels[ $key ] = function_exists( 'jwellery_supplement_products_for_grid' )
			? jwellery_supplement_products_for_grid( $products, 4, 2, $fill_args )
			: $products;
		foreach ( $panels[ $key ] as $product ) {
			$tab_exclude[ (int) $product->get_id() ] = true;
		}
	}
	if ( function_exists( 'jwellery_mark_homepage_products_shown' ) && is_front_page() ) {
		$shown = array();
		foreach ( $panels as $panel_products ) {
			$shown = array_merge( $shown, $panel_products );
		}
		jwellery_mark_homepage_products_shown( $shown );
	}

	if ( empty( $panels['featured'] ) && empty( $panels['new'] ) && empty( $panels['sale'] ) ) {
		return;
	}
	$section_class = 'jwellery-home-section jwellery-home-section--popular-tabs';
	if ( 'shop' === $context ) {
		$section_class .= ' jwellery-shop-section--popular-tabs';
	}
	?>
	<section class="<?php echo esc_attr( $section_class ); ?>" data-popular-tabs>
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'Popular Products', 'jwellery-jewelry' ),
					array(
						'center'   => true,
						'eyebrow'  => __( 'Curated for you', 'jwellery-jewelry' ),
						'subtitle' => __( 'Filter by what matters most', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<div class="jwellery-product-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Product filters', 'jwellery-jewelry' ); ?>">
				<?php
				$i = 0;
				foreach ( $tabs as $key => $tab ) :
					if ( empty( $panels[ $key ] ) ) {
						continue;
					}
					?>
					<button type="button" class="jwellery-product-tab<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" data-tab="<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $tab['label'] ); ?>
					</button>
					<?php
					++$i;
				endforeach;
				?>
			</div>
			<?php
			$i = 0;
			foreach ( $tabs as $key => $tab ) :
				if ( empty( $panels[ $key ] ) ) {
					continue;
				}
				?>
				<div class="jwellery-product-tab-panel<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tabpanel" data-tab-panel="<?php echo esc_attr( $key ); ?>"<?php echo 0 !== $i ? ' hidden' : ''; ?>>
					<ul class="products jwellery-product-grid jwellery-product-grid--static jwellery-product-grid--cols-4" data-animate="carousel">
						<?php
						foreach ( $panels[ $key ] as $product ) {
							jwellery_render_product_card( $product );
						}
						?>
					</ul>
					<p class="jwellery-tab-viewall">
						<a class="jwellery-btn jwellery-btn-viewall" href="<?php echo esc_url( $tab['link'] ); ?>"><?php esc_html_e( 'View all', 'jwellery-jewelry' ); ?></a>
					</p>
				</div>
				<?php
				++$i;
			endforeach;
			?>
		</div>
	</section>
	<?php
}

/**
 * Shop by Category â€” image browse grid.
 */
function jwellery_home_category_stats() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$slugs = array( 'ear-rings', 'studs', 'necklaces', 'chockers', 'bangles', 'rings', 'long-harams', 'handmade-collection', 'instagram-collection', 'latest-collection', 'combo' );
	$items = array();

	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$items[] = $term;
		}
	}

	if ( count( $items ) < 4 ) {
		$fallback = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 8,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'parent'     => 0,
			)
		);
		if ( ! is_wp_error( $fallback ) && $fallback ) {
			$items = $fallback;
		}
	}

	if ( empty( $items ) ) {
		return;
	}

	$shop_url = jwellery_get_shop_url();
	?>
	<section class="jwellery-home-section jwellery-home-section--category-browse">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'Shop by Category', 'jwellery-jewelry' ),
					array(
						'center'    => true,
						'eyebrow'   => __( 'Browse collections', 'jwellery-jewelry' ),
						'subtitle'  => __( 'Earrings, necklaces, bangles & more — tap a style to explore', 'jwellery-jewelry' ),
						'link'      => $shop_url,
						'link_text' => __( 'View all products', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<div class="jwellery-category-browse" data-animate="carousel">
				<?php foreach ( $items as $term ) : ?>
					<?php
					$count = (int) $term->count;
					/* translators: %d: number of products */
					$count_label = sprintf( _n( '%d product', '%d products', $count, 'jwellery-jewelry' ), $count );
					?>
					<a class="jwellery-category-browse-card" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
						<span class="jwellery-category-browse-media">
							<?php
							if ( function_exists( 'jwellery_category_browse_image_html' ) ) {
								echo jwellery_category_browse_image_html( $term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
							<span class="jwellery-category-browse-overlay" aria-hidden="true"></span>
							<span class="jwellery-category-browse-badge"><?php echo esc_html( $count_label ); ?></span>
						</span>
						<span class="jwellery-category-browse-body">
							<span class="jwellery-category-browse-name"><?php echo esc_html( $term->name ); ?></span>
							<span class="jwellery-category-browse-cta"><?php esc_html_e( 'Shop now', 'jwellery-jewelry' ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Steal deal offers (on-sale products).
 */
function jwellery_home_steal_deals() {
	if ( ! function_exists( 'jwellery_get_products_for_display' ) ) {
		return;
	}

	$base = array(
		'status'       => 'publish',
		'stock_status' => 'instock',
	);

	$products = jwellery_get_products_for_display( array_merge( $base, array( 'on_sale' => true ) ), 4, 2 );
	if ( count( $products ) < 4 ) {
		$products = jwellery_get_products_for_display(
			array_merge(
				$base,
				array(
					'orderby' => 'price',
					'order'   => 'ASC',
				)
			),
			4,
			2
		);
	}
	if ( empty( $products ) ) {
		return;
	}

	jwellery_home_product_grid(
		__( 'Steal Deal Offers', 'jwellery-jewelry' ),
		array(),
		jwellery_get_shop_url(),
		$products
	);
}

/**
 * Owner photo from Media Library (e.g. kalpana-pic.jpg uploaded in wp-admin).
 *
 * @return string
 */
function jwellery_owner_media_library_url() {
	$filenames = array( 'kalpana-pic.jpg', 'kalpana-pic.jpeg', 'kalpana-pic.png', 'owner.jpg', 'owner.png' );

	global $wpdb;
	foreach ( $filenames as $file ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
				'%' . $wpdb->esc_like( $file )
			)
		);
		if ( $attachment_id ) {
			$url = wp_get_attachment_image_url( (int) $attachment_id, 'large' );
			if ( $url ) {
				return $url;
			}
		}
	}

	return '';
}

/**
 * Owner photo URL (Customizer upload, Media Library, or bundled default).
 *
 * @return string
 */
function jwellery_owner_image_url() {
	$custom = get_theme_mod( 'jwellery_owner_image', '' );
	if ( $custom ) {
		if ( is_numeric( $custom ) ) {
			$url = wp_get_attachment_image_url( (int) $custom, 'large' );
			if ( $url ) {
				return $url;
			}
		} elseif ( is_string( $custom ) && filter_var( $custom, FILTER_VALIDATE_URL ) ) {
			return esc_url( $custom );
		}
	}

	$library = jwellery_owner_media_library_url();
	if ( $library ) {
		return $library;
	}

	$jpg = JWELLERY_THEME_DIR . '/assets/images/owner.jpg';
	if ( file_exists( $jpg ) ) {
		return JWELLERY_THEME_URI . '/assets/images/owner.jpg';
	}

	$path = JWELLERY_THEME_DIR . '/assets/images/owner.png';
	if ( file_exists( $path ) ) {
		return JWELLERY_THEME_URI . '/assets/images/owner.png';
	}

	return '';
}

/**
 * Meet the owner â€” photo + intro on homepage.
 */
function jwellery_home_owner_section() {
	if ( ! get_theme_mod( 'jwellery_owner_enable', true ) ) {
		return;
	}

	$image_url = jwellery_owner_image_url();
	if ( ! $image_url ) {
		return;
	}

	$name = trim( (string) get_theme_mod( 'jwellery_owner_name', __( 'Kalpana', 'jwellery-jewelry' ) ) );
	$role = trim( (string) get_theme_mod( 'jwellery_owner_role', __( 'Founder', 'jwellery-jewelry' ) ) );
	$bio  = trim( (string) get_theme_mod( 'jwellery_owner_bio', '' ) );
	if ( ! $bio ) {
		$bio = __( 'Passionate about traditional Indian jewelry â€” every piece is chosen with love so you can shine at weddings, festivals, and everyday moments.', 'jwellery-jewelry' );
	}

	$brand = function_exists( 'jwellery_brand_name' ) ? jwellery_brand_name() : get_bloginfo( 'name' );
	$about = function_exists( 'jwellery_get_store_page_url' ) ? jwellery_get_store_page_url( 'about' ) : home_url( '/about/' );
	?>
	<section class="jwellery-home-section jwellery-owner jwellery-home-section--meet-the-owner">
		<div class="container">
			<div class="jwellery-owner-split is-visible">
				<div class="jwellery-owner-photo">
					<img
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( $name ? sprintf( __( '%1$s â€” %2$s', 'jwellery-jewelry' ), $name, $brand ) : sprintf( __( 'Owner of %s', 'jwellery-jewelry' ), $brand ) ); ?>"
						width="480"
						height="640"
						loading="eager"
						decoding="async"
					/>
				</div>
				<div class="jwellery-owner-content">
					<?php
					if ( function_exists( 'jwellery_section_header' ) ) {
						jwellery_section_header(
							__( 'Meet the Owner', 'jwellery-jewelry' ),
							array(
								'eyebrow'  => $brand,
								'subtitle' => $role ? $role : __( 'Handcrafted jewelry with heart', 'jwellery-jewelry' ),
							)
						);
					}
					?>
					<?php if ( $name ) : ?>
						<p class="jwellery-owner-name"><?php echo esc_html( $name ); ?></p>
					<?php endif; ?>
					<p class="jwellery-owner-bio"><?php echo esc_html( $bio ); ?></p>
					<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( $about ); ?>"><?php esc_html_e( 'Our Story', 'jwellery-jewelry' ); ?></a>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Follow our journey â€” product / Instagram gallery.
 */
function jwellery_home_follow_journey() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return;
	}

	$products = function_exists( 'jwellery_get_products_for_display' )
		? jwellery_get_products_for_display(
			array(
				'status'       => 'publish',
				'stock_status' => 'instock',
				'category'     => array( 'instagram-collection' ),
			),
			4,
			2
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
				4,
				2
			)
			: array();
	}
	if ( empty( $products ) ) {
		return;
	}

	if ( count( $products ) < 4 ) {
		return;
	}

	$ig = get_theme_mod( 'jwellery_instagram', '' );
	?>
	<section class="jwellery-home-section jwellery-follow jwellery-home-section--follow-our-journey">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'Follow Our Journey', 'jwellery-jewelry' ),
					array(
						'center'   => true,
						'eyebrow'  => __( 'On social', 'jwellery-jewelry' ),
						'subtitle' => __( 'See how our customers style their jewels', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<div class="jwellery-follow-grid jwellery-follow-grid--cols-4" data-animate="carousel">
				<?php foreach ( $products as $product ) : ?>
					<?php if ( function_exists( 'jwellery_product_has_image' ) && ! jwellery_product_has_image( $product ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<a class="jwellery-follow-item" href="<?php echo esc_url( $product->get_permalink() ); ?>">
						<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore ?>
					</a>
				<?php endforeach; ?>
			</div>
			<?php if ( $ig ) : ?>
				<p class="jwellery-follow-cta">
					<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Follow on Instagram', 'jwellery-jewelry' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Single review card markup.
 *
 * @param array $r Review data.
 */
function jwellery_render_review_card( $r ) {
	?>
	<blockquote class="jwellery-review-card">
		<div class="jwellery-review-stars" aria-label="<?php esc_attr_e( '5 out of 5 stars', 'jwellery-jewelry' ); ?>">
			<?php for ( $i = 0; $i < 5; $i++ ) : ?>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>
			<?php endfor; ?>
		</div>
		<span class="jwellery-review-quote" aria-hidden="true">&ldquo;</span>
		<p class="jwellery-review-text"><?php echo esc_html( $r['text'] ); ?></p>
		<footer class="jwellery-review-footer">
			<span class="jwellery-review-avatar" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $r['name'], 0, 1 ) ) : strtoupper( substr( $r['name'], 0, 1 ) ) ); ?></span>
			<span class="jwellery-review-meta">
				<strong><?php echo esc_html( $r['name'] ); ?></strong>
				<cite><?php echo esc_html( $r['city'] ); ?></cite>
			</span>
		</footer>
	</blockquote>
	<?php
}

/**
 * FAQ accordion.
 */
function jwellery_home_faq() {
	$faqs = array(
		array(
			'q' => __( 'Is this real gold jewelry?', 'jwellery-jewelry' ),
			'a' => __( 'No, we offer premium imitation and fashion jewelry with gold-tone finishes.', 'jwellery-jewelry' ),
		),
		array(
			'q' => __( 'Is it suitable for weddings?', 'jwellery-jewelry' ),
			'a' => __( 'Yes â€” perfect for bridal, festive, and daily traditional wear.', 'jwellery-jewelry' ),
		),
		array(
			'q' => __( 'How do I pay?', 'jwellery-jewelry' ),
			'a' => __( 'We accept UPI only. Place your order first, then pay via UPI using the QR code and order number on the confirmation page.', 'jwellery-jewelry' ),
		),
		array(
			'q' => __( 'Do you offer free shipping?', 'jwellery-jewelry' ),
			'a' => __( 'We offer free shipping on all orders across India.', 'jwellery-jewelry' ),
		),
		array(
			'q' => __( 'Will the color fade?', 'jwellery-jewelry' ),
			'a' => __( 'With proper care â€” avoid water and perfume directly on pieces â€” color stays long-lasting.', 'jwellery-jewelry' ),
		),
	);
	$contact_url = function_exists( 'jwellery_get_store_page_url' ) ? jwellery_get_store_page_url( 'contact' ) : home_url( '/contact/' );
	$wa          = function_exists( 'jwellery_whatsapp_url' ) ? jwellery_whatsapp_url() : '';
	?>
	<section class="jwellery-home-section jwellery-faq jwellery-home-section--frequently-asked-questions">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'Frequently Asked Questions', 'jwellery-jewelry' ),
					array(
						'center'   => true,
						'eyebrow'  => __( 'Help center', 'jwellery-jewelry' ),
						'subtitle' => __( 'Quick answers about our jewelry, payments, and delivery.', 'jwellery-jewelry' ),
					)
				);
			}
			?>
			<div class="jwellery-faq-layout">
				<div class="jwellery-faq-list" role="list">
					<?php foreach ( $faqs as $i => $faq ) : ?>
						<details class="jwellery-faq-item" role="listitem"<?php echo 0 === $i ? ' open' : ''; ?>>
							<summary>
								<span class="jwellery-faq-num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
								<span class="jwellery-faq-question"><?php echo esc_html( $faq['q'] ); ?></span>
								<span class="jwellery-faq-icon" aria-hidden="true"></span>
							</summary>
							<div class="jwellery-faq-answer">
								<p><?php echo esc_html( $faq['a'] ); ?></p>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
				<aside class="jwellery-faq-cta">
					<div class="jwellery-faq-cta-inner">
						<h3><?php esc_html_e( 'Still Have Questions?', 'jwellery-jewelry' ); ?></h3>
						<p><?php esc_html_e( 'Our team is happy to help with orders, sizing, and styling advice.', 'jwellery-jewelry' ); ?></p>
						<div class="jwellery-faq-cta-actions">
							<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact Us', 'jwellery-jewelry' ); ?></a>
							<?php if ( $wa ) : ?>
								<a class="jwellery-btn jwellery-btn-outline" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp Us', 'jwellery-jewelry' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</aside>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Enhanced testimonials with cities (krishnamaalika style).
 */
function jwellery_home_testimonials_km() {
	$reviews = array(
		array(
			'text'   => __( 'Bangles are very good and strong â€” looks like real gold. Thank you!', 'jwellery-jewelry' ),
			'name'   => 'Sridevi',
			'city'   => 'Eluru',
		),
		array(
			'text'   => __( 'The jewellery looks really elegant. Perfect for festive wear and the quality is good.', 'jwellery-jewelry' ),
			'name'   => 'Ritika Patel',
			'city'   => 'Ahmedabad',
		),
		array(
			'text'   => __( 'Ordered earrings â€” they look exactly like the photos. Very stylish.', 'jwellery-jewelry' ),
			'name'   => 'Sneha Kulkarni',
			'city'   => 'Pune',
		),
		array(
			'text'   => __( 'Loved the necklace set. Perfect for a family function.', 'jwellery-jewelry' ),
			'name'   => 'Megha Reddy',
			'city'   => 'Hyderabad',
		),
	);
	?>
	<section class="jwellery-home-section jwellery-testimonials jwellery-testimonials--km jwellery-home-section--what-our-customers-say">
		<div class="container">
			<?php
			if ( function_exists( 'jwellery_section_header' ) ) {
				jwellery_section_header(
					__( 'What Our Customers Say', 'jwellery-jewelry' ),
					array(
						'center'   => true,
						'eyebrow'  => __( 'Reviews', 'jwellery-jewelry' ),
						'subtitle' => __( 'Loved by shoppers across India', 'jwellery-jewelry' ),
					)
				);
			}
			?>
		</div>
		<div class="jwellery-testimonials-marquee" data-testimonials-marquee>
			<div class="jwellery-testimonials-marquee-track">
				<?php
				foreach ( array_merge( $reviews, $reviews ) as $r ) {
					jwellery_render_review_card( $r );
				}
				?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Footer about blurb from Customizer.
 *
 * @return string
 */
function jwellery_footer_about_text() {
	$custom = (string) get_theme_mod( 'jwellery_footer_about', '' );
	if ( $custom ) {
		return $custom;
	}
	return __( 'Elegant artificial and fashion jewelry inspired by traditional Indian designs — timeless beauty, style, and affordability for every occasion.', 'jwellery-jewelry' );
}


