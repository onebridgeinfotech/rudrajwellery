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
			if ( isset( $seen[ $pid ] ) ) {
				continue;
			}
			$seen[ $pid ] = true;
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

	return jwellery_trim_products_to_full_rows( $picked, $cols, $rows );
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
	if ( function_exists( 'is_product' ) && is_product() && (int) get_queried_object_id() === (int) $id ) {
		return $visible;
	}
	return jwellery_product_has_image( $product );
}
add_filter( 'woocommerce_product_is_visible', 'jwellery_hide_products_without_images', 10, 3 );

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
		$lines = array_filter( array_map( 'trim', explode( '|', $raw ) ) );
		if ( $lines ) {
			return $lines;
		}
	}
	return array(
		__( 'FREE Shipping on Orders Above ₹999', 'jwellery-jewelry' ),
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
	$admin = get_option( 'admin_email' );
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
 * Hero slide image URLs (Customizer).
 *
 * @return array<int, string>
 */
function jwellery_hero_slides() {
	$slides = array();
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
	if ( empty( $slides ) ) {
		for ( $i = 1; $i <= 5; $i++ ) {
			$path = JWELLERY_THEME_DIR . '/assets/images/hero-slide-' . $i . '.jpg';
			if ( file_exists( $path ) ) {
				$slides[] = JWELLERY_THEME_URI . '/assets/images/hero-slide-' . $i . '.jpg';
			}
		}
	}
	if ( empty( $slides ) ) {
		$slides[] = JWELLERY_THEME_URI . '/assets/images/hero-default.jpg';
	}
	return $slides;
}

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
add_action( 'woocommerce_single_product_summary', 'jwellery_product_trust_badges', 25 );

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
add_action( 'woocommerce_single_product_summary', 'jwellery_product_whatsapp_share', 35 );

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
				<p class="jwellery-newsletter-hint"><?php esc_html_e( 'Add your WhatsApp number in Appearance → Customize → Store UI', 'jwellery-jewelry' ); ?></p>
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
				<blockquote><p><?php esc_html_e( 'Beautiful designs and great quality for the price!', 'jwellery-jewelry' ); ?></p><cite>— <?php esc_html_e( 'Happy customer', 'jwellery-jewelry' ); ?></cite></blockquote>
				<blockquote><p><?php esc_html_e( 'Easy UPI checkout and fast delivery.', 'jwellery-jewelry' ); ?></p><cite>— <?php esc_html_e( 'Repeat buyer', 'jwellery-jewelry' ); ?></cite></blockquote>
				<blockquote><p><?php esc_html_e( 'Perfect for festive wear — looks like real gold!', 'jwellery-jewelry' ); ?></p><cite>— <?php esc_html_e( 'Verified buyer', 'jwellery-jewelry' ); ?></cite></blockquote>
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
