<?php
/**
 * Store page content — About, Contact, policies (Rudra Jewellery).
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dynamic tokens for page templates.
 *
 * @return array<string, string>
 */
function jwellery_page_content_tokens() {
	$brand = function_exists( 'jwellery_brand_name' ) ? jwellery_brand_name() : get_bloginfo( 'name' );
	$email = function_exists( 'jwellery_store_email' ) ? jwellery_store_email() : 'kalpanayadav503@gmail.com';
	$info_email = function_exists( 'jwellery_info_email' ) ? jwellery_info_email() : 'info@rudrajewellery.co.in';
	$phone = trim( (string) get_theme_mod( 'jwellery_phone', '+91 7036837243' ) );
	$wa    = trim( (string) get_theme_mod( 'jwellery_whatsapp', '7730817950' ) );
	$addr  = trim( (string) get_theme_mod( 'jwellery_address', 'H no 7-7-11/8, New Sri Ram Nagar Colony, Peerzadiguda, Hyderabad - 500098' ) );

	if ( ! $phone ) {
		$phone = '+91 7036837243';
	}
	if ( ! $wa ) {
		$wa = '7730817950';
	}
	if ( ! $addr ) {
		$addr = 'H no 7-7-11/8, New Sri Ram Nagar Colony, Peerzadiguda, Hyderabad - 500098';
	}

	$shop_url    = function_exists( 'jwellery_get_shop_url' ) ? jwellery_get_shop_url() : home_url( '/shop/' );
	$contact_url = home_url( '/contact/' );
	$track_url   = home_url( '/track-order/' );
	$about_url   = home_url( '/about/' );
	$site_url    = home_url( '/' );

	if ( function_exists( 'jwellery_get_store_page_url' ) ) {
		$contact_url = jwellery_get_store_page_url( 'contact' );
		$track_url   = jwellery_get_store_page_url( 'track-order' );
		$about_url   = jwellery_get_store_page_url( 'about' );
	}

	return array(
		'brand'       => $brand,
		'email'       => $email,
		'info_email'  => $info_email,
		'phone'       => $phone,
		'phone_tel'   => preg_replace( '/\D+/', '', $phone ),
		'whatsapp'    => $wa,
		'wa_url'      => 'https://wa.me/91' . preg_replace( '/\D+/', '', $wa ),
		'address'     => $addr,
		'shop_url'    => $shop_url,
		'contact_url' => $contact_url,
		'track_url'   => $track_url,
		'about_url'   => $about_url,
		'site_url'    => $site_url,
		'year'        => gmdate( 'Y' ),
	);
}

/**
 * Default HTML content for a store page.
 *
 * @param string $key Page key.
 * @return string
 */
function jwellery_get_default_page_content( $key ) {
	$t = jwellery_page_content_tokens();

	switch ( $key ) {
		case 'about':
			return jwellery_build_about_page_content( $t );
		case 'contact':
			return jwellery_build_contact_page_content( $t );
		case 'track-order':
			return jwellery_build_track_order_page_content( $t );
		case 'privacy-policy':
			return jwellery_build_privacy_page_content( $t );
		case 'refund-policy':
			return jwellery_build_refund_page_content( $t );
		case 'shipping-policy':
			return jwellery_build_shipping_page_content( $t );
		case 'terms-of-service':
			return jwellery_build_terms_page_content( $t );
	}

	return '';
}

/**
 * About page — split layout, offer grid, CTA.
 *
 * @param array<string, string> $t Tokens.
 * @return string
 */
