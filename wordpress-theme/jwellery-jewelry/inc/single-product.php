<?php
/**
 * Single product page — layout hooks, placeholder cleanup, styles.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo catalog placeholder strings we should not show on the storefront.
 *
 * @return string[]
 */
function jwellery_product_placeholder_phrases() {
	return array(
		'imitation jewelry — update name and price in admin',
		'imitation jewellery — update name and price in admin',
	);
}

/**
 * Whether text is a demo placeholder.
 *
 * @param string $text Text.
 * @return bool
 */
function jwellery_is_product_placeholder_text( $text ) {
	$text = strtolower( trim( wp_strip_all_tags( (string) $text ) ) );
	if ( '' === $text ) {
		return false;
	}
	foreach ( jwellery_product_placeholder_phrases() as $phrase ) {
		if ( false !== strpos( $text, $phrase ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Register single-product hooks after WooCommerce loads.
 */
function jwellery_register_single_product_hooks() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

	add_action( 'woocommerce_single_product_summary', 'jwellery_single_product_category', 4 );
	add_action( 'woocommerce_single_product_summary', 'jwellery_product_trust_badges', 22 );
	add_action( 'woocommerce_single_product_summary', 'jwellery_single_product_purchase_open', 29 );
	add_action( 'woocommerce_single_product_summary', 'jwellery_single_product_purchase_close', 31 );
	add_action( 'woocommerce_single_product_summary', 'jwellery_single_product_details', 35 );
	add_action( 'woocommerce_single_product_summary', 'jwellery_product_whatsapp_share', 38 );
	add_action( 'woocommerce_single_product_summary', 'jwellery_single_product_meta', 42 );

	add_filter( 'woocommerce_short_description', 'jwellery_filter_product_short_description', 20 );
	add_filter( 'woocommerce_product_tabs', 'jwellery_filter_product_tabs', 20 );
}
add_action( 'woocommerce_init', 'jwellery_register_single_product_hooks' );

/**
 * Enqueue single-product styles.
 */
function jwellery_enqueue_single_product_styles() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	wp_enqueue_style(
		'jwellery-single-product',
		JWELLERY_THEME_URI . '/assets/css/single-product.css',
		array( 'jwellery-shop-experience' ),
		jwellery_asset_version()
	);
}
add_action( 'wp_enqueue_scripts', 'jwellery_enqueue_single_product_styles', 30 );

/**
 * Product category link above title.
 */
function jwellery_single_product_category() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return;
	}
	$term = $terms[0];
	?>
	<p class="jwellery-product-category">
		<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
	</p>
	<?php
}

/**
 * Open purchase card wrapper (before add to cart).
 */
function jwellery_single_product_purchase_open() {
	echo '<div class="jwellery-product-purchase">';
}

/**
 * Close purchase card wrapper (after add to cart).
 */
function jwellery_single_product_purchase_close() {
	echo '</div>';
}

/**
 * SKU + categories below purchase area.
 */
function jwellery_single_product_meta() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$sku = $product->get_sku();
	if ( ! $sku ) {
		return;
	}
	?>
	<p class="jwellery-product-meta-line">
		<span class="jwellery-product-meta-label"><?php esc_html_e( 'SKU', 'jwellery-jewelry' ); ?></span>
		<span><?php echo esc_html( $sku ); ?></span>
	</p>
	<?php
}

/**
 * Hide demo placeholder short descriptions.
 *
 * @param string $description Short description HTML.
 * @return string
 */
function jwellery_filter_product_short_description( $description ) {
	if ( jwellery_is_product_placeholder_text( $description ) ) {
		return '';
	}
	return $description;
}

/**
 * Remove empty or placeholder description/reviews tabs.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function jwellery_filter_product_tabs( $tabs ) {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return $tabs;
	}

	if ( isset( $tabs['description'] ) ) {
		$desc = $product->get_description();
		if ( '' === trim( wp_strip_all_tags( (string) $desc ) ) || jwellery_is_product_placeholder_text( $desc ) ) {
			unset( $tabs['description'] );
		}
	}

	if ( isset( $tabs['reviews'] ) && ! $product->get_review_count() ) {
		unset( $tabs['reviews'] );
	}

	return $tabs;
}
