<?php
/**
 * Social media icons — always visible in footer (SVG).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * SVG icon by network key.
 *
 * @param string $network Network id.
 * @return string
 */
function jwellery_social_svg( $network ) {
	$icons = array(
		'instagram' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2zm-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6A3.6 3.6 0 0 0 16.4 4H7.6zm9.65 2.75a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>',
		'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.8-1.6 1.5V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12z"/></svg>',
		'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18 5 12 5 12 5s-6 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C6 19 12 19 12 19s6 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8 26 26 0 0 0 .4-4.8 26 26 0 0 0-.4-4.8zM10 15.5V8.5l5.5 3.5L10 15.5z"/></svg>',
		'whatsapp'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.39-4.19-1.14l-.3-.18-3.12.82.83-3.04-.2-.31a8.227 8.227 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24M8.53 11.05l-.01.01a.77.77 0 0 0-.19.47c0 .08.02.16.05.23.06.16.18.38.38.65.34.42.95 1.17 2.08 1.88 1.31.82 2.15 1.03 2.58 1.14.24.06.42.06.57-.04.15-.09.56-.51.71-.71.15-.2.3-.17.5-.1.21.07 1.3.61 1.52.72.22.12.37.17.42.27.06.09.06.54-.12.98-.19.43-.88 1.06-1.27 1.13-.31.06-.72.09-1.16-.1-.43-.18-2.79-1.09-3.96-1.95-1.36-.98-2.22-2.2-2.48-2.57-.26-.37-.02-.57.19-.76.19-.18.43-.48.64-.72.22-.24.29-.41.43-.68.14-.27.07-.51-.04-.72-.1-.21-.98-2.36-1.34-3.23-.35-.87-.71-.75-.98-.76"/></svg>',
	);
	return isset( $icons[ $network ] ) ? $icons[ $network ] : '';
}

/**
 * Get social network URLs.
 *
 * @return array<string, string>
 */
function jwellery_get_social_urls() {
	$urls = array(
		'instagram' => (string) get_theme_mod( 'jwellery_instagram', '' ),
		'facebook'  => (string) get_theme_mod( 'jwellery_facebook', '' ),
		'youtube'   => (string) get_theme_mod( 'jwellery_youtube', '' ),
	);
	if ( function_exists( 'jwellery_whatsapp_url' ) ) {
		$urls['whatsapp'] = jwellery_whatsapp_url();
	}
	return $urls;
}

/**
 * Output social icon row (footer / header).
 *
 * @param string $context footer|header.
 */
function jwellery_social_links( $context = 'footer' ) {
	$urls      = jwellery_get_social_urls();
	$order     = array( 'instagram', 'facebook', 'youtube', 'whatsapp' );
	$labels    = array(
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'youtube'   => 'YouTube',
		'whatsapp'  => 'WhatsApp',
	);
	$compact   = 'footer-compact' === $context;
	$newsletter = 'footer-newsletter' === $context;
	$has_link  = false;

	if ( $newsletter ) {
		$order = array( 'instagram', 'facebook', 'youtube' );
	}

	echo '<div class="jwellery-social-icons jwellery-social-icons--' . esc_attr( $context ) . '">';

	foreach ( $order as $key ) {
		$url   = isset( $urls[ $key ] ) ? $urls[ $key ] : '';
		$label = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
		$svg   = jwellery_social_svg( $key );
		if ( ! $svg ) {
			continue;
		}
		if ( $compact && ! $url ) {
			continue;
		}
		if ( $url ) {
			$has_link = true;
			printf(
				'<a href="%s" class="jwellery-social-btn jwellery-social-btn--%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s<span class="jwellery-social-label">%s</span></a>',
				esc_url( $url ),
				esc_attr( $key ),
				esc_attr( $label ),
				$svg, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $label )
			);
		} else {
			printf(
				'<span class="jwellery-social-btn jwellery-social-btn--%s is-unlinked" title="%s">%s<span class="jwellery-social-label">%s</span></span>',
				esc_attr( $key ),
				esc_attr__( 'Add URL in Appearance → Customize → Social Links', 'jwellery-jewelry' ),
				$svg, // phpcs:ignore
				esc_html( $label )
			);
		}
	}

	echo '</div>';
}

/**
 * Social icons below newsletter subscribe (Instagram, Facebook, YouTube).
 */
function jwellery_footer_social_inline() {
	$urls    = jwellery_get_social_urls();
	$has_any = ! empty( $urls['instagram'] ) || ! empty( $urls['facebook'] ) || ! empty( $urls['youtube'] );
	if ( ! $has_any ) {
		return;
	}
	?>
	<div class="jwellery-footer-social-inline">
		<span class="jwellery-footer-social-label"><?php esc_html_e( 'Follow us', 'jwellery-jewelry' ); ?></span>
		<?php jwellery_social_links( 'footer-compact' ); ?>
	</div>
	<?php
}