function jwellery_build_about_page_content( $t ) {
	$story_icon = function_exists( 'jwellery_page_section_icon_html' ) ? jwellery_page_section_icon_html( 'quality', 'jwellery-page-section-icon--lg' ) : '';

	$offers = '';
	if ( function_exists( 'jwellery_page_offer_card_html' ) ) {
		$offers  = jwellery_page_offer_card_html( 'quality', __( 'Ear Rings', 'jwellery-jewelry' ), __( 'Studs, jhumkas, ear chains & more', 'jwellery-jewelry' ) );
		$offers .= jwellery_page_offer_card_html( 'shop', __( 'Necklaces', 'jwellery-jewelry' ), __( 'Chandraharams, black beads, pendant sets', 'jwellery-jewelry' ) );
		$offers .= jwellery_page_offer_card_html( 'quality', __( 'Chockers', 'jwellery-jewelry' ), __( 'Antique and festive choker styles', 'jwellery-jewelry' ) );
		$offers .= jwellery_page_offer_card_html( 'cart', __( 'Bangles', 'jwellery-jewelry' ), __( 'Traditional and contemporary designs', 'jwellery-jewelry' ) );
		$offers .= jwellery_page_offer_card_html( 'shop', __( 'Long Harams', 'jwellery-jewelry' ), __( 'Grand pieces for weddings & celebrations', 'jwellery-jewelry' ) );
		$offers .= jwellery_page_offer_card_html( 'home', __( 'Collections', 'jwellery-jewelry' ), __( 'Handmade, Instagram exclusives & latest arrivals', 'jwellery-jewelry' ) );
	}

	$quality_icon = function_exists( 'jwellery_page_icon_html' ) ? jwellery_page_icon_html( 'quality', 28 ) : '';
	$truck_icon   = function_exists( 'jwellery_page_icon_html' ) ? jwellery_page_icon_html( 'truck', 28 ) : '';
	$support_icon = function_exists( 'jwellery_page_icon_html' ) ? jwellery_page_icon_html( 'support', 28 ) : '';

	return sprintf(
		'<div class="jwellery-page-sections jwellery-page--about">
<section class="jwellery-page-split jwellery-animate-item">
<div class="jwellery-page-split-content">
<p class="jwellery-page-eyebrow">%7$s</p>
<h2>%1$s</h2>
<p class="jwellery-page-lead">Welcome to <strong>%1$s</strong> — your destination for elegant artificial and fashion jewellery inspired by traditional Indian designs.</p>
<p>From everyday studs to statement necklaces, we bring timeless beauty, style, and affordability for every occasion.</p>
</div>
<div class="jwellery-page-split-visual jwellery-page-split-visual--icon">%2$s</div>
</section>
<section class="jwellery-page-offerings jwellery-animate-item">
<h3>%8$s</h3>
<div class="jwellery-page-offer-grid">%3$s</div>
</section>
<section class="jwellery-page-promise jwellery-animate-item">
<h3>%9$s</h3>
<div class="jwellery-page-promise-grid">
<article class="jwellery-page-promise-card">%4$s<h4>%10$s</h4><p>%11$s</p></article>
<article class="jwellery-page-promise-card">%5$s<h4>%12$s</h4><p>%13$s</p></article>
<article class="jwellery-page-promise-card">%6$s<h4>%14$s</h4><p>%15$s</p></article>
</div>
<p class="jwellery-page-note">%16$s</p>
</section>
<section class="jwellery-page-cta-band jwellery-animate-item">
<a class="jwellery-btn jwellery-btn-primary" href="%17$s">%18$s</a>
<a class="jwellery-btn jwellery-btn-outline" href="%19$s">%20$s</a>
</section>
</div>',
		esc_html( $t['brand'] ),
		$story_icon,
		$offers,
		$quality_icon,
		$truck_icon,
		$support_icon,
		esc_html__( 'Our story', 'jwellery-jewelry' ),
		esc_html__( 'What we offer', 'jwellery-jewelry' ),
		esc_html__( 'Our promise', 'jwellery-jewelry' ),
		esc_html__( 'Curated quality', 'jwellery-jewelry' ),
		esc_html__( 'Every piece is selected for comfort, finish, and value.', 'jwellery-jewelry' ),
		esc_html__( 'All-India shipping', 'jwellery-jewelry' ),
		esc_html__( 'Secure UPI checkout with doorstep delivery across India.', 'jwellery-jewelry' ),
		esc_html__( 'Dedicated support', 'jwellery-jewelry' ),
		esc_html__( 'WhatsApp assistance for orders, styling, and payment help.', 'jwellery-jewelry' ),
		sprintf(
			/* translators: 1: brand */
			esc_html__( 'Based in Hyderabad, Telangana — %1$s by Kalpana Arikatla.', 'jwellery-jewelry' ),
			esc_html( $t['brand'] )
		),
		esc_url( $t['contact_url'] ),
		esc_html__( 'Contact us', 'jwellery-jewelry' ),
		esc_url( $t['shop_url'] ),
		esc_html__( 'Shop jewellery', 'jwellery-jewelry' )
	);
}

/**
 * Contact page — visual + contact cards.
 *
 * @param array<string, string> $t Tokens.
 * @return string
 */
