<?php
/**
 * Store setup: categories, menus, pages (ramyanagendra.com structure).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Category definitions.
 */
function jwellery_get_reference_categories() {
	return array(
		'ear-rings'            => array(
			'name' => 'Ear Rings',
			'desc' => 'Discover effortless elegance with our Earrings Collection, where style meets convenience.',
		),
		'studs'                => array(
			'name' => 'Studs',
			'desc' => 'Everyday Panchaloham and fashion studs — lightweight, elegant, and easy to wear.',
		),
		'necklaces'            => array(
			'name' => 'Necklaces',
			'desc' => 'Elevate your style with our exquisite Necklace Collection, where each piece tells a story.',
		),
		'chockers'             => array(
			'name' => 'Chockers',
			'desc' => 'Unveil the allure of timeless elegance with our Chokers Collection, designed for every occasion.',
		),
		'bangles'              => array(
			'name' => 'Bangles',
			'desc' => 'Embrace tradition with our Bangles Collection, where each piece reflects timeless artistry.',
		),
		'long-harams'          => array(
			'name' => 'Long Harams',
			'desc' => 'Embrace the grandeur of tradition with our Long Harams Collection, featuring jewelry for celebrations.',
		),
		'handmade-collection'  => array(
			'name' => 'Handmade Collection',
			'desc' => 'Curated handmade imitation jewelry.',
		),
		'instagram-collection' => array(
			'name' => 'Instagram Collection',
			'desc' => 'As seen on our Instagram — limited styles.',
		),
		'latest-collection'    => array(
			'name' => 'Latest Collection',
			'desc' => 'New arrivals and latest designs.',
		),
	);
}

/**
 * Create product categories.
 */
function jwellery_create_reference_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	foreach ( jwellery_get_reference_categories() as $slug => $data ) {
		if ( get_term_by( 'slug', $slug, 'product_cat' ) ) {
			continue;
		}
		wp_insert_term(
			$data['name'],
			'product_cat',
			array(
				'slug'        => $slug,
				'description' => $data['desc'],
			)
		);
	}
}

/**
 * Build primary menu with Shop submenu.
 */
