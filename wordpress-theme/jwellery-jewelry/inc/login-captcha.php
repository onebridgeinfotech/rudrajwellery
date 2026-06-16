<?php
/**
 * Login & registration CAPTCHA — math (default), Turnstile, or reCAPTCHA.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is login captcha enabled?
 *
 * @return bool
 */
function jwellery_captcha_enabled() {
	return (bool) get_theme_mod( 'jwellery_captcha_enable', true );
}

/**
 * Active provider (falls back to math if API keys missing).
 *
 * @return string math|turnstile|recaptcha
 */
function jwellery_captcha_provider() {
	if ( ! jwellery_captcha_enabled() ) {
		return 'math';
	}

	$choice = sanitize_key( (string) get_theme_mod( 'jwellery_captcha_provider', 'math' ) );

	if ( 'turnstile' === $choice && jwellery_captcha_turnstile_configured() ) {
		return 'turnstile';
	}
	if ( 'recaptcha' === $choice && jwellery_captcha_recaptcha_configured() ) {
		return 'recaptcha';
	}

	return 'math';
}

/**
 * @return bool
 */
function jwellery_captcha_turnstile_configured() {
	$site   = trim( (string) get_theme_mod( 'jwellery_turnstile_site_key', '' ) );
	$secret = trim( (string) get_theme_mod( 'jwellery_turnstile_secret_key', '' ) );
	return '' !== $site && '' !== $secret;
}

/**
 * @return bool
 */
function jwellery_captcha_recaptcha_configured() {
	$site   = trim( (string) get_theme_mod( 'jwellery_recaptcha_site_key', '' ) );
	$secret = trim( (string) get_theme_mod( 'jwellery_recaptcha_secret_key', '' ) );
	return '' !== $site && '' !== $secret;
}

/**
 * Create a math challenge stored in a transient.
 *
 * @return array{id: string, question: string}
 */
function jwellery_captcha_create_math_challenge() {
	$a  = wp_rand( 2, 12 );
	$b  = wp_rand( 2, 12 );
	$id = wp_generate_password( 20, false, false );

	set_transient(
		'jwellery_captcha_' . $id,
		(string) ( $a + $b ),
		15 * MINUTE_IN_SECONDS
	);

	return array(
		'id'       => $id,
		'question' => sprintf(
			/* translators: 1: number, 2: number */
			__( 'What is %1$d + %2$d?', 'jwellery-jewelry' ),
			$a,
			$b
		),
	);
}

/**
 * Render captcha markup for a form.
 *
 * @param string $context login|register.
 */
function jwellery_captcha_render( $context = 'login' ) {
	if ( ! jwellery_captcha_enabled() ) {
		return;
	}

	$provider = jwellery_captcha_provider();
	$uid      = 'jwellery-captcha-' . sanitize_key( $context );
	?>
	<div class="jwellery-login-captcha" id="<?php echo esc_attr( $uid ); ?>" data-jwellery-captcha="<?php echo esc_attr( $provider ); ?>">
		<?php if ( 'turnstile' === $provider ) : ?>
			<div
				class="cf-turnstile"
				data-sitekey="<?php echo esc_attr( get_theme_mod( 'jwellery_turnstile_site_key', '' ) ); ?>"
				data-theme="light"
			></div>
		<?php elseif ( 'recaptcha' === $provider ) : ?>
			<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( get_theme_mod( 'jwellery_recaptcha_site_key', '' ) ); ?>"></div>
		<?php else : ?>
			<?php $challenge = jwellery_captcha_create_math_challenge(); ?>
			<label class="jwellery-login-captcha__label" for="<?php echo esc_attr( $uid ); ?>-answer">
				<?php echo esc_html( $challenge['question'] ); ?>
				<span class="required" aria-hidden="true">*</span>
			</label>
			<input
				type="number"
				class="input-text jwellery-login-captcha__input"
				id="<?php echo esc_attr( $uid ); ?>-answer"
				name="jwellery_captcha_answer"
				required
				autocomplete="off"
				inputmode="numeric"
				placeholder="<?php esc_attr_e( 'Your answer', 'jwellery-jewelry' ); ?>"
			/>
			<input type="hidden" name="jwellery_captcha_id" value="<?php echo esc_attr( $challenge['id'] ); ?>" />
		<?php endif; ?>
		<input
			type="text"
			name="jwellery_captcha_hp"
			value=""
			class="jwellery-captcha-hp"
			tabindex="-1"
			autocomplete="off"
			aria-hidden="true"
		/>
	</div>
	<?php
}

