<?php
/**
 * Customer wishlist — My Account endpoint + header + product hearts.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * User meta key for saved product IDs.
 */
function jwellery_wishlist_meta_key() {
	return 'jwellery_wishlist';
}

/**
 * Wishlist product IDs for current user.
 *
 * @return int[]
 */
function jwellery_get_wishlist_ids() {
	if ( ! is_user_logged_in() ) {
		return array();
	}
	$raw = get_user_meta( get_current_user_id(), jwellery_wishlist_meta_key(), true );
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$ids = array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );
	return array_values( array_filter( $ids, 'jwellery_wishlist_product_is_valid' ) );
}

/**
 * @param int $product_id Product ID.
 * @return bool
 */
function jwellery_wishlist_product_is_valid( $product_id ) {
	if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
		return false;
	}
	$product = wc_get_product( $product_id );
	if ( ! $product || 'publish' !== $product->get_status() ) {
		return false;
	}
	if ( function_exists( 'jwellery_product_has_image' ) && ! jwellery_product_has_image( $product ) ) {
		return false;
	}
	return true;
}

/**
 * @return int
 */
function jwellery_wishlist_count() {
	return count( jwellery_get_wishlist_ids() );
}

/**
 * @param int $product_id Product ID.
 * @return bool
 */
function jwellery_is_in_wishlist( $product_id ) {
	return in_array( (int) $product_id, jwellery_get_wishlist_ids(), true );
}

/**
 * Wishlist URL (My Account endpoint).
 *
 * @return string
 */
function jwellery_wishlist_url() {
	if ( ! function_exists( 'wc_get_account_endpoint_url' ) ) {
		return home_url( '/my-account/' );
	}
	return wc_get_account_endpoint_url( 'wishlist' );
}

/**
 * Register WooCommerce account endpoint.
 */
function jwellery_register_wishlist_endpoint() {
	add_rewrite_endpoint( 'wishlist', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'jwellery_register_wishlist_endpoint' );

/**
 * Flush permalinks once after endpoint is added.
 */
function jwellery_maybe_flush_wishlist_rewrites() {
	if ( get_option( 'jwellery_wishlist_rewrites_flushed' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'jwellery_wishlist_rewrites_flushed', 1 );
}
add_action( 'admin_init', 'jwellery_maybe_flush_wishlist_rewrites' );

/**
 * @param array $items Menu items.
 * @return array
 */
function jwellery_wishlist_account_menu_item( $items ) {
	$new = array();
	foreach ( $items as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'dashboard' === $key ) {
			$new['wishlist'] = __( 'Wishlist', 'jwellery-jewelry' );
		}
	}
	if ( ! isset( $new['wishlist'] ) ) {
		$new['wishlist'] = __( 'Wishlist', 'jwellery-jewelry' );
	}
	return $new;
}
/**
 * Register WooCommerce wishlist hooks when WooCommerce is ready.
 */
function jwellery_wishlist_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_filter( 'woocommerce_account_menu_items', 'jwellery_wishlist_account_menu_item' );
	add_action( 'woocommerce_account_wishlist_endpoint', 'jwellery_wishlist_account_endpoint_content' );
	add_action( 'woocommerce_after_add_to_cart_button', 'jwellery_single_product_wishlist_button', 12 );
}
add_action( 'woocommerce_init', 'jwellery_wishlist_bootstrap' );

/**
 * Account wishlist row card (dashboard layout — not shop grid).
 *
 * @param WC_Product $product Product.
 */
