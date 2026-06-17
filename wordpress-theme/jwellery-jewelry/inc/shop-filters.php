<?php
/**
 * Shop category quick filters.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Category pill filters above shop products.
 */
function jwellery_shop_category_filters() {
	if ( ! function_exists( 'is_shop' ) ) {
		return;
	}
	if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
		return;
	}
	if ( function_exists( 'jwellery_is_main_shop_catalog' ) && jwellery_is_main_shop_catalog() ) {
		return;
	}
	if ( ! function_exists( 'jwellery_get_shop_categories' ) ) {
		return;
	}

	$terms   = jwellery_get_shop_categories();
	$shop    = jwellery_get_shop_url();
	$current = is_product_category() ? get_queried_object() : null;
	?>
	<div class="jwellery-shop-filters" data-animate="head">
		<p class="jwellery-shop-filters-label"><?php esc_html_e( 'Shop by category', 'jwellery-jewelry' ); ?></p>
		<div class="jwellery-shop-filters-pills">
			<a class="jwellery-shop-filter-pill<?php echo is_shop() && ! is_product_category() ? ' is-active' : ''; ?>" href="<?php echo esc_url( $shop ); ?>">
				<?php esc_html_e( 'All', 'jwellery-jewelry' ); ?>
			</a>
			<?php foreach ( $terms as $term ) : ?>
				<?php
				if ( ! $term instanceof WP_Term ) {
					continue;
				}
				$active = $current && (int) $current->term_id === (int) $term->term_id;
				?>
				<a class="jwellery-shop-filter-pill<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<?php echo esc_html( $term->name ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'jwellery_shop_category_filters', 15 );
