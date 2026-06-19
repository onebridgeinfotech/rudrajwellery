<?php
/**
 * User dashboard — complete account area (guest + logged-in).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nav icon per endpoint.
 *
 * @param string $endpoint Endpoint.
 * @return string
 */
function jwellery_uda_nav_icon( $endpoint ) {
	$icons = array(
		'dashboard'       => 'home',
		'orders'          => 'orders',
		'wishlist'        => 'heart',
		'edit-address'    => 'location',
		'edit-account'    => 'settings',
		'customer-logout' => 'logout',
	);
	return isset( $icons[ $endpoint ] ) ? $icons[ $endpoint ] : 'user';
}

/**
 * Nav item classes.
 *
 * @param string $endpoint Endpoint.
 * @return string
 */
function jwellery_uda_nav_classes( $endpoint ) {
	if ( function_exists( 'wc_get_account_menu_item_classes' ) ) {
		return wc_get_account_menu_item_classes( $endpoint );
	}
	$classes = array(
		'woocommerce-MyAccount-navigation-link',
		'woocommerce-MyAccount-navigation-link--' . sanitize_html_class( $endpoint ),
	);
	if ( function_exists( 'wc_is_current_account_menu_item' ) && wc_is_current_account_menu_item( $endpoint ) ) {
		$classes[] = 'is-active';
	}
	return implode( ' ', $classes );
}

/**
 * Customer display name.
 *
 * @return string
 */
function jwellery_uda_display_name() {
	$user = wp_get_current_user();
	if ( ! $user->ID ) {
		return '';
	}
	if ( $user->first_name ) {
		return $user->first_name;
	}
	return $user->display_name ? $user->display_name : $user->user_login;
}

/**
 * Avatar initials.
 *
 * @return string
 */
function jwellery_uda_initials() {
	$name = jwellery_uda_display_name();
	if ( ! $name ) {
		return 'U';
	}
	$parts = preg_split( '/\s+/', trim( $name ) );
	if ( count( $parts ) >= 2 ) {
		return strtoupper( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[1], 0, 1 ) );
	}
	return strtoupper( mb_substr( $name, 0, 2 ) );
}

/**
 * Is dashboard view.
 *
 * @return bool
 */
function jwellery_uda_is_dashboard() {
	return ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url();
}

/**
 * Section meta for sub-pages.
 *
 * @return array{title: string, desc: string}
 */
function jwellery_uda_section_meta() {
	$map = array(
		'view-order'   => array(
			__( 'Order details', 'jwellery-jewelry' ),
			__( 'Items, payment status and delivery address.', 'jwellery-jewelry' ),
		),
		'orders'       => array(
			__( 'Orders', 'jwellery-jewelry' ),
			__( 'Track purchases, payment status and delivery.', 'jwellery-jewelry' ),
		),
		'wishlist'     => array(
			__( 'Wishlist', 'jwellery-jewelry' ),
			__( 'Jewellery pieces you saved for later.', 'jwellery-jewelry' ),
		),
		'edit-address' => array(
			__( 'Addresses', 'jwellery-jewelry' ),
			__( 'Default shipping and billing addresses.', 'jwellery-jewelry' ),
		),
		'edit-account' => array(
			__( 'Account settings', 'jwellery-jewelry' ),
			__( 'Update your profile, email and password.', 'jwellery-jewelry' ),
		),
	);

	if ( function_exists( 'is_wc_endpoint_url' ) ) {
		foreach ( $map as $endpoint => $meta ) {
			if ( is_wc_endpoint_url( $endpoint ) ) {
				return array( 'title' => $meta[0], 'desc' => $meta[1] );
			}
		}
	}

	return array(
		'title' => __( 'Dashboard', 'jwellery-jewelry' ),
		'desc'  => '',
	);
}

/**
 * Hooks.
 */
function jwellery_uda_setup() {
	if ( ! function_exists( 'WC' ) ) {
		return;
	}
	remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation', 10 );
	add_action( 'woocommerce_account_navigation', 'jwellery_uda_render_sidebar', 10 );
	remove_action( 'woocommerce_account_dashboard', 'woocommerce_account_dashboard_content', 10 );
	add_action( 'woocommerce_account_dashboard', 'jwellery_uda_dashboard', 10 );
}
add_action( 'woocommerce_init', 'jwellery_uda_setup' );

/**
 * Ensure default dashboard copy is removed (WC may register hook after woocommerce_init).
 */
function jwellery_uda_remove_default_dashboard() {
	remove_action( 'woocommerce_account_dashboard', 'woocommerce_account_dashboard_content', 10 );
}
add_action( 'wp', 'jwellery_uda_remove_default_dashboard', 20 );