function jwellery_build_contact_page_content( $t ) {
	$intro_icon = function_exists( 'jwellery_page_section_icon_html' ) ? jwellery_page_section_icon_html( 'support', 'jwellery-page-section-icon--lg' ) : '';
	$phone_i    = function_exists( 'jwellery_page_icon_html' ) ? jwellery_page_icon_html( 'phone', 26 ) : '';
	$wa_i     = function_exists( 'jwellery_page_icon_html' ) ? jwellery_page_icon_html( 'whatsapp', 26 ) : '';
	$mail_i   = function_exists( 'jwellery_page_icon_html' ) ? jwellery_page_icon_html( 'email', 26 ) : '';
	$loc_i    = function_exists( 'jwellery_page_icon_html' ) ? jwellery_page_icon_html( 'location', 26 ) : '';

	return sprintf(
		'<div class="jwellery-page-sections jwellery-page--contact">
<div class="jwellery-contact-intro jwellery-contact-intro--icon jwellery-animate-item">
%1$s
<p class="jwellery-page-eyebrow">%2$s</p>
<h2>%3$s</h2>
<p class="jwellery-page-lead">%4$s</p>
</div>
<div class="jwellery-contact-cards jwellery-animate-item">
<a class="jwellery-contact-card" href="tel:%5$s"><span class="jwellery-contact-card-icon">%6$s</span><strong>%7$s</strong><span>%8$s</span></a>
<a class="jwellery-contact-card jwellery-contact-card--wa" href="%9$s" target="_blank" rel="noopener noreferrer"><span class="jwellery-contact-card-icon">%10$s</span><strong>%11$s</strong><span>+91 %12$s</span></a>
<a class="jwellery-contact-card" href="mailto:%13$s"><span class="jwellery-contact-card-icon">%14$s</span><strong>%15$s</strong><span>%13$s</span></a>
<div class="jwellery-contact-card jwellery-contact-card--static"><span class="jwellery-contact-card-icon">%16$s</span><strong>%17$s</strong><span>%18$s</span></div>
</div>
<div class="jwellery-page-info-row jwellery-animate-item">
<div class="jwellery-page-info-card"><h3>%19$s</h3><p>%20$s</p></div>
<div class="jwellery-page-info-card"><h3>%21$s</h3><ul class="jwellery-page-link-list"><li><a href="%22$s">%23$s</a></li><li><a href="%24$s">%25$s</a></li><li><a href="%26$s">%27$s</a></li></ul><p class="jwellery-page-tip">%28$s</p></div>
</div>
</div>',
		$intro_icon,
		esc_html__( 'Get in touch', 'jwellery-jewelry' ),
		esc_html__( 'Contact Us', 'jwellery-jewelry' ),
		esc_html__( 'We would love to hear from you! Reach out for orders, product questions, or custom requests.', 'jwellery-jewelry' ),
		esc_attr( $t['phone_tel'] ),
		$phone_i,
		esc_html__( 'Phone', 'jwellery-jewelry' ),
		esc_html( $t['phone'] ),
		esc_url( $t['wa_url'] ),
		$wa_i,
		esc_html__( 'WhatsApp', 'jwellery-jewelry' ),
		esc_html( $t['whatsapp'] ),
		esc_attr( $t['email'] ),
		$mail_i,
		esc_html__( 'Email', 'jwellery-jewelry' ),
		$loc_i,
		esc_html__( 'Visit us', 'jwellery-jewelry' ),
		esc_html( $t['address'] ),
		esc_html__( 'Store hours', 'jwellery-jewelry' ),
		esc_html__( 'Monday – Saturday, 10:00 AM – 7:00 PM IST', 'jwellery-jewelry' ),
		esc_html__( 'Order help', 'jwellery-jewelry' ),
		esc_url( $t['track_url'] ),
		esc_html__( 'Track your order', 'jwellery-jewelry' ),
		esc_url( $t['shop_url'] ),
		esc_html__( 'Shop all products', 'jwellery-jewelry' ),
		esc_url( $t['about_url'] ),
		esc_html__( 'About us', 'jwellery-jewelry' ),
		esc_html__( 'For payment issues, share your order number and UPI UTR on WhatsApp for faster support.', 'jwellery-jewelry' )
	);
}

/**
 * Track order page — steps + form card.
 *
 * @param array<string, string> $t Tokens.
 * @return string
 */