function jwellery_create_reference_menu() {
	if ( ! function_exists( 'jwellery_get_shop_url' ) ) {
		return;
	}

	$menu_name = 'Main Menu';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : wp_create_nav_menu( $menu_name );

	if ( is_wp_error( $menu_id ) || ! $menu_id ) {
		return;
	}

	$items = wp_get_nav_menu_items( $menu_id );
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			if ( ! empty( $item->ID ) ) {
				wp_delete_post( (int) $item->ID, true );
			}
		}
	}

	$shop_url = jwellery_get_shop_url();

	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'  => 'Home',
			'menu-item-url'    => home_url( '/' ),
			'menu-item-type'   => 'custom',
			'menu-item-status' => 'publish',
		)
	);

	$about_page = function_exists( 'jwellery_find_store_page' ) ? jwellery_find_store_page( 'about' ) : get_page_by_path( 'about' );
	if ( $about_page && ! empty( $about_page->ID ) ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'About',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => (int) $about_page->ID,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}

	$shop_parent = wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'  => 'Shop',
			'menu-item-url'    => $shop_url,
			'menu-item-type'   => 'custom',
			'menu-item-status' => 'publish',
		)
	);

	if ( is_wp_error( $shop_parent ) || ! $shop_parent ) {
		return;
	}

	$shop_parent = (int) $shop_parent;

	$submenus = array(
		'Handmade Collection'  => jwellery_term_link( 'handmade-collection' ),
		'Instagram Collection' => jwellery_term_link( 'instagram-collection' ),
		'Best Sellers'         => add_query_arg( 'featured', '1', $shop_url ),
		'Latest Collection'    => jwellery_term_link( 'latest-collection' ),
		'All Collections'      => $shop_url,
		'All Products'         => function_exists( 'jwellery_all_products_url' ) ? jwellery_all_products_url() : $shop_url,
	);

	foreach ( $submenus as $title => $url ) {
		if ( is_wp_error( $url ) || ! $url ) {
			$url = $shop_url;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $title,
				'menu-item-url'       => $url,
				'menu-item-type'      => 'custom',
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $shop_parent,
			)
		);
	}

	foreach ( array( 'track-order' => 'Track Order', 'contact' => 'Contact' ) as $slug => $title ) {
		$page = function_exists( 'jwellery_find_store_page' ) ? jwellery_find_store_page( $slug ) : get_page_by_path( $slug );
		if ( $page && ! empty( $page->ID ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $title,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => (int) $page->ID,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
	}

	$locations            = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Map menu item titles to store page keys.
 *
 * @return array<string, string[]>
 */
function jwellery_menu_page_title_map() {
	return array(
		'about'            => array( 'about', 'about us' ),
		'contact'          => array( 'contact', 'contact us' ),
		'track-order'      => array( 'track order', 'track your order' ),
		'privacy-policy'   => array( 'privacy policy' ),
		'refund-policy'    => array( 'refund policy', 'cancellation & refund', 'cancellation and refund' ),
		'shipping-policy'  => array( 'shipping policy', 'shipping & delivery', 'shipping and delivery' ),
		'terms-of-service' => array( 'terms of service', 'terms & conditions', 'terms and conditions' ),
	);
}

/**
 * Top-level primary menu order: Home, About, Shop, Track Order, Contact.
 *
 * @return array<string, int>
 */
function jwellery_primary_menu_order_map() {
	return array(
		'home'              => 1,
		'about'             => 2,
		'about us'          => 2,
		'shop'              => 3,
		'track order'       => 4,
		'track your order'  => 4,
		'contact'           => 5,
		'contact us'        => 5,
	);
}

/**
 * Normalize primary menu item order on existing WordPress menus.
 */
function jwellery_fix_primary_menu_order() {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['primary'] ) ) {
		return;
	}

	$menu_id   = (int) $locations['primary'];
	$items     = wp_get_nav_menu_items( $menu_id );
	$order_map = jwellery_primary_menu_order_map();

	if ( ! is_array( $items ) ) {
		return;
	}

	foreach ( $items as $item ) {
		if ( empty( $item->ID ) || (int) $item->menu_item_parent !== 0 ) {
			continue;
		}

		$title_norm = strtolower( trim( wp_strip_all_tags( (string) $item->title ) ) );
		$position   = 0;

		if ( isset( $order_map[ $title_norm ] ) ) {
			$position = (int) $order_map[ $title_norm ];
		} elseif ( false !== strpos( $title_norm, 'shop' ) ) {
			$position = 3;
		}

		if ( $position < 1 || (int) $item->menu_order === $position ) {
			continue;
		}

		wp_update_nav_menu_item(
			$menu_id,
			(int) $item->ID,
			array(
				'menu-item-title'     => $item->title,
				'menu-item-url'       => $item->url,
				'menu-item-type'      => $item->type,
				'menu-item-object'    => $item->object,
				'menu-item-object-id' => (int) $item->object_id,
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $position,
			)
		);
	}
}

/**
 * Repair primary menu links to real store pages (About, Contact, policies, Shop).
 */