/**
 * Strip WooCommerce default dashboard paragraph (runs after all plugins register hooks).
 */
function jwellery_uda_strip_default_dashboard() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
		return;
	}
	remove_action( 'woocommerce_account_dashboard', 'woocommerce_account_dashboard_content', 10 );
}
add_action( 'template_redirect', 'jwellery_uda_strip_default_dashboard', 99 );

/**
 * Menu cleanup.
 *
 * @param array $items Items.
 * @return array
 */
function jwellery_uda_menu_items( $items ) {
	unset( $items['downloads'] );
	if ( isset( $items['dashboard'] ) ) {
		$items['dashboard'] = __( 'Overview', 'jwellery-jewelry' );
	}
	if ( isset( $items['customer-logout'] ) ) {
		$items['customer-logout'] = __( 'Sign out', 'jwellery-jewelry' );
	}
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'jwellery_uda_menu_items', 20 );

/**
 * Assets.
 */
function jwellery_uda_assets() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}
	$deps = array( 'jwellery-buttons' );
	if ( wp_style_is( 'woocommerce-general', 'registered' ) ) {
		$deps[] = 'woocommerce-general';
	}
	wp_enqueue_style( 'jwellery-my-account', JWELLERY_THEME_URI . '/assets/css/my-account.css', $deps, jwellery_asset_version() );
}
add_action( 'wp_enqueue_scripts', 'jwellery_uda_assets', 30 );

/**
 * Body class.
 *
 * @param array $classes Classes.
 * @return array
 */
function jwellery_uda_body_class( $classes ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$classes[] = 'jwellery-account-page';
		$classes[] = is_user_logged_in() ? 'jwellery-account-page--logged-in' : 'jwellery-account-page--guest';
	}
	return $classes;
}
add_filter( 'body_class', 'jwellery_uda_body_class' );

/**
 * Force theme WooCommerce templates (some hosts skip overrides).
 *
 * @param string $template      Path.
 * @param string $template_name Name.
 * @return string
 */
function jwellery_uda_locate_template( $template, $template_name ) {
	$overrides = array(
		'myaccount/my-account.php',
	);
	if ( ! in_array( $template_name, $overrides, true ) ) {
		return $template;
	}
	$custom = JWELLERY_THEME_DIR . '/woocommerce/' . $template_name;
	return is_readable( $custom ) ? $custom : $template;
}
add_filter( 'woocommerce_locate_template', 'jwellery_uda_locate_template', 50, 2 );

/* ——— Guest login shell (wraps default WC forms) ——— */
function jwellery_uda_guest_shell_open() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
		return;
	}

	$brand = function_exists( 'jwellery_brand_name' ) ? jwellery_brand_name() : get_bloginfo( 'name' );
	$shop  = function_exists( 'jwellery_get_shop_url' ) ? jwellery_get_shop_url() : home_url( '/shop/' );
	?>
	<div class="jwellery-uda jwellery-uda--guest jwellery-uda--animate">
		<div class="jwellery-uda-guest">
			<nav class="jwellery-uda-guest__crumb jwellery-uda-reveal" aria-label="<?php esc_attr_e( 'Breadcrumb', 'jwellery-jewelry' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'jwellery-jewelry' ); ?></a>
				<span aria-hidden="true">/</span>
				<span><?php esc_html_e( 'My Account', 'jwellery-jewelry' ); ?></span>
			</nav>
			<div class="jwellery-uda-guest__layout">
				<header class="jwellery-uda-guest__hero jwellery-uda-reveal" aria-label="<?php esc_attr_e( 'Account welcome', 'jwellery-jewelry' ); ?>">
					<div class="jwellery-uda-guest__hero-text">
						<p class="jwellery-uda-guest__brand"><?php echo esc_html( $brand ); ?></p>
						<h1 class="jwellery-uda-guest__title"><?php esc_html_e( 'Welcome to your account', 'jwellery-jewelry' ); ?></h1>
						<p class="jwellery-uda-guest__lead"><?php esc_html_e( 'Sign in or create an account to track orders and save your wishlist.', 'jwellery-jewelry' ); ?></p>
					</div>
					<div class="jwellery-uda-guest__hero-actions">
						<div class="jwellery-uda-guest__trust">
							<span><?php esc_html_e( 'Free shipping on all orders', 'jwellery-jewelry' ); ?></span>
							<span><?php esc_html_e( 'Easy returns', 'jwellery-jewelry' ); ?></span>
							<span><?php esc_html_e( 'UPI & COD', 'jwellery-jewelry' ); ?></span>
						</div>
						<a class="jwellery-btn jwellery-btn-outline jwellery-uda-guest__shop-link" href="<?php echo esc_url( $shop ); ?>">
							<?php esc_html_e( 'Continue shopping', 'jwellery-jewelry' ); ?>
						</a>
					</div>
				</header>
				<div class="jwellery-uda-guest__forms-panel jwellery-uda-reveal jwellery-uda-reveal--delay">
					<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
						<div class="jwellery-uda-guest__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Account access', 'jwellery-jewelry' ); ?>">
							<button type="button" class="jwellery-uda-guest__tab is-active" role="tab" aria-selected="true" data-uda-tab="login">
								<?php esc_html_e( 'Sign in', 'jwellery-jewelry' ); ?>
							</button>
							<button type="button" class="jwellery-uda-guest__tab" role="tab" aria-selected="false" data-uda-tab="register">
								<?php esc_html_e( 'Create account', 'jwellery-jewelry' ); ?>
							</button>
						</div>
					<?php endif; ?>
					<div class="jwellery-uda-guest__card">
	<?php
}
add_action( 'woocommerce_before_customer_login_form', 'jwellery_uda_guest_shell_open', 1 );