function jwellery_build_track_order_page_content( $t ) {
	$intro_icon = function_exists( 'jwellery_page_section_icon_html' ) ? jwellery_page_section_icon_html( 'truck', 'jwellery-page-section-icon--lg' ) : '';
	$steps      = '';
	if ( function_exists( 'jwellery_page_track_step_html' ) ) {
		$steps  = jwellery_page_track_step_html( '01', 'lock', __( 'UPI verified', 'jwellery-jewelry' ), __( 'Payment & UTR confirmed within 24 hours', 'jwellery-jewelry' ) );
		$steps .= jwellery_page_track_step_html( '02', 'truck', __( 'Packed & shipped', 'jwellery-jewelry' ), __( 'Courier pickup with tracking email', 'jwellery-jewelry' ) );
		$steps .= jwellery_page_track_step_html( '03', 'home', __( 'Delivered', 'jwellery-jewelry' ), __( '5–10 business days across India', 'jwellery-jewelry' ) );
	}

	return sprintf(
		'<div class="jwellery-page-sections jwellery-page--track">
<div class="jwellery-track-intro jwellery-animate-item">
%1$s
<div class="jwellery-track-copy">
<p class="jwellery-page-eyebrow">%2$s</p>
<h2>%3$s</h2>
<p>%4$s</p>
<p>%5$s</p>
<p>%6$s <a href="tel:%7$s">%8$s</a> %9$s <a href="%10$s" target="_blank" rel="noopener noreferrer">%11$s</a>.</p>
</div>
</div>
<div class="jwellery-track-steps jwellery-animate-item">%12$s</div>
<div class="jwellery-track-form-wrap jwellery-animate-item">[woocommerce_order_tracking]</div>
</div>',
		$intro_icon,
		esc_html__( 'Order status', 'jwellery-jewelry' ),
		esc_html__( 'Track Your Order', 'jwellery-jewelry' ),
		esc_html__( 'Enter your order number and the billing email used at checkout to see the latest status.', 'jwellery-jewelry' ),
		esc_html__( 'Orders are processed after UPI payment verification. You will receive email updates when your order ships.', 'jwellery-jewelry' ),
		esc_html__( 'Delivery across India usually takes 5–10 business days. Need help? Call', 'jwellery-jewelry' ),
		esc_attr( $t['phone_tel'] ),
		esc_html( $t['phone'] ),
		esc_html__( 'or', 'jwellery-jewelry' ),
		esc_url( $t['wa_url'] ),
		esc_html__( 'WhatsApp us', 'jwellery-jewelry' ),
		$steps
	);
}

/**
 * Privacy policy page.
 *
 * @param array<string, string> $t Tokens.
 * @return string
 */
