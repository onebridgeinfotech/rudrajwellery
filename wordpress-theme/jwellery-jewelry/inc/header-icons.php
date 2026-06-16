<?php
/**
 * Header icon markup (search, account, cart).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thin-line search icon button.
 */
function jwellery_header_search_toggle() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'jwellery_icon_svg' ) ) {
		return;
	}
	?>
	<button type="button" class="jwellery-header-icon jwellery-search-toggle" aria-expanded="false" aria-controls="jwellery-search-panel" aria-label="<?php esc_attr_e( 'Search products', 'jwellery-jewelry' ); ?>">
		<?php echo jwellery_icon_svg( 'search', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
	<?php
}

/**
 * Account icon link.
 */
function jwellery_header_account_icon() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'jwellery_icon_svg' ) ) {
		return;
	}
	$label = function_exists( 'jwellery_account_label' ) ? jwellery_account_label() : __( 'My account', 'jwellery-jewelry' );
	$user  = is_user_logged_in() ? wp_get_current_user() : null;
	$name  = $user ? trim( (string) $user->display_name ) : '';
	?>
	<a class="jwellery-header-icon jwellery-account-icon<?php echo is_user_logged_in() ? ' is-logged-in' : ' is-guest'; ?>" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" title="<?php echo esc_attr( $label ); ?>">
		<?php echo jwellery_icon_svg( 'user', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $name ) : ?>
			<span class="jwellery-header-user-name"><?php echo esc_html( $name ); ?></span>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * Search panel (dropdown bar below header).
 */
function jwellery_header_search_panel() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	?>
	<div id="jwellery-search-panel" class="jwellery-search-panel" hidden>
		<div class="container">
			<form role="search" method="get" class="jwellery-search-form jwellery-search-form--panel" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="jwellery-search-panel-input"><?php esc_html_e( 'Search', 'jwellery-jewelry' ); ?></label>
				<input type="search" id="jwellery-search-panel-input" name="s" placeholder="<?php esc_attr_e( 'Search products…', 'jwellery-jewelry' ); ?>" />
				<input type="hidden" name="post_type" value="product" />
				<button type="submit" class="jwellery-search-submit" aria-label="<?php esc_attr_e( 'Search', 'jwellery-jewelry' ); ?>">
					<?php
					if ( function_exists( 'jwellery_icon_svg' ) ) {
						echo jwellery_icon_svg( 'search', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</button>
			</form>
			<p class="jwellery-search-hints">
				<?php esc_html_e( 'Popular:', 'jwellery-jewelry' ); ?>
				<a href="<?php echo esc_url( add_query_arg( array( 's' => 'earrings', 'post_type' => 'product' ), home_url( '/' ) ) ); ?>"><?php esc_html_e( 'earrings', 'jwellery-jewelry' ); ?></a>,
				<a href="<?php echo esc_url( add_query_arg( array( 's' => 'black beads', 'post_type' => 'product' ), home_url( '/' ) ) ); ?>"><?php esc_html_e( 'black beads', 'jwellery-jewelry' ); ?></a>
			</p>
		</div>
	</div>
	<?php
}
