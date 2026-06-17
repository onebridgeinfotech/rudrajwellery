<?php
/**
 * Theme Customizer â€” brand, social, hero, WhatsApp, announcements.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize hero image (attachment ID or URL).
 *
 * @param mixed $value Theme mod value.
 * @return int|string
 */
function jwellery_sanitize_hero_image( $value ) {
	if ( is_numeric( $value ) ) {
		return absint( $value );
	}
	return esc_url_raw( (string) $value );
}

/**
 * Register customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function jwellery_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'jwellery_store_ui',
		array(
			'title'    => __( 'Store UI', 'jwellery-jewelry' ),
			'priority' => 120,
		)
	);

	$wp_customize->add_setting(
		'jwellery_brand_name',
		array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
	$wp_customize->add_control(
		'jwellery_brand_name',
		array(
			'label'   => __( 'Store brand name', 'jwellery-jewelry' ),
			'section' => 'jwellery_store_ui',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'jwellery_phone',
		array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '+91 7036837243',
		)
	);
	$wp_customize->add_control(
		'jwellery_phone',
		array(
			'label'   => __( 'Store phone (footer)', 'jwellery-jewelry' ),
			'section' => 'jwellery_store_ui',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'jwellery_email',
		array(
			'sanitize_callback' => 'sanitize_email',
			'default'           => 'kalpanayadav503@gmail.com',
		)
	);
	$wp_customize->add_control(
		'jwellery_email',
		array(
			'label'   => __( 'Store email (footer)', 'jwellery-jewelry' ),
			'section' => 'jwellery_store_ui',
			'type'    => 'email',
		)
	);

	$wp_customize->add_setting(
		'jwellery_address',
		array(
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => 'H no 7-7-11/8, New Sri Ram Nagar Colony, Peerzadiguda, Hyderabad - 500098',
		)
	);
	$wp_customize->add_control(
		'jwellery_address',
		array(
			'label'   => __( 'Store address (footer)', 'jwellery-jewelry' ),
			'section' => 'jwellery_store_ui',
			'type'    => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'jwellery_footer_about',
		array(
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		)
	);
	$wp_customize->add_control(
		'jwellery_footer_about',
		array(
			'label'   => __( 'Footer about text', 'jwellery-jewelry' ),
			'section' => 'jwellery_store_ui',
			'type'    => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'jwellery_whatsapp',
		array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '7730817950',
		)
	);
	$wp_customize->add_control(
		'jwellery_whatsapp',
		array(
			'label'       => __( 'WhatsApp number (10 digits, India)', 'jwellery-jewelry' ),
			'description' => __( 'Enables floating WhatsApp button and product share.', 'jwellery-jewelry' ),
			'section'     => 'jwellery_store_ui',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'jwellery_announcements',
		array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
	$wp_customize->add_control(
		'jwellery_announcements',
		array(
			'label'       => __( 'Announcement bar messages', 'jwellery-jewelry' ),
			'description' => __( 'Separate with | (pipe). Example: Free Shipping All Over India | New Arrivals', 'jwellery-jewelry' ),
			'section'     => 'jwellery_store_ui',
			'type'        => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'jwellery_free_shipping_min',
		array(
			'sanitize_callback' => 'absint',
			'default'           => 999,
		)
	);
	$wp_customize->add_control(
		'jwellery_free_shipping_min',
		array(
			'label'       => __( 'Free shipping minimum (â‚¹)', 'jwellery-jewelry' ),
			'description' => __( 'Used in the mini cart progress bar.', 'jwellery-jewelry' ),
			'section'     => 'jwellery_store_ui',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'step' => 1,
			),
		)
	);

	foreach (
		array(
			'jwellery_enable_cart_drawer' => __( 'Mini cart drawer', 'jwellery-jewelry' ),
			'jwellery_enable_quick_view'  => __( 'Quick view on products', 'jwellery-jewelry' ),
			'jwellery_enable_mega_menu'   => __( 'Shop mega menu (wide panel â€” off = simple dropdown)', 'jwellery-jewelry' ),
		) as $key => $label
	) {
		$default_on = 'jwellery_enable_mega_menu' !== $key;
		$wp_customize->add_setting(
			$key,
			array(
				'sanitize_callback' => 'wp_validate_boolean',
				'default'           => $default_on,
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => 'jwellery_store_ui',
				'type'    => 'checkbox',
			)
		);
	}

	$wp_customize->add_setting(
		'jwellery_newsletter_text',
		array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => __( 'Get new designs & offers on WhatsApp', 'jwellery-jewelry' ),
		)
	);
	$wp_customize->add_control(
		'jwellery_newsletter_text',
		array(
			'label'   => __( 'Newsletter / WhatsApp CTA heading', 'jwellery-jewelry' ),
			'section' => 'jwellery_store_ui',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'jwellery_carousel_autoplay',
		array(
			'sanitize_callback' => 'wp_validate_boolean',
			'default'           => true,
		)
	);
	$wp_customize->add_control(
		'jwellery_carousel_autoplay',
		array(
			'label'   => __( 'Auto-scroll product carousels', 'jwellery-jewelry' ),
			'section' => 'jwellery_store_ui',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_section(
		'jwellery_owner',
		array(
			'title'       => __( 'Homepage â€” Meet the Owner', 'jwellery-jewelry' ),
			'description' => __( 'Owner photo and intro shown on the homepage.', 'jwellery-jewelry' ),
			'priority'    => 120,
		)
	);

	$wp_customize->add_setting(
		'jwellery_owner_enable',
		array(
			'sanitize_callback' => 'wp_validate_boolean',
			'default'           => true,
		)
	);
	$wp_customize->add_control(
		'jwellery_owner_enable',
		array(
			'label'   => __( 'Show owner section on homepage', 'jwellery-jewelry' ),
			'section' => 'jwellery_owner',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'jwellery_owner_image',
		array(
			'sanitize_callback' => 'jwellery_sanitize_hero_image',
			'default'           => '',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'jwellery_owner_image',
			array(
				'label'       => __( 'Owner photo', 'jwellery-jewelry' ),
				'description' => __( 'Leave empty to use kalpana-pic from Media Library or the built-in photo.', 'jwellery-jewelry' ),
				'section'     => 'jwellery_owner',
			)
		)
	);

	$wp_customize->add_setting(
		'jwellery_owner_name',
		array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Kalpana',
		)
	);
	$wp_customize->add_control(
		'jwellery_owner_name',
		array(
			'label'   => __( 'Owner name', 'jwellery-jewelry' ),
			'section' => 'jwellery_owner',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'jwellery_owner_role',
		array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => __( 'Founder', 'jwellery-jewelry' ),
		)
	);
	$wp_customize->add_control(
		'jwellery_owner_role',
		array(
			'label'   => __( 'Owner title / role', 'jwellery-jewelry' ),
			'section' => 'jwellery_owner',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'jwellery_owner_bio',
		array(
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => __( 'Passionate about traditional Indian jewelry â€” every piece is chosen with love so you can shine at weddings, festivals, and everyday moments.', 'jwellery-jewelry' ),
		)
	);
	$wp_customize->add_control(
		'jwellery_owner_bio',
		array(
			'label'   => __( 'Owner introduction', 'jwellery-jewelry' ),
			'section' => 'jwellery_owner',
			'type'    => 'textarea',
		)
	);

	$wp_customize->add_section(
		'jwellery_home_sections',
		array(
			'title'       => __( 'Homepage Sections', 'jwellery-jewelry' ),
			'description' => __( 'Show or hide homepage blocks. Top Categories carousel is off by default â€” Shop by Category cards are recommended.', 'jwellery-jewelry' ),
			'priority'    => 119,
		)
	);

	$home_toggles = array(
		'jwellery_home_enable_trust_strip'     => __( 'Trust strip (shipping, payment)', 'jwellery-jewelry' ),
		'jwellery_home_enable_budget'          => __( 'Shop by Budget', 'jwellery-jewelry' ),
		'jwellery_home_enable_top_categories'  => __( 'Top Categories carousel', 'jwellery-jewelry' ),
		'jwellery_home_enable_category_browse' => __( 'Shop by Category cards', 'jwellery-jewelry' ),
		'jwellery_home_enable_handmade'        => __( 'Handmade Collection', 'jwellery-jewelry' ),
		'jwellery_home_enable_steal_deals'     => __( 'Steal Deal Offers', 'jwellery-jewelry' ),
		'jwellery_home_enable_new_collection'  => __( 'New Collection', 'jwellery-jewelry' ),
		'jwellery_home_enable_product_of_day'  => __( 'Product of the Day', 'jwellery-jewelry' ),
		'jwellery_home_enable_follow_journey'  => __( 'Follow Our Journey', 'jwellery-jewelry' ),
		'jwellery_home_enable_instagram'       => __( 'Instagram Collection', 'jwellery-jewelry' ),
		'jwellery_home_enable_testimonials'    => __( 'Customer Reviews', 'jwellery-jewelry' ),
		'jwellery_home_enable_faq'             => __( 'FAQ', 'jwellery-jewelry' ),
	);

	foreach ( $home_toggles as $key => $label ) {
		$default = true;
		if ( 'jwellery_home_enable_top_categories' === $key ) {
			$default = false;
		}
		$wp_customize->add_setting(
			$key,
			array(
				'sanitize_callback' => 'wp_validate_boolean',
				'default'           => $default,
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => 'jwellery_home_sections',
				'type'    => 'checkbox',
			)
		);
	}

	$wp_customize->add_section(
		'jwellery_hero',
		array(
			'title'       => __( 'Homepage Hero', 'jwellery-jewelry' ),
			'description' => __( 'Upload up to 5 images for the homepage slider. Leave empty to use built-in jewelry photos.', 'jwellery-jewelry' ),
			'priority'    => 121,
		)
	);

	for ( $i = 1; $i <= 5; $i++ ) {
		$key = 'jwellery_hero_image_' . $i;
		$wp_customize->add_setting(
			$key,
			array(
				'sanitize_callback' => 'jwellery_sanitize_hero_image',
				'default'           => '',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				$key,
				array(
					'label'   => sprintf(
						/* translators: %d: slide number */
						__( 'Hero slide %d image', 'jwellery-jewelry' ),
						$i
					),
					'section' => 'jwellery_hero',
				)
			)
		);
	}

	$wp_customize->add_setting(
		'jwellery_hero_from_price',
		array(
			'sanitize_callback' => 'absint',
			'default'           => 0,
		)
	);
	$wp_customize->add_control(
		'jwellery_hero_from_price',
		array(
			'label'       => __( 'Hero â€œfromâ€ price (â‚¹)', 'jwellery-jewelry' ),
			'description' => __( 'Leave 0 to use your lowest in-stock product price automatically.', 'jwellery-jewelry' ),
			'section'     => 'jwellery_hero',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'step' => 1,
			),
		)
	);

	$wp_customize->add_section(
		'jwellery_social',
		array(
			'title'       => __( 'Social Links', 'jwellery-jewelry' ),
			'description' => __( 'Icons always show in the footer. Paste full URLs (Instagram, Facebook, YouTube). WhatsApp uses the number from Store UI.', 'jwellery-jewelry' ),
			'priority'    => 130,
		)
	);

	$wp_customize->add_section(
		'jwellery_security_captcha',
		array(
			'title'       => __( 'Login CAPTCHA', 'jwellery-jewelry' ),
			'description' => __( 'Stops spam login and registration bots. Math CAPTCHA works immediately. For Turnstile or reCAPTCHA, add keys from Cloudflare or Google.', 'jwellery-jewelry' ),
			'priority'    => 125,
		)
	);

	$wp_customize->add_setting(
		'jwellery_captcha_enable',
		array(
			'default'           => true,
			'sanitize_callback' => 'wp_validate_boolean',
		)
	);
	$wp_customize->add_control(
		'jwellery_captcha_enable',
		array(
			'label'   => __( 'Enable login CAPTCHA', 'jwellery-jewelry' ),
			'section' => 'jwellery_security_captcha',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'jwellery_captcha_provider',
		array(
			'default'           => 'math',
			'sanitize_callback' => static function ( $value ) {
				$allowed = array( 'math', 'turnstile', 'recaptcha' );
				return in_array( $value, $allowed, true ) ? $value : 'math';
			},
		)
	);
	$wp_customize->add_control(
		'jwellery_captcha_provider',
		array(
			'label'   => __( 'CAPTCHA type', 'jwellery-jewelry' ),
			'section' => 'jwellery_security_captcha',
			'type'    => 'select',
			'choices' => array(
				'math'      => __( 'Math question (no API key)', 'jwellery-jewelry' ),
				'turnstile' => __( 'Cloudflare Turnstile', 'jwellery-jewelry' ),
				'recaptcha' => __( 'Google reCAPTCHA v2', 'jwellery-jewelry' ),
			),
		)
	);

	foreach (
		array(
			'jwellery_turnstile_site_key'   => __( 'Turnstile site key', 'jwellery-jewelry' ),
			'jwellery_turnstile_secret_key' => __( 'Turnstile secret key', 'jwellery-jewelry' ),
			'jwellery_recaptcha_site_key'   => __( 'reCAPTCHA site key', 'jwellery-jewelry' ),
			'jwellery_recaptcha_secret_key' => __( 'reCAPTCHA secret key', 'jwellery-jewelry' ),
		) as $setting_id => $label
	) {
		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			$setting_id,
			array(
				'label'   => $label,
				'section' => 'jwellery_security_captcha',
				'type'    => 'text',
			)
		);
	}

	foreach ( array( 'facebook', 'instagram', 'youtube' ) as $network ) {
		$wp_customize->add_setting(
			'jwellery_' . $network,
			array(
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
		$wp_customize->add_control(
			'jwellery_' . $network,
			array(
				'label'   => ucfirst( $network ) . ' URL',
				'section' => 'jwellery_social',
				'type'    => 'url',
			)
		);
	}
}
add_action( 'customize_register', 'jwellery_customize_register' );