function jwellery_build_privacy_page_content( $t ) {
	$intro_icon = function_exists( 'jwellery_page_section_icon_html' ) ? jwellery_page_section_icon_html( 'lock', 'jwellery-page-section-icon--lg' ) : '';

	$cards = '';
	if ( function_exists( 'jwellery_page_policy_card_html' ) ) {
		$cards .= jwellery_page_policy_card_html( __( 'Information we collect', 'jwellery-jewelry' ), '<ul><li>' . esc_html__( 'Name, email, phone, and shipping address when you register or place an order', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'UPI transaction reference (UTR) for payment verification', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Order history, cart activity, and account login details', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Messages via email, WhatsApp, or contact forms', 'jwellery-jewelry' ) . '</li></ul>', 'lock' );
		$cards .= jwellery_page_policy_card_html( __( 'How we use information', 'jwellery-jewelry' ), '<ul><li>' . esc_html__( 'Process orders, verify UPI payments, and ship products', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Send order confirmations and shipping updates', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Improve our website and customer experience', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Comply with legal and tax requirements in India', 'jwellery-jewelry' ) . '</li></ul>', 'quality' );
		$cards .= jwellery_page_policy_card_html( __( 'Sharing & security', 'jwellery-jewelry' ), '<p>' . esc_html__( 'We do not sell your personal data. Information is shared only with courier partners and service providers required to operate our store.', 'jwellery-jewelry' ) . '</p><p>' . esc_html__( 'We use HTTPS and secure hosting. Passwords are stored using WordPress standard encryption.', 'jwellery-jewelry' ) . '</p>', 'lock' );
		$cards .= jwellery_page_policy_card_html( __( 'Cookies & your rights', 'jwellery-jewelry' ), '<p>' . esc_html__( 'Our site uses cookies for cart, login sessions, and analytics. You can control cookies through your browser settings.', 'jwellery-jewelry' ) . '</p><p>' . sprintf( __( 'Contact us at <a href="mailto:%1$s">%1$s</a> to request access, correction, or deletion of your account data.', 'jwellery-jewelry' ), esc_attr( $t['email'] ) ) . '</p>', 'support' );
	}

	return sprintf(
		'<div class="jwellery-page-sections jwellery-page--policy">
<div class="jwellery-policy-intro jwellery-policy-intro--icon jwellery-animate-item">
%1$s
<p class="jwellery-page-meta">%2$s</p>
<p><strong>%3$s</strong> %4$s <a href="%5$s">%5$s</a>. %6$s</p>
</div>
<div class="jwellery-page-card-grid">%7$s</div>
<div class="jwellery-page-contact-strip jwellery-animate-item"><p>%8$s <a href="mailto:%9$s">%9$s</a> · <a href="tel:%10$s">%11$s</a> · %12$s</p></div>
</div>',
		$intro_icon,
		sprintf( esc_html__( 'Last updated: June %s', 'jwellery-jewelry' ), esc_html( $t['year'] ) ),
		esc_html( $t['brand'] ),
		esc_html__( '("we", "our") operates', 'jwellery-jewelry' ),
		esc_url( $t['site_url'] ),
		esc_html__( 'This policy explains how we collect and use your information when you shop with us.', 'jwellery-jewelry' ),
		$cards,
		esc_html__( 'Questions?', 'jwellery-jewelry' ),
		esc_attr( $t['email'] ),
		esc_attr( $t['phone_tel'] ),
		esc_html( $t['phone'] ),
		esc_html( $t['address'] )
	);
}

/**
 * Refund policy page.
 *
 * @param array<string, string> $t Tokens.
 * @return string
 */
function jwellery_build_refund_page_content( $t ) {
	$intro_icon = function_exists( 'jwellery_page_section_icon_html' ) ? jwellery_page_section_icon_html( 'cart', 'jwellery-page-section-icon--lg' ) : '';

	return sprintf(
		'<div class="jwellery-page-sections jwellery-page--policy jwellery-page--refund">
<div class="jwellery-policy-intro jwellery-policy-intro--icon jwellery-animate-item">
%1$s
<p class="jwellery-page-meta">%2$s</p>
<p>%3$s <strong>%4$s</strong> %5$s</p>
</div>
<div class="jwellery-page-card-grid">
%6$s
%7$s
%8$s
%9$s
</div>
<div class="jwellery-page-cta-band jwellery-animate-item">
<a class="jwellery-btn jwellery-btn-primary" href="%10$s" target="_blank" rel="noopener noreferrer">%11$s</a>
<a class="jwellery-btn jwellery-btn-outline" href="mailto:%12$s">%13$s</a>
</div>
</div>',
		$intro_icon,
		sprintf( esc_html__( 'Last updated: June %s', 'jwellery-jewelry' ), esc_html( $t['year'] ) ),
		esc_html__( 'At', 'jwellery-jewelry' ),
		esc_html( $t['brand'] ),
		esc_html__( 'we want you to love your purchase. Please read this policy before ordering.', 'jwellery-jewelry' ),
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Imitation jewellery', 'jwellery-jewelry' ), '<p>' . esc_html__( 'All products are fashion/imitation jewellery unless stated otherwise. Slight variation in colour or finish may occur and is not considered a defect.', 'jwellery-jewelry' ) . '</p>', 'quality' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Earrings — hygiene policy', 'jwellery-jewelry' ), '<p>' . esc_html__( 'Due to hygiene and safety, earrings cannot be returned or exchanged unless you received the wrong item or a damaged piece.', 'jwellery-jewelry' ) . '</p>', 'lock' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Eligible returns', 'jwellery-jewelry' ), '<ul><li>' . esc_html__( 'Wrong item shipped', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Damaged on arrival — report within 48 hours with photos', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Missing items from your order', 'jwellery-jewelry' ) . '</li></ul>', 'quality' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'UPI refunds & cancellations', 'jwellery-jewelry' ), '<p>' . esc_html__( 'Orders may be cancelled before payment verification. Approved refunds are processed to the original UPI account within 7–10 business days.', 'jwellery-jewelry' ) . '</p><p>' . sprintf( __( 'Email <a href="mailto:%1$s">%1$s</a> or WhatsApp within 48 hours of delivery.', 'jwellery-jewelry' ), esc_attr( $t['email'] ) ) . '</p>', 'support' ) : '',
		esc_url( $t['wa_url'] ),
		esc_html__( 'Request on WhatsApp', 'jwellery-jewelry' ),
		esc_attr( $t['email'] ),
		esc_html__( 'Email support', 'jwellery-jewelry' )
	);
}

/**
 * Shipping policy page.
 *
 * @param array<string, string> $t Tokens.
 * @return string
 */
function jwellery_build_shipping_page_content( $t ) {
	$intro_icon = function_exists( 'jwellery_page_section_icon_html' ) ? jwellery_page_section_icon_html( 'truck', 'jwellery-page-section-icon--lg' ) : '';

	return sprintf(
		'<div class="jwellery-page-sections jwellery-page--policy jwellery-page--shipping">
<div class="jwellery-policy-intro jwellery-policy-intro--icon jwellery-animate-item">
%1$s
<p class="jwellery-page-meta">%2$s</p>
<p>%3$s</p>
</div>
<div class="jwellery-page-card-grid">
%4$s
%5$s
%6$s
%7$s
</div>
<div class="jwellery-page-contact-strip jwellery-animate-item"><p>%8$s <a href="tel:%9$s">%10$s</a> · <a href="%11$s">%12$s</a> · <a href="%13$s">%14$s</a></p></div>
</div>',
		$intro_icon,
		sprintf( esc_html__( 'Last updated: June %s', 'jwellery-jewelry' ), esc_html( $t['year'] ) ),
		esc_html__( 'We ship across India with secure packaging and reliable courier partners.', 'jwellery-jewelry' ),
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Processing time', 'jwellery-jewelry' ), '<p>' . esc_html__( 'Orders are processed after your UPI payment and UTR are verified (usually within 24 hours on working days).', 'jwellery-jewelry' ) . '</p>', 'lock' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Delivery timeline', 'jwellery-jewelry' ), '<ul><li><strong>' . esc_html__( '5–10 business days', 'jwellery-jewelry' ) . '</strong> ' . esc_html__( 'after dispatch', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Free shipping on all orders across India', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Festival seasons may cause occasional delays', 'jwellery-jewelry' ) . '</li></ul>', 'truck' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Tracking', 'jwellery-jewelry' ), '<p>' . sprintf( __( 'Tracking details are emailed when available. Use our <a href="%1$s">Track Order</a> page with your order ID and billing email.', 'jwellery-jewelry' ), esc_url( $t['track_url'] ) ) . '</p>', 'truck' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Undelivered / RTO', 'jwellery-jewelry' ), '<p>' . esc_html__( 'If a parcel is returned due to an incorrect address or failed delivery attempts, re-shipping charges may apply.', 'jwellery-jewelry' ) . '</p>', 'support' ) : '',
		esc_html__( 'Questions?', 'jwellery-jewelry' ),
		esc_attr( $t['phone_tel'] ),
		esc_html( $t['phone'] ),
		esc_url( $t['contact_url'] ),
		esc_html__( 'Contact us', 'jwellery-jewelry' ),
		esc_url( $t['track_url'] ),
		esc_html__( 'Track order', 'jwellery-jewelry' )
	);
}

