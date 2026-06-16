<?php
/**
 * Main template fallback.
 *
 * @package JwelleryJewelry
 */

get_header();
?>
<div class="container jwellery-page-content">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</div>
<?php
get_footer();
