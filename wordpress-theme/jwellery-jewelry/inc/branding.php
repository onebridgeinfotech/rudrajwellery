<?php
/**
 * Site logo — Rudra Jewellery bundled asset + Customizer override.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bundled logo file info for header or footer.
 *
 * @param string $context header|footer.
 * @return array{url: string, slug: string, width: int, height: int}|null
 */
function jwellery_bundled_logo_info( $context = 'header' ) {
	$landscape = array( 'slug' => 'rudra', 'width' => 360, 'height' => 164 );

	$candidates = 'footer' === $context
		? array(
			'rudra-logo-footer.png' => array_merge( $landscape, array( 'width' => 220, 'height' => 100 ) ),
			'rudra-logo.png'        => array_merge( $landscape, array( 'width' => 220, 'height' => 100 ) ),
			'kalpana-logo.png'      => array( 'slug' => 'kalpana', 'width' => 280, 'height' => 120 ),
			'logo.svg'              => array( 'slug' => 'default', 'width' => 240, 'height' => 56 ),
		)
		: array(
			'rudra-logo-header.png' => $landscape,
			'rudra-logo.png'        => $landscape,
			'kalpana-logo.png'      => array( 'slug' => 'kalpana', 'width' => 280, 'height' => 120 ),
			'logo.svg'              => array( 'slug' => 'default', 'width' => 240, 'height' => 56 ),
		);

	foreach ( $candidates as $file => $meta ) {
		$path = JWELLERY_THEME_DIR . '/assets/images/' . $file;
		if ( file_exists( $path ) ) {
			return array_merge(
				$meta,
				array( 'url' => JWELLERY_THEME_URI . '/assets/images/' . $file )
			);
		}
	}

	return null;
}

/**
 * Store currency symbol (encoding-safe; avoids mojibake for INR).
 *
 * @return string
 */
function jwellery_currency_symbol() {
	if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
		$symbol = get_woocommerce_currency_symbol();
		if ( is_string( $symbol ) && '' !== $symbol ) {
			return html_entity_decode( $symbol, ENT_QUOTES, 'UTF-8' );
		}
	}

	return html_entity_decode( '&#8377;', ENT_QUOTES, 'UTF-8' );
}

/**
 * Bundled default logo URL (header).
 *
 * @return string
 */
function jwellery_default_logo_url() {
	$info = jwellery_bundled_logo_info( 'header' );
	return $info ? $info['url'] : '';
}

/**
 * Default logo alt text.
 *
 * @return string
 */
function jwellery_default_logo_alt() {
	$brand = function_exists( 'jwellery_brand_name' ) ? jwellery_brand_name() : get_bloginfo( 'name' );
	if ( $brand && false === strpos( strtolower( $brand ), 'hostingersite' ) ) {
		return $brand;
	}
	return __( 'Rudra Jewellery by Kalpana Arikatla', 'jwellery-jewelry' );
}

/**
 * Logo image HTML (header or footer).
 *
 * @param string $context header|footer.
 * @return string
 */