/**
 * Terms of service page.
 *
 * @param array<string, string> $t Tokens.
 * @return string
 */
function jwellery_build_terms_page_content( $t ) {
	$intro_icon = function_exists( 'jwellery_page_section_icon_html' ) ? jwellery_page_section_icon_html( 'lock', 'jwellery-page-section-icon--lg' ) : '';

	return sprintf(
		'<div class="jwellery-page-sections jwellery-page--policy jwellery-page--terms">
<div class="jwellery-policy-intro jwellery-policy-intro--icon jwellery-animate-item">
%1$s
<p class="jwellery-page-meta">%2$s</p>
<p>%3$s <a href="%4$s">%4$s</a> %5$s</p>
</div>
<div class="jwellery-page-card-grid">
%6$s
%7$s
%8$s
%9$s
</div>
<div class="jwellery-page-contact-strip jwellery-animate-item"><p><a href="mailto:%10$s">%10$s</a> · <a href="tel:%11$s">%12$s</a></p></div>
</div>',
		$intro_icon,
		sprintf( esc_html__( 'Last updated: June %s', 'jwellery-jewelry' ), esc_html( $t['year'] ) ),
		esc_html__( 'By using', 'jwellery-jewelry' ),
		esc_url( $t['site_url'] ),
		esc_html__( 'you agree to these terms. Please read them carefully before placing an order.', 'jwellery-jewelry' ),
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Products', 'jwellery-jewelry' ), '<p>' . sprintf( esc_html__( 'All items sold by %s are imitation / fashion jewellery unless explicitly stated. Product images are representative; minor variation may occur.', 'jwellery-jewelry' ), esc_html( $t['brand'] ) ) . '</p>', 'quality' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Orders & UPI payment', 'jwellery-jewelry' ), '<ul><li>' . esc_html__( 'Payment is accepted via UPI only', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Orders confirmed after UPI payment and UTR verification', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'False or duplicate UTR may result in cancellation', 'jwellery-jewelry' ) . '</li><li>' . esc_html__( 'Prices in INR including applicable taxes at checkout', 'jwellery-jewelry' ) . '</li></ul>', 'lock' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Accounts & intellectual property', 'jwellery-jewelry' ), '<p>' . esc_html__( 'Provide accurate registration and shipping details. You are responsible for keeping your password secure.', 'jwellery-jewelry' ) . '</p><p>' . sprintf( esc_html__( 'Website content, logos, and images belong to %s and may not be copied without permission.', 'jwellery-jewelry' ), esc_html( $t['brand'] ) ) . '</p>', 'quality' ) : '',
		function_exists( 'jwellery_page_policy_card_html' ) ? jwellery_page_policy_card_html( __( 'Liability & governing law', 'jwellery-jewelry' ), '<p>' . esc_html__( 'We are not liable for indirect damages from site use, bank payment delays, or courier delays beyond our reasonable control.', 'jwellery-jewelry' ) . '</p><p>' . esc_html__( 'These terms are governed by the laws of India. Disputes are subject to courts in Hyderabad, Telangana.', 'jwellery-jewelry' ) . '</p>', 'support' ) : '',
		esc_attr( $t['email'] ),
		esc_attr( $t['phone_tel'] ),
		esc_html( $t['phone'] )
	);
}