/**
 * Verify submitted captcha.
 *
 * @return true|WP_Error
 */
function jwellery_captcha_verify() {
	if ( ! jwellery_captcha_enabled() ) {
		return true;
	}

	if ( ! empty( $_POST['jwellery_captcha_hp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return new WP_Error(
			'jwellery_captcha_bot',
			__( 'Security verification failed. Please try again.', 'jwellery-jewelry' )
		);
	}

	$provider = jwellery_captcha_provider();

	if ( 'turnstile' === $provider ) {
		return jwellery_captcha_verify_turnstile();
	}
	if ( 'recaptcha' === $provider ) {
		return jwellery_captcha_verify_recaptcha();
	}

	return jwellery_captcha_verify_math();
}

/**
 * @return true|WP_Error
 */
function jwellery_captcha_verify_math() {
	$id     = isset( $_POST['jwellery_captcha_id'] ) ? sanitize_text_field( wp_unslash( $_POST['jwellery_captcha_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$answer = isset( $_POST['jwellery_captcha_answer'] ) ? sanitize_text_field( wp_unslash( $_POST['jwellery_captcha_answer'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( '' === $id || '' === $answer ) {
		return new WP_Error(
			'jwellery_captcha_missing',
			__( 'Please complete the security check.', 'jwellery-jewelry' )
		);
	}

	$expected = get_transient( 'jwellery_captcha_' . $id );
	delete_transient( 'jwellery_captcha_' . $id );

	if ( false === $expected || (string) (int) $answer !== (string) $expected ) {
		return new WP_Error(
			'jwellery_captcha_wrong',
			__( 'Incorrect security answer. Please try again.', 'jwellery-jewelry' )
		);
	}

	return true;
}

/**
 * @return true|WP_Error
 */
function jwellery_captcha_verify_turnstile() {
	$token  = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$secret = trim( (string) get_theme_mod( 'jwellery_turnstile_secret_key', '' ) );

	if ( '' === $token || '' === $secret ) {
		return new WP_Error(
			'jwellery_captcha_missing',
			__( 'Please complete the CAPTCHA.', 'jwellery-jewelry' )
		);
	}

	return jwellery_captcha_remote_verify(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'secret'   => $secret,
			'response' => $token,
			'remoteip' => function_exists( 'jwellery_security_client_ip' ) ? jwellery_security_client_ip() : '',
		)
	);
}

/**
 * @return true|WP_Error
 */
function jwellery_captcha_verify_recaptcha() {
	$token  = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$secret = trim( (string) get_theme_mod( 'jwellery_recaptcha_secret_key', '' ) );

	if ( '' === $token || '' === $secret ) {
		return new WP_Error(
			'jwellery_captcha_missing',
			__( 'Please complete the CAPTCHA.', 'jwellery-jewelry' )
		);
	}

	return jwellery_captcha_remote_verify(
		'https://www.google.com/recaptcha/api/siteverify',
		array(
			'secret'   => $secret,
			'response' => $token,
			'remoteip' => function_exists( 'jwellery_security_client_ip' ) ? jwellery_security_client_ip() : '',
		)
	);
}

/**
 * POST to captcha provider API.
 *
 * @param string               $url  Endpoint.
 * @param array<string,string> $body Body.
 * @return true|WP_Error
 */
function jwellery_captcha_remote_verify( $url, $body ) {
	$response = wp_remote_post(
		$url,
		array(
			'timeout' => 12,
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'jwellery_captcha_remote',
			__( 'Could not verify CAPTCHA. Please try again.', 'jwellery-jewelry' )
		);
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['success'] ) ) {
		return new WP_Error(
			'jwellery_captcha_failed',
			__( 'CAPTCHA verification failed. Please try again.', 'jwellery-jewelry' )
		);
	}

	return true;
}

/**
 * Is this a login POST attempt?
 *
 * @return bool
 */
function jwellery_captcha_is_login_post() {
	return isset( $_POST['log'], $_POST['pwd'] ) || isset( $_POST['username'], $_POST['password'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Is this a registration POST attempt?
 *
 * @return bool
 */
function jwellery_captcha_is_register_post() {
	return isset( $_POST['register'] ) || ( isset( $_POST['woocommerce-register-nonce'] ) && isset( $_POST['email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Validate captcha on login (wp-login + WooCommerce my-account).
 *
 * @param WP_User|WP_Error|null $user     User.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function jwellery_captcha_authenticate( $user, $username, $password ) {
	unset( $username, $password );

	if ( ! jwellery_captcha_is_login_post() ) {
		return $user;
	}

	$result = jwellery_captcha_verify();
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return $user;
}
add_filter( 'authenticate', 'jwellery_captcha_authenticate', 21, 3 );

/**
 * WooCommerce registration captcha.
 *
 * @param WP_Error $errors   Errors.
 * @param string   $username Username.
 * @param string   $email    Email.
 * @return WP_Error
 */
function jwellery_captcha_wc_registration_errors( $errors, $username, $email ) {
	unset( $username, $email );

	if ( ! jwellery_captcha_is_register_post() ) {
		return $errors;
	}

	$result = jwellery_captcha_verify();
	if ( is_wp_error( $result ) ) {
		$errors->add( $result->get_error_code(), $result->get_error_message() );
	}

	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'jwellery_captcha_wc_registration_errors', 10, 3 );

/**
 * wp-login.php registration captcha.
 *
 * @param WP_Error $errors Errors.
 * @return WP_Error
 */
function jwellery_captcha_wp_registration_errors( $errors ) {
	if ( ! jwellery_captcha_is_register_post() ) {
		return $errors;
	}

	$result = jwellery_captcha_verify();
	if ( is_wp_error( $result ) ) {
		$errors->add( $result->get_error_code(), $result->get_error_message() );
	}

	return $errors;
}
add_filter( 'registration_errors', 'jwellery_captcha_wp_registration_errors', 10, 1 );

/* ——— Form hooks ——— */
add_action( 'woocommerce_login_form', static function () {
	jwellery_captcha_render( 'login' );
}, 20 );

add_action( 'woocommerce_register_form', static function () {
	jwellery_captcha_render( 'register' );
}, 20 );

add_action( 'login_form', static function () {
	jwellery_captcha_render( 'login' );
}, 20 );

add_action( 'register_form', static function () {
	jwellery_captcha_render( 'register' );
}, 20 );

/**
 * Enqueue Turnstile / reCAPTCHA scripts on login surfaces.
 */
function jwellery_captcha_enqueue_scripts() {
	if ( ! jwellery_captcha_enabled() ) {
		return;
	}

	$provider = jwellery_captcha_provider();
	$on_login = function_exists( 'is_account_page' ) && is_account_page();

	if ( ! $on_login && ( ! isset( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] ) ) {
		return;
	}

	if ( 'turnstile' === $provider ) {
		wp_enqueue_script(
			'cloudflare-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js',
			array(),
			null,
			true
		);
	} elseif ( 'recaptcha' === $provider ) {
		wp_enqueue_script(
			'google-recaptcha',
			'https://www.google.com/recaptcha/api.js',
			array(),
			null,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'jwellery_captcha_enqueue_scripts', 40 );
add_action( 'login_enqueue_scripts', 'jwellery_captcha_enqueue_scripts' );

/**
 * Captcha styles on account + wp-login.
 */
function jwellery_captcha_enqueue_styles() {
	if ( ! jwellery_captcha_enabled() ) {
		return;
	}

	$load = ( function_exists( 'is_account_page' ) && is_account_page() )
		|| ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] );

	if ( ! $load ) {
		return;
	}

	wp_enqueue_style(
		'jwellery-login-captcha',
		JWELLERY_THEME_URI . '/assets/css/login-captcha.css',
		array(),
		jwellery_asset_version()
	);
}
add_action( 'wp_enqueue_scripts', 'jwellery_captcha_enqueue_styles', 40 );
add_action( 'login_enqueue_scripts', 'jwellery_captcha_enqueue_styles' );
