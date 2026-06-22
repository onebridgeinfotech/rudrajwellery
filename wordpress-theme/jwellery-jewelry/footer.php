<?php
/**
 * Footer template — krishnamaalika.in style.
 *
 * @package JwelleryJewelry
 */

defined( 'ABSPATH' ) || exit;

$phone        = trim( (string) get_theme_mod( 'jwellery_phone', '+91 7730817950' ) );
$address      = trim( (string) get_theme_mod( 'jwellery_address', 'H no 7-7-11/8, New Sri Ram Nagar Colony, Peerzadiguda, Hyderabad - 500098' ) );
$footer_emails = function_exists( 'jwellery_footer_contact_email_rows' )
	? jwellery_footer_contact_email_rows()
	: array(
		'info'     => 'info@rudrajewellery.co.in',
		'personal' => 'kalpanayadav503@gmail.com',
	);
if ( ! $phone ) {
	$phone = '+91 7730817950';
}
if ( ! $address ) {
	$address = 'H no 7-7-11/8, New Sri Ram Nagar Colony, Peerzadiguda, Hyderabad - 500098';
}
?>

</main>

<footer class="jwellery-footer" role="contentinfo">
	<div class="container">
		<div class="jwellery-footer-grid">
			<div class="jwellery-footer-col jwellery-footer-brand">
				<?php
				if ( function_exists( 'jwellery_render_footer_logo' ) ) {
					jwellery_render_footer_logo();
				}
				?>
				<p class="screen-reader-text"><?php echo esc_html( function_exists( 'jwellery_footer_brand' ) ? jwellery_footer_brand() : get_bloginfo( 'name' ) ); ?></p>
				<p class="jwellery-footer-about"><?php echo esc_html( function_exists( 'jwellery_footer_about_text' ) ? jwellery_footer_about_text() : '' ); ?></p>
				<?php
				$wa_url = function_exists( 'jwellery_whatsapp_url' ) ? jwellery_whatsapp_url() : '';
				$cta    = get_theme_mod( 'jwellery_newsletter_text', __( 'Get new designs & offers on WhatsApp', 'jwellery-jewelry' ) );
				if ( $wa_url ) :
					?>
					<p class="jwellery-footer-wa-cta"><?php echo esc_html( $cta ); ?></p>
					<a class="jwellery-btn jwellery-btn-wa jwellery-footer-wa-btn" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Chat on WhatsApp', 'jwellery-jewelry' ); ?>
					</a>
				<?php else : ?>
					<form class="jwellery-subscribe-form jwellery-subscribe-form--brand" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="jwellery_subscribe" />
						<?php wp_nonce_field( 'jwellery_subscribe', 'jwellery_subscribe_nonce' ); ?>
						<label class="screen-reader-text" for="jwellery-footer-email"><?php esc_html_e( 'Email', 'jwellery-jewelry' ); ?></label>
						<input type="email" id="jwellery-footer-email" name="jwellery_email" placeholder="<?php esc_attr_e( 'Your email address', 'jwellery-jewelry' ); ?>" required />
						<button type="submit" class="jwellery-btn jwellery-btn-primary"><?php esc_html_e( 'Subscribe', 'jwellery-jewelry' ); ?></button>
					</form>
					<?php
					$jwellery_subscribe_flag = isset( $_GET['subscribe'] ) ? sanitize_key( wp_unslash( $_GET['subscribe'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( 'thanks' === $jwellery_subscribe_flag ) :
					?>
						<p class="jwellery-subscribe-msg"><?php esc_html_e( 'Thank you for subscribing!', 'jwellery-jewelry' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="jwellery-footer-col">
				<h4 class="jwellery-footer-title"><?php esc_html_e( 'Quick Links', 'jwellery-jewelry' ); ?></h4>
				<ul class="jwellery-footer-links">
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>?s=&post_type=product"><?php esc_html_e( 'Search', 'jwellery-jewelry' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'jwellery-jewelry' ); ?></a></li>
					<li><a href="<?php echo esc_url( function_exists( 'jwellery_all_products_url' ) ? jwellery_all_products_url() : jwellery_get_shop_url() ); ?>"><?php esc_html_e( 'All Products', 'jwellery-jewelry' ); ?></a></li>
					<?php
					foreach ( array( 'about' => 'About Us', 'contact' => 'Contact Us' ) as $slug => $label ) {
						$url = function_exists( 'jwellery_get_store_page_url' ) ? jwellery_get_store_page_url( $slug ) : home_url( '/' . $slug . '/' );
						printf(
							'<li><a href="%s">%s</a></li>',
							esc_url( $url ),
							esc_html( $label )
						);
					}
					?>
				</ul>
			</div>

			<div class="jwellery-footer-col">
				<h4 class="jwellery-footer-title"><?php esc_html_e( 'Help & Info', 'jwellery-jewelry' ); ?></h4>
				<ul class="jwellery-footer-links">
					<?php
					foreach ( array( 'privacy-policy' => 'Privacy Policy', 'refund-policy' => 'Cancellation & Refund', 'terms-of-service' => 'Terms of Service', 'shipping-policy' => 'Shipping & Delivery', 'track-order' => 'Track Order' ) as $slug => $label ) {
						$url = function_exists( 'jwellery_get_store_page_url' ) ? jwellery_get_store_page_url( $slug ) : home_url( '/' . $slug . '/' );
						printf(
							'<li><a href="%s">%s</a></li>',
							esc_url( $url ),
							esc_html( $label )
						);
					}
					?>
				</ul>
			</div>

			<div class="jwellery-footer-col jwellery-footer-newsletter">
				<h4 class="jwellery-footer-title"><?php esc_html_e( 'Stay in the Loop', 'jwellery-jewelry' ); ?></h4>
				<ul class="jwellery-footer-stay-contact">
					<?php if ( $phone ) : ?>
						<li>
							<span class="jwellery-footer-stay-icon" aria-hidden="true">
								<?php
								if ( function_exists( 'jwellery_icon_svg' ) ) {
									echo jwellery_icon_svg( 'phone', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</span>
							<a href="tel:<?php echo esc_attr( preg_replace( '/\D+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
						</li>
					<?php endif; ?>
					<?php if ( ! empty( $footer_emails['info'] ) && is_email( $footer_emails['info'] ) ) : ?>
						<li>
							<span class="jwellery-footer-stay-icon" aria-hidden="true">
								<?php
								if ( function_exists( 'jwellery_icon_svg' ) ) {
									echo jwellery_icon_svg( 'email', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</span>
							<a href="mailto:<?php echo esc_attr( $footer_emails['info'] ); ?>"><?php echo esc_html( $footer_emails['info'] ); ?></a>
						</li>
					<?php endif; ?>
					<?php
					$personal_email = isset( $footer_emails['personal'] ) ? strtolower( trim( (string) $footer_emails['personal'] ) ) : '';
					if ( $personal_email && is_email( $personal_email ) && $personal_email !== strtolower( $footer_emails['info'] ) ) :
						?>
						<li class="jwellery-footer-stay-contact__item--plain">
							<a href="mailto:<?php echo esc_attr( $personal_email ); ?>"><?php echo esc_html( $personal_email ); ?></a>
						</li>
					<?php endif; ?>
					<?php if ( $address ) : ?>
						<li>
							<span class="jwellery-footer-stay-icon" aria-hidden="true">
								<?php
								if ( function_exists( 'jwellery_icon_svg' ) ) {
									echo jwellery_icon_svg( 'location', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</span>
							<span><?php echo esc_html( $address ); ?></span>
						</li>
					<?php endif; ?>
				</ul>
				<?php
				if ( function_exists( 'jwellery_footer_social_inline' ) ) {
					jwellery_footer_social_inline();
				}
				?>
			</div>
		</div>

		<?php
		if ( function_exists( 'jwellery_footer_trust' ) ) {
			jwellery_footer_trust();
		}
		?>

		<p class="jwellery-copyright">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( function_exists( 'jwellery_footer_brand' ) ? jwellery_footer_brand() : get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'jwellery-jewelry' ); ?>
		</p>
		<p class="jwellery-footer-credit">
			<a href="https://www.onebridgeinfotech.com" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Designed by Onebridge Infotech', 'jwellery-jewelry' ); ?>
			</a>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
