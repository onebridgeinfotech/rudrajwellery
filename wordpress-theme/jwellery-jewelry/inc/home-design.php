<?php
/**
 * Homepage section headers and design helpers.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Section header with optional eyebrow, subtitle, and CTA link.
 *
 * @param string $title     Section title.
 * @param array  $args      link, link_text, subtitle, eyebrow, center.
 */
function jwellery_section_header( $title, $args = array() ) {
	$defaults = array(
		'link'      => '',
		'link_text' => __( 'View all', 'jwellery-jewelry' ),
		'subtitle'  => '',
		'eyebrow'   => '',
		'center'    => false,
	);
	$args = wp_parse_args( $args, $defaults );

	$class = 'jwellery-section-head';
	if ( $args['center'] ) {
		$class .= ' jwellery-section-head--center';
	}
	?>
	<div class="<?php echo esc_attr( $class ); ?>" data-animate="head">
		<?php if ( $args['eyebrow'] ) : ?>
			<span class="jwellery-section-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></span>
		<?php endif; ?>
		<h2 class="section-title<?php echo $args['center'] ? ' section-title--center' : ''; ?>"><?php echo esc_html( $title ); ?></h2>
		<?php if ( $args['subtitle'] ) : ?>
			<p class="jwellery-section-desc"><?php echo esc_html( $args['subtitle'] ); ?></p>
		<?php endif; ?>
		<?php if ( $args['link'] ) : ?>
			<a class="jwellery-btn jwellery-btn-viewall section-link" href="<?php echo esc_url( $args['link'] ); ?>"><?php echo esc_html( $args['link_text'] ); ?></a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Sanitized section modifier class.
 *
 * @param string $title Section title.
 * @return string
 */
function jwellery_section_class( $title ) {
	return 'jwellery-home-section--' . sanitize_title( $title );
}