/**
 * Title aliases when locating pages created with different names.
 *
 * @return array<string, string[]>
 */
function jwellery_store_page_title_aliases() {
	return array(
		'about'            => array( 'About', 'About Us' ),
		'contact'          => array( 'Contact', 'Contact Us' ),
		'track-order'      => array( 'Track Order', 'Track Your Order' ),
		'privacy-policy'   => array( 'Privacy Policy' ),
		'refund-policy'    => array( 'Refund Policy', 'Cancellation & Refund' ),
		'shipping-policy'  => array( 'Shipping Policy', 'Shipping & Delivery' ),
		'terms-of-service' => array( 'Terms of Service' ),
	);
}

/**
 * Find a store page by key (option ID, slug, or title).
 *
 * @param string $key Page key.
 * @return WP_Post|null
 */
function jwellery_find_store_page( $key ) {
	$pages = jwellery_store_content_pages();
	if ( ! isset( $pages[ $key ] ) ) {
		return null;
	}

	$slug = $pages[ $key ];
	$id   = (int) get_option( 'jwellery_page_' . $key );

	if ( $id ) {
		$page = get_post( $id );
		if ( $page instanceof WP_Post && 'page' === $page->post_type && 'publish' === $page->post_status ) {
			return $page;
		}
	}

	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		update_option( 'jwellery_page_' . $key, $page->ID );
		return $page;
	}

	$by_slug = get_posts(
		array(
			'post_type'              => 'page',
			'name'                   => $slug,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	if ( ! empty( $by_slug[0] ) ) {
		update_option( 'jwellery_page_' . $key, $by_slug[0]->ID );
		return $by_slug[0];
	}

	$aliases = jwellery_store_page_title_aliases();
	if ( isset( $aliases[ $key ] ) ) {
		foreach ( $aliases[ $key ] as $title ) {
			$found = get_page_by_title( $title, OBJECT, 'page' );
			if ( $found instanceof WP_Post && 'publish' === $found->post_status ) {
				update_option( 'jwellery_page_' . $key, $found->ID );
				return $found;
			}
		}
	}

	return null;
}

/**
 * Permalink for a store page (creates slug fallback if page missing).
 *
 * @param string $key Page key.
 * @return string
 */
function jwellery_get_store_page_url( $key ) {
	$page = jwellery_find_store_page( $key );
	if ( $page ) {
		$url = get_permalink( $page );
		if ( $url ) {
			return $url;
		}
	}

	$pages = jwellery_store_content_pages();
	$slug  = isset( $pages[ $key ] ) ? $pages[ $key ] : $key;
	return home_url( '/' . $slug . '/' );
}

/**
 * Page slugs that receive themed content.
 *
 * @return array<string, string> key => slug
 */
function jwellery_store_content_pages() {
	return array(
		'about'            => 'about',
		'contact'          => 'contact',
		'track-order'      => 'track-order',
		'privacy-policy'   => 'privacy-policy',
		'refund-policy'    => 'refund-policy',
		'shipping-policy'  => 'shipping-policy',
		'terms-of-service' => 'terms-of-service',
	);
}

/**
 * Create or update store pages with relevant content.
 *
 * @param bool $update_existing Overwrite content on existing pages.
 * @return int Pages touched.
 */
function jwellery_sync_store_page_content( $update_existing = true ) {
	$count = 0;

	foreach ( jwellery_store_content_pages() as $key => $slug ) {
		$content = jwellery_get_default_page_content( $key );
		if ( ! $content ) {
			continue;
		}

		$titles = array(
			'about'            => 'About',
			'contact'          => 'Contact',
			'track-order'      => 'Track Order',
			'privacy-policy'   => 'Privacy Policy',
			'refund-policy'    => 'Refund Policy',
			'shipping-policy'  => 'Shipping Policy',
			'terms-of-service' => 'Terms of Service',
		);

		$existing = jwellery_find_store_page( $key );
		if ( $existing ) {
			update_option( 'jwellery_page_' . $key, $existing->ID );
			if ( $update_existing || jwellery_should_refresh_page_content( $existing, $key ) ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $content,
						'post_name'    => $slug,
					)
				);
				update_post_meta( $existing->ID, '_jwellery_page_content_ver', JWELLERY_THEME_VERSION );
				++$count;
			}
			continue;
		}

		$id = wp_insert_post(
			array(
				'post_title'   => isset( $titles[ $key ] ) ? $titles[ $key ] : ucfirst( $key ),
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'jwellery_page_' . $key, $id );
			++$count;
		}
	}

	update_option( 'jwellery_pages_content_ver', JWELLERY_THEME_VERSION );
	if ( function_exists( 'jwellery_mark_pages_content_version' ) ) {
		jwellery_mark_pages_content_version();
	}

	return $count;
}

