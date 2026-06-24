<?php
/**
 * Jwellery Jewelry theme functions.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

define( 'JWELLERY_THEME_VERSION', '4.6.58' );
define( 'JWELLERY_THEME_DIR', get_template_directory() );
define( 'JWELLERY_THEME_URI', get_template_directory_uri() );
define( 'JWELLERY_THEME_SLUG', 'jwellery-jewelry' );

/**
 * Cache-busting version for CSS/JS (changes when theme files update).
 *
 * @return string
 */
function jwellery_asset_version() {
	$style = JWELLERY_THEME_DIR . '/style.css';
	$mtime = file_exists( $style ) ? (string) filemtime( $style ) : '';
	return JWELLERY_THEME_VERSION . ( $mtime ? '.' . $mtime : '' );
}

/**
 * Warn in admin if theme was installed from FLAT zip (wrong folder name).
 */
function jwellery_wrong_theme_folder_notice() {
	if ( ! current_user_can( 'switch_themes' ) ) {
		return;
	}
	$folder = basename( JWELLERY_THEME_DIR );
	if ( JWELLERY_THEME_SLUG === $folder ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Jwellery theme: wrong install folder', 'jwellery-jewelry' ) . '</strong> â€” ';
	echo esc_html(
		sprintf(
			/* translators: 1: current folder, 2: expected folder */
			__( 'Active theme path is "%1$s" but must be "%2$s". Delete this theme, upload 1-THEME-UPLOAD-jwellery-jewelry.zip (not FLAT), activate Jwellery Jewelry.', 'jwellery-jewelry' ),
			$folder,
			JWELLERY_THEME_SLUG
		)
	);
	echo '</p></div>';
}
add_action( 'admin_notices', 'jwellery_wrong_theme_folder_notice' );

/**
 * HTML comment for verifying deployed theme version (View Page Source).
 */
function jwellery_theme_version_comment() {
	echo "\n<!-- Jwellery Jewelry theme " . esc_attr( JWELLERY_THEME_VERSION ) . ' | folder: ' . esc_attr( basename( JWELLERY_THEME_DIR ) ) . " -->\n";
}
add_action( 'wp_footer', 'jwellery_theme_version_comment', 99 );

require_once JWELLERY_THEME_DIR . '/inc/page-content.php';
require_once JWELLERY_THEME_DIR . '/inc/setup.php';
require_once JWELLERY_THEME_DIR . '/inc/icons.php';
require_once JWELLERY_THEME_DIR . '/inc/branding.php';
require_once JWELLERY_THEME_DIR . '/inc/woocommerce.php';
require_once JWELLERY_THEME_DIR . '/inc/category-thumbnails.php';
require_once JWELLERY_THEME_DIR . '/inc/page-design.php';
require_once JWELLERY_THEME_DIR . '/inc/homepage.php';
require_once JWELLERY_THEME_DIR . '/inc/customizer.php';
require_once JWELLERY_THEME_DIR . '/inc/social-icons.php';
require_once JWELLERY_THEME_DIR . '/inc/menu-fallback.php';
require_once JWELLERY_THEME_DIR . '/inc/demo-products.php';
require_once JWELLERY_THEME_DIR . '/inc/catalog-sync.php';
require_once JWELLERY_THEME_DIR . '/inc/product-images.php';
require_once JWELLERY_THEME_DIR . '/inc/store-config.php';
require_once JWELLERY_THEME_DIR . '/inc/checkout-fix.php';
require_once JWELLERY_THEME_DIR . '/inc/coupons.php';
require_once JWELLERY_THEME_DIR . '/inc/ui-enhancements.php';
require_once JWELLERY_THEME_DIR . '/inc/header-icons.php';
require_once JWELLERY_THEME_DIR . '/inc/home-design.php';
require_once JWELLERY_THEME_DIR . '/inc/km-sections.php';
require_once JWELLERY_THEME_DIR . '/inc/reference-setup.php';
require_once JWELLERY_THEME_DIR . '/inc/store-live.php';
require_once JWELLERY_THEME_DIR . '/inc/deploy-purge.php';
require_once JWELLERY_THEME_DIR . '/inc/shop-experience.php';
require_once JWELLERY_THEME_DIR . '/inc/wishlist.php';
require_once JWELLERY_THEME_DIR . '/inc/home-sections.php';
require_once JWELLERY_THEME_DIR . '/inc/product-enhancements.php';
require_once JWELLERY_THEME_DIR . '/inc/single-product.php';
require_once JWELLERY_THEME_DIR . '/inc/shop-filters.php';
require_once JWELLERY_THEME_DIR . '/inc/shop-catalog.php';
require_once JWELLERY_THEME_DIR . '/inc/my-account.php';
require_once JWELLERY_THEME_DIR . '/inc/security.php';
require_once JWELLERY_THEME_DIR . '/inc/login-captcha.php';
require_once JWELLERY_THEME_DIR . '/inc/mobile-bar.php';

/**
 * Theme setup.
 */
function jwellery_theme_setup() {
	load_theme_textdomain( 'jwellery-jewelry', JWELLERY_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 280,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary'   => __( 'Primary Menu', 'jwellery-jewelry' ),
			'footer'    => __( 'Footer Menu', 'jwellery-jewelry' ),
			'shop_sub'  => __( 'Shop Dropdown', 'jwellery-jewelry' ),
		)
	);
}
add_action( 'after_setup_theme', 'jwellery_theme_setup' );