function jwellery_repair_primary_menu_links() {
	if ( ! function_exists( 'jwellery_get_shop_url' ) ) {
		return;
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['primary'] ) ) {
		jwellery_create_reference_menu();
		return;
	}

	$menu_id = (int) $locations['primary'];
	$items   = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) ) {
		jwellery_create_reference_menu();
		return;
	}

	$title_map   = jwellery_menu_page_title_map();
	$linked_keys = array();
	$shop_url    = jwellery_get_shop_url();

	foreach ( $items as $item ) {
		if ( empty( $item->ID ) ) {
			continue;
		}

		$title_norm = strtolower( trim( wp_strip_all_tags( $item->title ) ) );

		if ( 0 === (int) $item->menu_item_parent && ( 'shop' === $title_norm || false !== strpos( $title_norm, 'shop' ) ) ) {
			if ( $item->url !== $shop_url ) {
				wp_update_nav_menu_item(
					$menu_id,
					(int) $item->ID,
					array(
						'menu-item-title'  => $item->title,
						'menu-item-url'    => $shop_url,
						'menu-item-type'   => 'custom',
						'menu-item-status' => 'publish',
					)
				);
			}
			continue;
		}

		foreach ( $title_map as $key => $aliases ) {
			if ( ! in_array( $title_norm, $aliases, true ) ) {
				continue;
			}

			$page = function_exists( 'jwellery_find_store_page' ) ? jwellery_find_store_page( $key ) : get_page_by_path( $key );
			if ( ! $page instanceof WP_Post ) {
				break;
			}

			$linked_keys[ $key ] = true;
			if ( 'post_type' === $item->type && 'page' === $item->object && (int) $item->object_id === (int) $page->ID ) {
				break;
			}

			wp_update_nav_menu_item(
				$menu_id,
				(int) $item->ID,
				array(
					'menu-item-title'     => $item->title,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => (int) $page->ID,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
			break;
		}
	}

	$add_titles = array(
		'about'       => 'About',
		'track-order' => 'Track Order',
		'contact'     => 'Contact',
	);

	foreach ( $add_titles as $key => $label ) {
		if ( isset( $linked_keys[ $key ] ) ) {
			continue;
		}
		$page = function_exists( 'jwellery_find_store_page' ) ? jwellery_find_store_page( $key ) : get_page_by_path( $key );
		if ( ! $page instanceof WP_Post ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $label,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => (int) $page->ID,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}

	jwellery_fix_primary_menu_order();
}

/**
 * Run full setup (manual or first activation only).
 */
function jwellery_run_reference_setup() {
	if ( ! function_exists( 'jwellery_create_pages' ) ) {
		return;
	}
	jwellery_create_pages();
	if ( function_exists( 'jwellery_sync_store_page_content' ) ) {
		jwellery_sync_store_page_content( true );
		jwellery_mark_pages_content_version();
	}
	if ( function_exists( 'jwellery_apply_store_config' ) ) {
		jwellery_apply_store_config();
	}
	jwellery_create_reference_categories();
	if ( function_exists( 'jwellery_register_product_attributes' ) ) {
		jwellery_register_product_attributes();
	}
	jwellery_create_reference_menu();
	jwellery_repair_primary_menu_links();
	if ( function_exists( 'jwellery_create_demo_products' ) ) {
		jwellery_create_demo_products();
	}
	if ( function_exists( 'jwellery_import_demo_product_images' ) ) {
		jwellery_import_demo_product_images( true );
	}
	if ( function_exists( 'jwellery_create_default_coupons' ) ) {
		jwellery_create_default_coupons();
	}
	if ( function_exists( 'jwellery_assign_category_thumbnails' ) ) {
		jwellery_assign_category_thumbnails();
	}
	jwellery_set_front_page();
	jwellery_force_store_live();
	update_option( 'jwellery_reference_setup_done', JWELLERY_THEME_VERSION );
	flush_rewrite_rules( false );
}

/**
 * Theme activation — defer heavy setup to wp-admin (avoids critical error on shared hosting).
 */
function jwellery_on_reference_theme_activation() {
	update_option( 'jwellery_pending_reference_setup', '1' );
	jwellery_force_store_live();
}
add_action( 'after_switch_theme', 'jwellery_on_reference_theme_activation' );

/**
 * Run store setup in admin after activation (needs WooCommerce active).
 */
function jwellery_run_pending_reference_setup() {
	if ( ! get_option( 'jwellery_pending_reference_setup' ) ) {
		return;
	}
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	delete_option( 'jwellery_pending_reference_setup' );

	if ( get_option( 'jwellery_reference_setup_done' ) ) {
		jwellery_force_store_live();
		if ( function_exists( 'jwellery_maintain_store' ) ) {
			jwellery_maintain_store( true );
		}
		return;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		set_transient( 'jwellery_setup_needs_woocommerce', 1, WEEK_IN_SECONDS );
		return;
	}

	jwellery_run_reference_setup();
}
add_action( 'admin_init', 'jwellery_run_pending_reference_setup', 5 );

/**
 * Admin notice when WooCommerce is required for setup.
 */
function jwellery_admin_notice_needs_woocommerce() {
	if ( ! current_user_can( 'activate_plugins' ) || class_exists( 'WooCommerce' ) ) {
		return;
	}
	if ( JWELLERY_THEME_SLUG !== get_template() ) {
		return;
	}
	if ( ! get_transient( 'jwellery_setup_needs_woocommerce' ) && ! get_option( 'jwellery_pending_reference_setup' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Jwellery Jewelry:</strong> ';
	echo esc_html__( 'Install and activate WooCommerce, then open Appearance → Store Setup to finish.', 'jwellery-jewelry' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'jwellery_admin_notice_needs_woocommerce' );

/**
 * Admin setup page.
 */
function jwellery_add_setup_menu() {
	add_theme_page(
		__( 'Store Setup', 'jwellery-jewelry' ),
		__( 'Store Setup', 'jwellery-jewelry' ),
		'manage_options',
		'jwellery-store-setup',
		'jwellery_store_setup_page'
	);
}
add_action( 'admin_menu', 'jwellery_add_setup_menu' );

/**
 * Setup page UI.
 */
function jwellery_store_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'jwellery-jewelry' ) );
	}

	if ( isset( $_POST['jwellery_run_setup'] ) && check_admin_referer( 'jwellery_setup' ) ) {
		jwellery_run_reference_setup();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Setup complete: menu, categories, 19 demo products, and product images.', 'jwellery-jewelry' ) . '</p></div>';
	}
	if ( isset( $_POST['jwellery_demo_products'] ) && check_admin_referer( 'jwellery_setup' ) ) {
		$n = jwellery_create_demo_products();
		echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Demo products ready (%d new items).', 'jwellery-jewelry' ), (int) $n ) ) . '</p></div>';
	}
	if ( isset( $_POST['jwellery_import_images'] ) && check_admin_referer( 'jwellery_setup' ) ) {
		$n = function_exists( 'jwellery_import_demo_product_images' ) ? jwellery_import_demo_product_images( true ) : 0;
		$repaired = function_exists( 'jwellery_repair_missing_product_images' ) ? jwellery_repair_missing_product_images() : 0;
		echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Product images replaced (%1$d products). Missing thumbnails repaired: %2$d.', 'jwellery-jewelry' ), (int) $n, (int) $repaired ) ) . '</p></div>';
	}
	if ( isset( $_POST['jwellery_update_pages'] ) && check_admin_referer( 'jwellery_setup' ) ) {
		if ( function_exists( 'jwellery_maintain_store' ) ) {
			jwellery_maintain_store( true );
		}
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Store pages updated and header/footer menu links repaired.', 'jwellery-jewelry' ) . '</p></div>';
	}
	if ( isset( $_POST['jwellery_repair_links'] ) && check_admin_referer( 'jwellery_setup' ) ) {
		if ( function_exists( 'jwellery_repair_primary_menu_links' ) ) {
			jwellery_repair_primary_menu_links();
		}
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Primary menu links repaired (About, Contact, Shop, policies).', 'jwellery-jewelry' ) . '</p></div>';
	}
	if ( isset( $_POST['jwellery_store_config'] ) && check_admin_referer( 'jwellery_setup' ) ) {
		if ( function_exists( 'jwellery_apply_store_config' ) ) {
			jwellery_apply_store_config();
		}
		$n = function_exists( 'jwellery_create_default_coupons' ) ? jwellery_create_default_coupons() : 0;
		echo '<div class="notice notice-success"><p>' . esc_html__( 'INR currency enabled. Promo codes: WELCOME10 (10%), FLAT50 (₹50 off), SALE15 (15%).', 'jwellery-jewelry' ) . ' ' . esc_html( sprintf( __( '(%d new coupons created.)', 'jwellery-jewelry' ), (int) $n ) ) . '</p></div>';
	}
	if ( isset( $_POST['jwellery_fix_checkout'] ) && check_admin_referer( 'jwellery_setup' ) ) {
		delete_transient( 'jwellery_checkout_fix_lock' );
		delete_transient( 'jwellery_checkout_block_fix_lock' );
		$report = function_exists( 'jwellery_run_checkout_payment_fix' ) ? jwellery_run_checkout_payment_fix() : array();
		echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Checkout fix ran:', 'jwellery-jewelry' ) . '</strong></p><ul style="margin:0.5em 0 0 1.2em;list-style:disc;">';
		foreach ( $report as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul></div>';
	}
	$product_count = class_exists( 'WooCommerce' ) ? wp_count_posts( 'product' )->publish : 0;
	$currency      = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Jwellery Store Setup', 'jwellery-jewelry' ); ?></h1>
		<p><?php esc_html_e( 'Match your store to ramyanagendra.com — run full setup once.', 'jwellery-jewelry' ); ?></p>
		<p><strong><?php esc_html_e( 'Products:', 'jwellery-jewelry' ); ?></strong> <?php echo (int) $product_count; ?>
		&nbsp;|&nbsp;<strong><?php esc_html_e( 'Currency:', 'jwellery-jewelry' ); ?></strong> <?php echo esc_html( $currency ? $currency : '—' ); ?></p>
		<p><?php esc_html_e( 'Promo codes at checkout: WELCOME10, FLAT50, SALE15 (after you run “Enable INR + promo codes” below).', 'jwellery-jewelry' ); ?></p>
		<form method="post" style="margin-bottom:12px;">
			<?php wp_nonce_field( 'jwellery_setup' ); ?>
			<button type="submit" name="jwellery_run_setup" class="button button-primary button-hero"><?php esc_html_e( 'Full setup (menu + categories + 19 products)', 'jwellery-jewelry' ); ?></button>
		</form>
		<form method="post" style="margin-bottom:12px;">
			<?php wp_nonce_field( 'jwellery_setup' ); ?>
			<button type="submit" name="jwellery_demo_products" class="button"><?php esc_html_e( 'Create demo products only', 'jwellery-jewelry' ); ?></button>
		</form>
		<form method="post" style="margin-bottom:12px;">
			<?php wp_nonce_field( 'jwellery_setup' ); ?>
			<button type="submit" name="jwellery_import_images" class="button"><?php esc_html_e( 'Replace & import product images', 'jwellery-jewelry' ); ?></button>
		</form>
		<form method="post" style="margin-bottom:12px;">
			<?php wp_nonce_field( 'jwellery_setup' ); ?>
			<button type="submit" name="jwellery_update_pages" class="button"><?php esc_html_e( 'Update pages + fix menu links', 'jwellery-jewelry' ); ?></button>
		</form>
		<form method="post" style="margin-bottom:12px;">
			<?php wp_nonce_field( 'jwellery_setup' ); ?>
			<button type="submit" name="jwellery_repair_links" class="button"><?php esc_html_e( 'Fix header menu links only', 'jwellery-jewelry' ); ?></button>
		</form>
		<form method="post" style="margin-bottom:12px;">
			<?php wp_nonce_field( 'jwellery_setup' ); ?>
			<button type="submit" name="jwellery_fix_checkout" class="button button-primary"><?php esc_html_e( 'Fix checkout & UPI payment', 'jwellery-jewelry' ); ?></button>
		</form>
		<form method="post">
			<?php wp_nonce_field( 'jwellery_setup' ); ?>
			<button type="submit" name="jwellery_store_config" class="button button-secondary"><?php esc_html_e( 'Enable INR + create promo codes', 'jwellery-jewelry' ); ?></button>
		</form>
		<p><?php esc_html_e( 'Then: Settings → General → change Site title from Hostinger URL to your brand name.', 'jwellery-jewelry' ); ?></p>
	</div>
	<?php
}
