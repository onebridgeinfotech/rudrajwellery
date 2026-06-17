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