/**
 * Enqueue scripts and styles.
 */
function jwellery_enqueue_assets() {
	wp_enqueue_style(
		'jwellery-google-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Lato:wght@400;700&display=swap',
		array(),
		null
	);
	$ver = jwellery_asset_version();
	wp_enqueue_style( 'jwellery-theme', JWELLERY_THEME_URI . '/assets/css/theme.css', array(), $ver );
	wp_enqueue_style( 'jwellery-buttons', JWELLERY_THEME_URI . '/assets/css/buttons.css', array( 'jwellery-theme' ), $ver );
	wp_enqueue_style( 'jwellery-responsive', JWELLERY_THEME_URI . '/assets/css/responsive.css', array( 'jwellery-buttons' ), $ver );
	wp_enqueue_style( 'jwellery-home-design', JWELLERY_THEME_URI . '/assets/css/home-design.css', array( 'jwellery-responsive' ), $ver );
	wp_enqueue_style( 'jwellery-icons', JWELLERY_THEME_URI . '/assets/css/icons.css', array( 'jwellery-theme' ), $ver );
	wp_enqueue_script( 'jwellery-theme', JWELLERY_THEME_URI . '/assets/js/theme.js', array( 'jquery' ), $ver, true );
}
add_action( 'wp_enqueue_scripts', 'jwellery_enqueue_assets' );

/**
 * Load button overrides after WooCommerce so centered text wins over WC defaults.
 */
function jwellery_enqueue_button_overrides() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$ver = jwellery_asset_version();
	wp_enqueue_style(
		'jwellery-buttons',
		JWELLERY_THEME_URI . '/assets/css/buttons.css',
		array( 'jwellery-theme', 'woocommerce-general', 'woocommerce-layout' ),
		$ver
	);
}
add_action( 'wp_enqueue_scripts', 'jwellery_enqueue_button_overrides', 100 );

/**
 * WooCommerce scripts + shop/product/checkout styles.
 */
function jwellery_enqueue_wc_cart_scripts() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	wp_enqueue_script( 'wc-add-to-cart' );
	wp_enqueue_style( 'jwellery-shop-experience', JWELLERY_THEME_URI . '/assets/css/shop-experience.css', array( 'jwellery-buttons' ), jwellery_asset_version() );
}
add_action( 'wp_enqueue_scripts', 'jwellery_enqueue_wc_cart_scripts', 25 );

/**
 * Register widget areas.
 */
function jwellery_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer Column', 'jwellery-jewelry' ),
			'id'            => 'footer-1',
			'before_widget' => '<div class="footer-widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="footer-widget-title">',
			'after_title'   => '</h4>',
		)
	);
}
add_action( 'widgets_init', 'jwellery_widgets_init' );

/**
 * Body classes.
 *
 * @param array $classes Classes.
 * @return array
 */
function jwellery_body_classes( $classes ) {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return $classes;
	}
	$wc_page = is_woocommerce()
		|| ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() );
	if ( $wc_page ) {
		$classes[] = 'jwellery-woocommerce';
	}
	return $classes;
}
add_filter( 'body_class', 'jwellery_body_classes' );



