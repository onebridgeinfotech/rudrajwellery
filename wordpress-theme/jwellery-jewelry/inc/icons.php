<?php
/**
 * SVG icon library — header, trust strip, mobile bar, WhatsApp.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline SVG icon.
 *
 * @param string $name Icon key.
 * @param int    $size Pixel size.
 * @return string
 */
function jwellery_icon_svg( $name, $size = 22 ) {
	$stroke = 'fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"';
	$icons  = array(
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/>',
		'cart'     => '<path d="M8 8V6a4 4 0 0 1 8 0v2"/><path d="M5 8h14l-1.2 10H6.2L5 8z"/>',
		'heart'    => '<path d="M12 20.5s-7.2-4.6-9.2-8.8C1.2 8.2 3.4 5 6.6 5c1.7 0 3.2.9 4 2.2.8-1.3 2.3-2.2 4-2.2 3.2 0 5.4 3.2 3.8 6.7-2 4.2-9.2 8.8-9.2 8.8z"/>',
		'home'     => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z"/>',
		'shop'     => '<path d="M4 8h16l-1.2 12H5.2L4 8z"/><path d="M9 8a3 3 0 0 1 6 0"/>',
		'truck'    => '<path d="M3 7h11v8H3z"/><path d="M14 10h4l2 3v2h-6v-5z"/><circle cx="7" cy="17" r="1.5"/><circle cx="17" cy="17" r="1.5"/>',
		'lock'     => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
		'support'  => '<path d="M4 6a8 8 0 0 1 16 0v5a3 3 0 0 1-3 3h-1l-2 3-2-3h-1a3 3 0 0 1-3-3V6z"/>',
		'quality'  => '<path d="M12 3l2.2 4.5 5 .7-3.6 3.5.9 5-4.5-2.4-4.5 2.4.9-5L4.8 8.2l5-.7L12 3z"/>',
		'whatsapp' => '<path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.39-4.19-1.14l-.3-.18-3.12.82.83-3.04-.2-.31a8.227 8.227 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24M8.53 11.05l-.01.01a.77.77 0 0 0-.19.47c0 .08.02.16.05.23.06.16.18.38.38.65.34.42.95 1.17 2.08 1.88 1.31.82 2.15 1.03 2.58 1.14.24.06.42.06.57-.04.15-.09.56-.51.71-.71.15-.2.3-.17.5-.1.21.07 1.3.61 1.52.72.22.12.37.17.42.27.06.09.06.54-.12.98-.19.43-.88 1.06-1.27 1.13-.31.06-.72.09-1.16-.1-.43-.18-2.79-1.09-3.96-1.95-1.36-.98-2.22-2.2-2.48-2.57-.26-.37-.02-.57.19-.76.19-.18.43-.48.64-.72.22-.24.29-.41.43-.68.14-.27.07-.51-.04-.72-.1-.21-.98-2.36-1.34-3.23-.35-.87-.71-.75-.98-.76"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'chevron-up'   => '<path d="M6 14l6-6 6 6"/>',
		'chevron-down' => '<path d="M6 10l6 6 6-6"/>',
		'phone'    => '<path d="M6.5 4h3l1.5 4-2 1.2a11 11 0 0 0 5.3 5.3L17.5 13l4 1.5v3a1.5 1.5 0 0 1-1.5 1.5C9.2 19 5 14.8 5 8.5A1.5 1.5 0 0 1 6.5 4z"/>',
		'location' => '<path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.5"/>',
		'email'    => '<rect x="4" y="6" width="16" height="12" rx="2"/><path d="M4 8l8 5 8-5"/>',
		'orders'   => '<path d="M5 7h14l-1 12H6L5 7z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/><path d="M9 12h6M9 16h4"/>',
		'download' => '<path d="M12 4v10"/><path d="M8 11l4 4 4-4"/><path d="M5 20h14"/>',
		'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'logout'   => '<path d="M10 6H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h4"/><path d="M14 12H8M18 8l4 4-4 4"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$inner = $icons[ $name ];
	$attrs = in_array( $name, array( 'whatsapp', 'heart' ), true ) ? 'fill="currentColor" stroke="none"' : $stroke;

	return sprintf(
		'<svg class="jwellery-icon jwellery-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" %3$s aria-hidden="true">%4$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$attrs,
		$inner
	);
}