function jwellery_wishlist_account_card( $product ) {
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$product_id   = $product->get_id();
	$out_of_stock = ! $product->is_in_stock();
	?>
	<article class="jwellery-wishlist-row<?php echo $out_of_stock ? ' is-sold-out' : ''; ?>">
		<a class="jwellery-wishlist-row__image" href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<div class="jwellery-wishlist-row__body">
			<h3 class="jwellery-wishlist-row__title">
				<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
			</h3>
			<div class="jwellery-wishlist-row__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			<?php if ( $out_of_stock ) : ?>
				<span class="jwellery-wishlist-row__badge"><?php esc_html_e( 'Sold out', 'jwellery-jewelry' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="jwellery-wishlist-row__actions">
			<?php if ( ! $out_of_stock ) : ?>
				<a
					href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
					class="button add_to_cart_button ajax_add_to_cart product_type_<?php echo esc_attr( $product->get_type() ); ?> jwellery-btn jwellery-btn-primary"
					data-product_id="<?php echo esc_attr( (string) $product_id ); ?>"
				>
					<?php echo esc_html( $product->add_to_cart_text() ); ?>
				</a>
			<?php endif; ?>
			<?php if ( function_exists( 'jwellery_wishlist_button' ) ) : ?>
				<?php jwellery_wishlist_button( $product, 'loop' ); ?>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Wishlist tab content.
 */
function jwellery_wishlist_account_endpoint_content() {
	$ids = jwellery_get_wishlist_ids();
	?>
	<div class="jwellery-wishlist-page jwellery-wishlist-page--account">
		<?php if ( empty( $ids ) ) : ?>
			<div class="jwellery-uda-empty">
				<p><?php esc_html_e( 'Your wishlist is empty. Browse the shop and tap the heart on pieces you love.', 'jwellery-jewelry' ); ?></p>
				<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( function_exists( 'jwellery_all_products_url' ) ? jwellery_all_products_url() : jwellery_get_shop_url() ); ?>"><?php esc_html_e( 'Shop now', 'jwellery-jewelry' ); ?></a>
			</div>
		<?php else : ?>
			<p class="jwellery-wishlist-count">
				<?php
				printf(
					/* translators: %d: number of products */
					esc_html( _n( '%d saved item', '%d saved items', count( $ids ), 'jwellery-jewelry' ) ),
					(int) count( $ids )
				);
				?>
			</p>
			<div class="jwellery-wishlist-account-list">
				<?php
				foreach ( $ids as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						jwellery_wishlist_account_card( $product );
					}
				}
				?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Persist wishlist for user.
 *
 * @param int[] $ids Product IDs.
 */
function jwellery_save_wishlist_ids( $ids ) {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	update_user_meta( get_current_user_id(), jwellery_wishlist_meta_key(), $ids );
}

/**
 * Toggle product in wishlist.
 *
 * @param int $product_id Product ID.
 * @return array{added: bool, count: int}
 */
function jwellery_toggle_wishlist_product( $product_id ) {
	$product_id = absint( $product_id );
	if ( ! jwellery_wishlist_product_is_valid( $product_id ) ) {
		return array(
			'added'  => false,
			'count'  => jwellery_wishlist_count(),
			'error'  => __( 'Product not found.', 'jwellery-jewelry' ),
		);
	}

	$ids = jwellery_get_wishlist_ids();
	$added = false;
	if ( in_array( $product_id, $ids, true ) ) {
		$ids = array_values( array_diff( $ids, array( $product_id ) ) );
	} else {
		$ids[] = $product_id;
		$added = true;
	}
	jwellery_save_wishlist_ids( $ids );

	return array(
		'added' => $added,
		'count' => count( $ids ),
	);
}

/**
 * Header wishlist icon HTML.
 *
 * @return string
 */
function jwellery_wishlist_header_icon_html() {
	if ( ! function_exists( 'jwellery_icon_svg' ) || ! class_exists( 'WooCommerce' ) ) {
		return '';
	}

	$logged_in = is_user_logged_in();
	$count     = $logged_in ? jwellery_wishlist_count() : 0;
	$url       = $logged_in ? jwellery_wishlist_url() : wc_get_page_permalink( 'myaccount' );
	$label     = $logged_in
		? sprintf(
			/* translators: %d: wishlist count */
			_n( 'Wishlist (%d item)', 'Wishlist (%d items)', $count, 'jwellery-jewelry' ),
			$count
		)
		: __( 'Log in to use wishlist', 'jwellery-jewelry' );

	$badge = $count > 0
		? '<span class="jwellery-wishlist-count-badge">' . (int) $count . '</span>'
		: '<span class="jwellery-wishlist-count-badge" hidden>0</span>';

	return sprintf(
		'<a class="jwellery-header-icon jwellery-wishlist-header-link%s" href="%s" aria-label="%s" title="%s">%s%s</a>',
		$logged_in ? ' is-logged-in' : ' is-guest',
		esc_url( $url ),
		esc_attr( $label ),
		esc_attr( $label ),
		jwellery_icon_svg( 'heart', 22 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$badge
	);
}

/**
 * Output header wishlist link.
 */
function jwellery_header_wishlist_icon() {
	echo jwellery_wishlist_header_icon_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Product wishlist button.
 *
 * @param WC_Product|int $product Product or ID.
 * @param string         $context loop|single.
 */
function jwellery_wishlist_button( $product, $context = 'loop' ) {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'jwellery_icon_svg' ) ) {
		return;
	}

	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$product_id = $product->get_id();
	$active     = is_user_logged_in() && jwellery_is_in_wishlist( $product_id );
	$label      = $active
		? __( 'Remove from wishlist', 'jwellery-jewelry' )
		: __( 'Add to wishlist', 'jwellery-jewelry' );

	printf(
		'<button type="button" class="jwellery-wishlist-btn jwellery-wishlist-btn--%1$s%2$s" data-product-id="%3$d" data-context="%1$s" aria-label="%4$s" aria-pressed="%5$s" title="%4$s">%6$s</button>',
		esc_attr( $context ),
		$active ? ' is-active' : '',
		(int) $product_id,
		esc_attr( $label ),
		$active ? 'true' : 'false',
		jwellery_icon_svg( 'heart', 20 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * AJAX toggle handler.
 */
function jwellery_ajax_toggle_wishlist() {
	check_ajax_referer( 'jwellery_shop', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error(
			array(
				'message'  => __( 'Please log in to save items to your wishlist.', 'jwellery-jewelry' ),
				'loginUrl' => wc_get_page_permalink( 'myaccount' ),
			),
			401
		);
	}

	$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$result     = jwellery_toggle_wishlist_product( $product_id );

	if ( ! empty( $result['error'] ) ) {
		wp_send_json_error( array( 'message' => $result['error'] ) );
	}

	wp_send_json_success(
		array(
			'added'     => (bool) $result['added'],
			'count'     => (int) $result['count'],
			'message'   => $result['added']
				? __( 'Added to wishlist', 'jwellery-jewelry' )
				: __( 'Removed from wishlist', 'jwellery-jewelry' ),
			'fragments' => array(
				'a.jwellery-wishlist-header-link' => jwellery_wishlist_header_icon_html(),
			),
		)
	);
}
add_action( 'wp_ajax_jwellery_toggle_wishlist', 'jwellery_ajax_toggle_wishlist' );
add_action( 'wp_ajax_nopriv_jwellery_toggle_wishlist', 'jwellery_ajax_toggle_wishlist' );

/**
 * Script data for wishlist.
 *
 * @param array $data Data.
 * @return array
 */
function jwellery_wishlist_script_data( $data ) {
	if ( ! is_array( $data ) ) {
		$data = array();
	}
	$data['isLoggedIn']        = is_user_logged_in();
	$data['wishlistLoginMsg']  = __( 'Please log in to save items to your wishlist.', 'jwellery-jewelry' );
	$data['wishlistAddedMsg']  = __( 'Added to wishlist', 'jwellery-jewelry' );
	$data['wishlistRemovedMsg'] = __( 'Removed from wishlist', 'jwellery-jewelry' );
	$data['loginUrl']          = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	return $data;
}
add_filter( 'jwellery_shop_experience_script_data', 'jwellery_wishlist_script_data' );

/**
 * Single product wishlist button beside add to cart.
 */
function jwellery_single_product_wishlist_button() {
	global $product;
	if ( $product && function_exists( 'jwellery_wishlist_button' ) ) {
		jwellery_wishlist_button( $product, 'single' );
	}
}