function jwellery_logo_image_html( $context = 'header' ) {
	$alt  = jwellery_default_logo_alt();
	$info = jwellery_bundled_logo_info( $context );

	// Prefer bundled Rudra logo from PDF so header always fits correctly.
	if ( $info && false !== strpos( $info['url'], 'rudra-logo' ) ) {
		$class = 'jwellery-site-logo jwellery-site-logo--' . $info['slug'];
		if ( 'footer' === $context ) {
			$class .= ' jwellery-footer-logo';
		}

		return sprintf(
			'<img class="%s" src="%s" alt="%s" width="%d" height="%d" loading="%s" decoding="async" />',
			esc_attr( $class ),
			esc_url( $info['url'] ),
			esc_attr( $alt ),
			(int) $info['width'],
			(int) $info['height'],
			'header' === $context ? 'eager' : 'lazy'
		);
	}

	if ( has_custom_logo() ) {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		$class   = 'jwellery-site-logo jwellery-site-logo--custom custom-logo';
		if ( 'footer' === $context ) {
			$class .= ' jwellery-footer-logo';
		}
		ob_start();
		echo wp_get_attachment_image(
			$logo_id,
			'medium',
			false,
			array(
				'class'    => $class,
				'alt'      => esc_attr( $alt ),
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
		return ob_get_clean();
	}

	if ( ! $info ) {
		return '';
	}

	$class = 'jwellery-site-logo jwellery-site-logo--' . $info['slug'];
	if ( 'footer' === $context ) {
		$class .= ' jwellery-footer-logo';
	}

	return sprintf(
		'<img class="%s" src="%s" alt="%s" width="%d" height="%d" loading="%s" decoding="async" />',
		esc_attr( $class ),
		esc_url( $info['url'] ),
		esc_attr( $alt ),
		(int) $info['width'],
		(int) $info['height'],
		'header' === $context ? 'eager' : 'lazy'
	);
}

/**
 * Legacy store emails replaced during migrations.
 *
 * @param string $email Email address.
 * @return bool
 */
function jwellery_is_legacy_store_email( $email ) {
	$email = strtolower( trim( (string) $email ) );
	if ( ! $email ) {
		return false;
	}

	return in_array(
		$email,
		array(
			'udayach123@gmail.com',
			'your@email.com',
		),
		true
	);
}

/**
 * Primary store contact email.
 *
 * @return string
 */
function jwellery_store_email() {
	$email = (string) get_theme_mod( 'jwellery_email', 'kalpanayadav503@gmail.com' );
	if ( ! is_email( $email ) || jwellery_is_legacy_store_email( $email ) ) {
		if ( class_exists( 'JUS_Notifications' ) ) {
			return JUS_Notifications::store_email();
		}
		return 'kalpanayadav503@gmail.com';
	}

	return $email;
}

/**
 * Secondary public inbox (footer / enquiries).
 *
 * @return string
 */
function jwellery_info_email() {
	$email = (string) get_theme_mod( 'jwellery_info_email', 'info@rudrajewellery.co.in' );
	if ( ! is_email( $email ) ) {
		return 'info@rudrajewellery.co.in';
	}
	// Fix legacy typo saved in customizer.
	if ( 'info@rudrajwelelry.co.in' === strtolower( $email ) ) {
		return 'info@rudrajewellery.co.in';
	}
	return $email;
}

/**
 * Footer emails: info inbox first, personal store email second.
 *
 * @return array{info: string, personal: string}
 */
function jwellery_footer_contact_email_rows() {
	$info     = strtolower( trim( jwellery_info_email() ) );
	$personal = strtolower( trim( jwellery_store_email() ) );

	return array(
		'info'     => is_email( $info ) ? $info : 'info@rudrajewellery.co.in',
		'personal' => is_email( $personal ) ? $personal : 'kalpanayadav503@gmail.com',
	);
}

/**
 * Unique footer contact emails (info first, then personal).
 *
 * @return string[]
 */
function jwellery_footer_contact_emails() {
	$rows   = jwellery_footer_contact_email_rows();
	$emails = array();

	foreach ( array( $rows['info'], $rows['personal'] ) as $email ) {
		if ( is_email( $email ) && ! in_array( $email, $emails, true ) ) {
			$emails[] = $email;
		}
	}

	return $emails;
}

/**
 * Header logo.
 */
function jwellery_render_site_logo() {
	$home = home_url( '/' );
	$alt  = jwellery_default_logo_alt();
	$html = jwellery_logo_image_html( 'header' );

	if ( ! $html ) {
		printf(
			'<a class="site-title" href="%s">%s</a>',
			esc_url( $home ),
			esc_html( $alt )
		);
		return;
	}

	printf(
		'<a class="jwellery-logo-link" href="%s" rel="home" aria-label="%s">%s</a>',
		esc_url( $home ),
		esc_attr( $alt ),
		$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
	);
}

/**
 * Footer logo.
 */
function jwellery_render_footer_logo() {
	$home = home_url( '/' );
	$alt  = jwellery_default_logo_alt();
	$html = jwellery_logo_image_html( 'footer' );

	if ( ! $html ) {
		return;
	}

	printf(
		'<a class="jwellery-footer-logo-link" href="%s" rel="home" aria-label="%s">%s</a>',
		esc_url( $home ),
		esc_attr( $alt ),
		$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Theme favicon asset URL.
 *
 * @param string $filename File under assets/images/.
 * @return string
 */
function jwellery_theme_favicon_asset_url( $filename ) {
	return JWELLERY_THEME_URI . '/assets/images/' . ltrim( $filename, '/' );
}

/**
 * Stop WordPress Site Icon JPEG from overriding theme favicon.
 */
function jwellery_disable_wp_site_icon() {
	remove_action( 'wp_head', 'wp_site_icon', 99 );
}
add_action( 'init', 'jwellery_disable_wp_site_icon', 20 );

/**
 * Early favicon in document head (before wp_head cache/plugins).
 */
function jwellery_early_favicon_link() {
	$ver      = defined( 'JWELLERY_THEME_VERSION' ) ? JWELLERY_THEME_VERSION : '1';
	$root_ico = add_query_arg( 'v', $ver, home_url( '/favicon.ico' ) );
	$png_32   = add_query_arg( 'v', $ver, jwellery_theme_favicon_asset_url( 'favicon-32x32.png' ) );

	printf( '<link rel="icon" href="%s" type="image/x-icon" sizes="any" />' . "\n", esc_url( $root_ico ) );
	printf( '<link rel="shortcut icon" href="%s" type="image/x-icon" />' . "\n", esc_url( $root_ico ) );
	if ( file_exists( JWELLERY_THEME_DIR . '/assets/images/favicon-32x32.png' ) ) {
		printf(
			'<link rel="icon" href="%s" type="image/png" sizes="32x32" />' . "\n",
			esc_url( $png_32 )
		);
	}
}

/**
 * Output favicon tags (root ICO + PNG/SVG fallbacks).
 *
 * Browsers often request /favicon.ico before HTML; Hostinger CDN serves a static
 * file at the site root, so theme PHP cannot intercept that request reliably.
 */
function jwellery_render_favicon_tags() {
	$ver       = defined( 'JWELLERY_THEME_VERSION' ) ? JWELLERY_THEME_VERSION : '1';
	$root_ico  = add_query_arg( 'v', $ver, home_url( '/favicon.ico' ) );
	$root_apple = add_query_arg( 'v', $ver, home_url( '/apple-touch-icon.png' ) );
	$png_32    = add_query_arg( 'v', $ver, jwellery_theme_favicon_asset_url( 'favicon-32x32.png' ) );
	$png_192   = add_query_arg( 'v', $ver, jwellery_theme_favicon_asset_url( 'favicon-192x192.png' ) );
	$svg       = add_query_arg( 'v', $ver, jwellery_theme_favicon_asset_url( 'favicon.svg' ) );

	printf( '<link rel="icon" href="%s" sizes="any" />' . "\n", esc_url( $root_ico ) );
	printf( '<link rel="shortcut icon" href="%s" />' . "\n", esc_url( $root_ico ) );

	if ( file_exists( JWELLERY_THEME_DIR . '/assets/images/favicon-32x32.png' ) ) {
		printf(
			'<link rel="icon" href="%s" type="image/png" sizes="32x32" />' . "\n",
			esc_url( $png_32 )
		);
	}

	if ( file_exists( JWELLERY_THEME_DIR . '/assets/images/favicon.svg' ) ) {
		printf(
			'<link rel="icon" href="%s" type="image/svg+xml" sizes="any" />' . "\n",
			esc_url( $svg )
		);
	}

	if ( file_exists( JWELLERY_THEME_DIR . '/assets/images/favicon-192x192.png' ) ) {
		printf(
			'<link rel="icon" href="%s" type="image/png" sizes="192x192" />' . "\n",
			esc_url( $png_192 )
		);
	}

	printf(
		'<link rel="apple-touch-icon" href="%s" sizes="180x180" />' . "\n",
		esc_url( $root_apple )
	);
}
add_action( 'wp_head', 'jwellery_render_favicon_tags', -100 );

/**
 * Replace WordPress Site Icon JPEG tags (we output our own set above).
 *
 * @param array<int, string> $meta_tags Site icon link tags.
 * @return array<int, string>
 */
function jwellery_replace_site_icon_meta_tags( $meta_tags ) {
	return array();
}
add_filter( 'site_icon_meta_tags', 'jwellery_replace_site_icon_meta_tags', 100 );