/**
 * Whether an existing page still has placeholder content worth replacing.
 *
 * @param WP_Post     $page Page object.
 * @param string|null $key  Optional page key.
 * @return bool
 */
function jwellery_should_refresh_page_content( $page, $key = null ) {
	if ( ! $page instanceof WP_Post ) {
		return false;
	}

	$content = (string) $page->post_content;
	$plain   = wp_strip_all_tags( $content );

	$placeholders = array(
		'YOUR_STORE_NAME',
		'your@email.com',
		'udayach123@gmail.com',
		'info@rudrajwelelry.co.in',
		'XXXXXXXXXX',
		'yourdomain.com',
		'yourhandle',
		'Add a Contact Form 7',
		'Welcome to our imitation',
		'imitation jewelry store',
		'Sample Page',
	);
	foreach ( $placeholders as $needle ) {
		if ( false !== stripos( $content, $needle ) ) {
			return true;
		}
	}

	if ( in_array( $key, array( 'about', 'contact' ), true ) ) {
		if ( strlen( $plain ) < 280 ) {
			return true;
		}
		if ( 'about' === $key && false === stripos( $content, '<h2>About' ) ) {
			return true;
		}
		if ( 'contact' === $key && false === stripos( $content, '<h2>Contact' ) ) {
			return true;
		}
	}

	if ( false === stripos( $content, 'jwellery-page-sections' ) ) {
		return true;
	}

	if ( false !== stripos( $content, 'jwellery-page-img' )
		|| false !== stripos( $content, 'jwellery-contact-hero-img' )
		|| false !== stripos( $content, 'jwellery-policy-side-img' )
		|| false !== stripos( $content, 'jwellery-page-offer-media' )
		|| false !== stripos( $content, 'jwellery-page-scroll-hint' )
		|| false !== stripos( $content, 'data-auto-scroll' ) ) {
		return true;
	}

	$version = get_post_meta( $page->ID, '_jwellery_page_content_ver', true );
	return JWELLERY_THEME_VERSION !== $version;
}

/**
 * Mark pages after a full content sync.
 */
function jwellery_mark_pages_content_version() {
	foreach ( jwellery_store_content_pages() as $key => $slug ) {
		$page = jwellery_find_store_page( $key );
		if ( $page ) {
			update_post_meta( $page->ID, '_jwellery_page_content_ver', JWELLERY_THEME_VERSION );
		}
	}
}

/**
 * Sync pages + repair header menu after theme update (runs once per version).
 *
 * @param bool $force Run even if already done for this version.
 */
function jwellery_maintain_store( $force = false ) {
	$done_ver = (string) get_option( 'jwellery_store_maintain_ver', '' );
	if ( ! $force && $done_ver === JWELLERY_THEME_VERSION ) {
		return;
	}

	jwellery_sync_store_page_content( true );

	if ( function_exists( 'jwellery_apply_store_config' ) ) {
		jwellery_apply_store_config();
	} elseif ( function_exists( 'jwellery_ensure_classic_checkout_page' ) ) {
		jwellery_ensure_classic_checkout_page();
	}

	if ( function_exists( 'jwellery_repair_primary_menu_links' ) ) {
		jwellery_repair_primary_menu_links();
	}

	flush_rewrite_rules( false );
	update_option( 'jwellery_store_maintain_ver', JWELLERY_THEME_VERSION );
}

/**
 * Run maintain_store in wp-admin only (not on REST product editor requests).
 */
function jwellery_maintain_store_on_admin() {
	if ( function_exists( 'jwellery_should_skip_heavy_admin_work' ) && jwellery_should_skip_heavy_admin_work() ) {
		return;
	}
	if ( get_transient( 'jwellery_maintain_store_lock' ) ) {
		return;
	}
	set_transient( 'jwellery_maintain_store_lock', 1, 30 );
	jwellery_maintain_store( false );
	delete_transient( 'jwellery_maintain_store_lock' );
}
add_action( 'admin_init', 'jwellery_maintain_store_on_admin', 20 );
