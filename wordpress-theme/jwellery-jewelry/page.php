<?php
/**
 * Page template — store pages with icon hero & animated sections.
 *
 * @package JwelleryJewelry
 */

get_header();

while ( have_posts() ) :
	the_post();

	$page_key    = function_exists( 'jwellery_current_store_page_key' ) ? jwellery_current_store_page_key( get_the_ID() ) : '';
	$is_account  = function_exists( 'is_account_page' ) && is_account_page();
	$hero_sub    = $page_key && function_exists( 'jwellery_page_hero_subtitle' ) ? jwellery_page_hero_subtitle( $page_key ) : '';
	$hero_icon   = $page_key && function_exists( 'jwellery_page_hero_icon_html' ) ? jwellery_page_hero_icon_html( $page_key ) : '';
	// Account pages use in-layout headers — no full-page hero.
	$show_account_hero = false;

	if ( $page_key ) :
		?>
		<section class="jwellery-page-hero jwellery-page-hero--icon jwellery-animate-hero">
			<div class="jwellery-page-hero-overlay"></div>
			<div class="container jwellery-page-hero-inner">
				<nav class="jwellery-page-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'jwellery-jewelry' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'jwellery-jewelry' ); ?></a>
					<span aria-hidden="true">/</span>
					<span><?php the_title(); ?></span>
				</nav>
				<?php if ( $hero_icon ) : ?>
					<div class="jwellery-page-hero-icon-badge" aria-hidden="true"><?php echo $hero_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
				<h1 class="jwellery-page-hero-title"><?php the_title(); ?></h1>
				<?php if ( $hero_sub ) : ?>
					<p class="jwellery-page-hero-sub"><?php echo esc_html( $hero_sub ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
	endif;
	?>
	<div class="container jwellery-page-content<?php echo $page_key ? ' jwellery-page-content--store' : ''; ?><?php echo $is_account ? ' jwellery-page-content--account' : ''; ?>">
		<article <?php post_class( $page_key ? 'jwellery-page-article jwellery-page-article--store' : 'jwellery-page-article' ); ?>>
			<?php if ( ! $page_key && ! $is_account ) : ?>
				<h1 class="page-title"><?php the_title(); ?></h1>
			<?php endif; ?>
			<div class="entry-content jwellery-page-body">
				<?php the_content(); ?>
			</div>
		</article>
	</div>
	<?php
endwhile;

get_footer();