function jwellery_uda_guest_shell_close() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
		return;
	}
	echo '</div></div></div></div></div>';
}
add_action( 'woocommerce_after_customer_login_form', 'jwellery_uda_guest_shell_close', 99 );

/* ——— Sidebar ——— */
function jwellery_uda_render_sidebar() {
	if ( ! is_user_logged_in() || ! function_exists( 'wc_get_account_menu_items' ) ) {
		return;
	}

	$user  = wp_get_current_user();
	$name  = jwellery_uda_display_name();
	$email = $user->user_email;
	$items = wc_get_account_menu_items();
	?>
	<aside class="jwellery-uda__sidebar" aria-label="<?php esc_attr_e( 'Account navigation', 'jwellery-jewelry' ); ?>">
		<div class="jwellery-uda__user">
			<span class="jwellery-uda__avatar" aria-hidden="true"><?php echo esc_html( jwellery_uda_initials() ); ?></span>
			<div class="jwellery-uda__user-text">
				<strong><?php echo esc_html( $name ); ?></strong>
				<span><?php echo esc_html( $email ); ?></span>
			</div>
		</div>

		<nav class="woocommerce-MyAccount-navigation jwellery-uda__nav">
			<ul>
				<?php foreach ( $items as $endpoint => $label ) : ?>
					<?php if ( 'customer-logout' === $endpoint ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<li class="<?php echo esc_attr( jwellery_uda_nav_classes( $endpoint ) ); ?>">
						<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>">
							<?php
							if ( function_exists( 'jwellery_icon_svg' ) ) {
								echo jwellery_icon_svg( jwellery_uda_nav_icon( $endpoint ), 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
							<span><?php echo esc_html( $label ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( isset( $items['customer-logout'] ) ) : ?>
				<div class="jwellery-uda__nav-foot">
					<a class="jwellery-uda__signout" href="<?php echo esc_url( wc_get_account_endpoint_url( 'customer-logout' ) ); ?>">
						<?php
						if ( function_exists( 'jwellery_icon_svg' ) ) {
							echo jwellery_icon_svg( 'logout', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
						<span><?php echo esc_html( $items['customer-logout'] ); ?></span>
					</a>
				</div>
			<?php endif; ?>
		</nav>
	</aside>
	<?php
}

/**
 * Mobile nav pills.
 */
function jwellery_uda_mobile_nav() {
	if ( ! is_user_logged_in() || ! function_exists( 'wc_get_account_menu_items' ) ) {
		return;
	}

	$short = array( 'dashboard', 'orders', 'wishlist', 'edit-address', 'edit-account' );
	$items = wc_get_account_menu_items();
	$labels = array(
		'dashboard'    => __( 'Overview', 'jwellery-jewelry' ),
		'orders'       => __( 'Orders', 'jwellery-jewelry' ),
		'wishlist'     => __( 'Saved', 'jwellery-jewelry' ),
		'edit-address' => __( 'Address', 'jwellery-jewelry' ),
		'edit-account' => __( 'Profile', 'jwellery-jewelry' ),
	);
	?>
	<nav class="jwellery-uda__mobnav" aria-label="<?php esc_attr_e( 'Account sections', 'jwellery-jewelry' ); ?>">
		<?php foreach ( $short as $endpoint ) : ?>
			<?php if ( ! isset( $items[ $endpoint ] ) ) : ?>
				<?php continue; ?>
			<?php endif; ?>
			<a
				class="jwellery-uda__mobnav-link<?php echo function_exists( 'wc_is_current_account_menu_item' ) && wc_is_current_account_menu_item( $endpoint ) ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
			>
				<?php echo esc_html( $labels[ $endpoint ] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Sub-page header only (not on dashboard).
 */
function jwellery_uda_page_header() {
	if ( ! is_user_logged_in() || jwellery_uda_is_dashboard() ) {
		return;
	}
	$meta = jwellery_uda_section_meta();
	?>
	<header class="jwellery-uda__pagehead">
		<h1><?php echo esc_html( $meta['title'] ); ?></h1>
		<?php if ( $meta['desc'] ) : ?>
			<p><?php echo esc_html( $meta['desc'] ); ?></p>
		<?php endif; ?>
	</header>
	<?php
}
add_action( 'woocommerce_account_content', 'jwellery_uda_page_header', 3 );

/**
 * Dashboard — full redesign.
 */
function jwellery_uda_dashboard() {
	$user = wp_get_current_user();
	if ( ! $user->ID ) {
		return;
	}

	$name           = jwellery_uda_display_name();
	$orders         = 0;
	$wishlist       = function_exists( 'jwellery_wishlist_count' ) ? (int) jwellery_wishlist_count() : 0;
	$shop           = function_exists( 'jwellery_get_shop_url' ) ? jwellery_get_shop_url() : home_url( '/shop/' );
	$wa             = function_exists( 'jwellery_whatsapp_url' ) ? jwellery_whatsapp_url() : '';
	$member_since   = date_i18n( 'M Y', strtotime( $user->user_registered ) );

	if ( function_exists( 'wc_get_customer_order_count' ) ) {
		$orders = (int) wc_get_customer_order_count( $user->ID );
	}

	$actions = array(
		array(
			'icon'  => 'shop',
			'title' => __( 'Shop jewellery', 'jwellery-jewelry' ),
			'desc'  => __( 'Explore new arrivals', 'jwellery-jewelry' ),
			'url'   => $shop,
			'tone'  => 'gold',
		),
		array(
			'icon'  => 'orders',
			'title' => __( 'Order history', 'jwellery-jewelry' ),
			'desc'  => __( 'Track all purchases', 'jwellery-jewelry' ),
			'url'   => wc_get_account_endpoint_url( 'orders' ),
			'tone'  => 'maroon',
		),
		array(
			'icon'  => 'heart',
			'title' => __( 'My wishlist', 'jwellery-jewelry' ),
			'desc'  => __( 'View saved pieces', 'jwellery-jewelry' ),
			'url'   => function_exists( 'jwellery_wishlist_url' ) ? jwellery_wishlist_url() : wc_get_account_endpoint_url( 'wishlist' ),
			'tone'  => 'rose',
		),
		array(
			'icon'  => 'settings',
			'title' => __( 'Account settings', 'jwellery-jewelry' ),
			'desc'  => __( 'Edit profile & password', 'jwellery-jewelry' ),
			'url'   => wc_get_account_endpoint_url( 'edit-account' ),
			'tone'  => 'cream',
		),
	);
	?>
	<div class="jwellery-uda-dashboard">
		<section class="jwellery-uda-hero">
			<div class="jwellery-uda-hero__left">
				<span class="jwellery-uda-hero__avatar"><?php echo esc_html( jwellery_uda_initials() ); ?></span>
				<div>
					<p class="jwellery-uda-hero__eyebrow"><?php esc_html_e( 'Customer dashboard', 'jwellery-jewelry' ); ?></p>
					<h1 class="jwellery-uda-hero__title">
						<?php
						printf(
							/* translators: %s: name */
							esc_html__( 'Hi, %s', 'jwellery-jewelry' ),
							esc_html( $name ? $name : __( 'there', 'jwellery-jewelry' ) )
						);
						?>
					</h1>
					<p class="jwellery-uda-hero__meta">
						<?php
						printf(
							/* translators: %s: month year */
							esc_html__( 'Member since %s', 'jwellery-jewelry' ),
							esc_html( $member_since )
						);
						?>
					</p>
				</div>
			</div>
			<div class="jwellery-uda-hero__actions">
				<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Shop now', 'jwellery-jewelry' ); ?></a>
				<?php if ( $wa ) : ?>
					<a class="jwellery-btn jwellery-btn-outline jwellery-uda-wa-btn" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get help', 'jwellery-jewelry' ); ?></a>
				<?php endif; ?>
			</div>
		</section>

		<section class="jwellery-uda-metrics" aria-label="<?php esc_attr_e( 'Account summary', 'jwellery-jewelry' ); ?>">
			<article class="jwellery-uda-metric">
				<span class="jwellery-uda-metric__value"><?php echo (int) $orders; ?></span>
				<span class="jwellery-uda-metric__label"><?php esc_html_e( 'Total orders', 'jwellery-jewelry' ); ?></span>
			</article>
			<article class="jwellery-uda-metric">
				<span class="jwellery-uda-metric__value"><?php echo (int) $wishlist; ?></span>
				<span class="jwellery-uda-metric__label"><?php esc_html_e( 'Wishlist items', 'jwellery-jewelry' ); ?></span>
			</article>
			<article class="jwellery-uda-metric jwellery-uda-metric--accent">
				<span class="jwellery-uda-metric__value">&#10022;</span>
				<span class="jwellery-uda-metric__label"><?php esc_html_e( 'Premium member', 'jwellery-jewelry' ); ?></span>
			</article>
		</section>

		<section class="jwellery-uda-actions">
			<h2 class="jwellery-uda-section-title"><?php esc_html_e( 'Quick actions', 'jwellery-jewelry' ); ?></h2>
			<div class="jwellery-uda-actions__grid">
				<?php foreach ( $actions as $action ) : ?>
					<a class="jwellery-uda-action jwellery-uda-action--<?php echo esc_attr( $action['tone'] ); ?>" href="<?php echo esc_url( $action['url'] ); ?>">
						<span class="jwellery-uda-action__icon" aria-hidden="true">
							<?php echo function_exists( 'jwellery_icon_svg' ) ? jwellery_icon_svg( $action['icon'], 22 ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
						<span class="jwellery-uda-action__title"><?php echo esc_html( $action['title'] ); ?></span>
						<span class="jwellery-uda-action__desc"><?php echo esc_html( $action['desc'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<?php jwellery_uda_recent_orders(); ?>
	</div>
	<?php
}

/**
 * Recent orders table on dashboard.
 */
function jwellery_uda_recent_orders() {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return;
	}

	$order_list = wc_get_orders(
		array(
			'customer_id' => get_current_user_id(),
			'limit'       => 5,
			'orderby'     => 'date',
			'order'       => 'DESC',
		)
	);
	?>
	<section class="jwellery-uda-orders">
		<div class="jwellery-uda-orders__head">
			<h2 class="jwellery-uda-section-title"><?php esc_html_e( 'Recent orders', 'jwellery-jewelry' ); ?></h2>
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'View all', 'jwellery-jewelry' ); ?></a>
		</div>

		<?php if ( empty( $order_list ) ) : ?>
			<div class="jwellery-uda-empty">
				<p><?php esc_html_e( 'No orders yet. Start shopping to see your order history here.', 'jwellery-jewelry' ); ?></p>
				<a class="jwellery-btn jwellery-btn-primary" href="<?php echo esc_url( function_exists( 'jwellery_get_shop_url' ) ? jwellery_get_shop_url() : home_url( '/shop/' ) ); ?>">
					<?php esc_html_e( 'Browse collection', 'jwellery-jewelry' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="jwellery-uda-table-wrap">
				<table class="jwellery-uda-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Order', 'jwellery-jewelry' ); ?></th>
							<th><?php esc_html_e( 'Date', 'jwellery-jewelry' ); ?></th>
							<th><?php esc_html_e( 'Status', 'jwellery-jewelry' ); ?></th>
							<th><?php esc_html_e( 'Total', 'jwellery-jewelry' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $order_list as $order ) : ?>
							<?php if ( ! $order instanceof WC_Order ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<tr>
								<td data-label="<?php esc_attr_e( 'Order', 'jwellery-jewelry' ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></td>
								<td data-label="<?php esc_attr_e( 'Date', 'jwellery-jewelry' ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
								<td data-label="<?php esc_attr_e( 'Status', 'jwellery-jewelry' ); ?>">
									<span class="jwellery-uda-badge jwellery-uda-badge--<?php echo esc_attr( $order->get_status() ); ?>">
										<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
									</span>
								</td>
								<td data-label="<?php esc_attr_e( 'Total', 'jwellery-jewelry' ); ?>"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
								<td data-label="">
									<a class="jwellery-uda-link" href="<?php echo esc_url( $order->get_view_order_url() ); ?>"><?php esc_html_e( 'Details', 'jwellery-jewelry' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

// Back-compat aliases for older hooks.
function jwellery_account_mobile_toolbar() {
	jwellery_uda_mobile_nav();
}
