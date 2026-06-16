<?php

/**

 * Front page — layout inspired by krishnamaalika.in + ramyanagendra.com

 *

 * @package JwelleryJewelry

 */



defined( 'ABSPATH' ) || exit;



get_header();



jwellery_home_hero();



if ( function_exists( 'jwellery_home_section_enabled' ) && jwellery_home_section_enabled( 'trust_strip' ) && function_exists( 'jwellery_home_trust_strip' ) ) {

	jwellery_home_trust_strip();

}



if ( function_exists( 'wc_get_products' ) && class_exists( 'WooCommerce' ) ) {

	if ( function_exists( 'jwellery_home_popular_tabs' ) ) {

		jwellery_home_popular_tabs();

	} elseif ( function_exists( 'jwellery_home_product_grid' ) ) {

		jwellery_home_product_grid(

			__( 'Best Sellers', 'jwellery-jewelry' ),

			array( 'featured' => true ),

			add_query_arg( 'featured', '1', jwellery_get_shop_url() )

		);

	} else {

		jwellery_home_section(

			__( 'Best Sellers', 'jwellery-jewelry' ),

			array( 'featured' => true ),

			add_query_arg( 'featured', '1', jwellery_get_shop_url() )

		);

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'budget' ) ) && function_exists( 'jwellery_home_shop_by_budget' ) ) {

		jwellery_home_shop_by_budget();

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'top_categories' ) ) ) {

		jwellery_home_categories();

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'category_browse' ) ) && function_exists( 'jwellery_home_category_stats' ) ) {

		jwellery_home_category_stats();

	}



	if ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'handmade' ) ) {

		jwellery_home_section(

			__( 'Handmade Collection', 'jwellery-jewelry' ),

			array( 'category' => array( 'handmade-collection' ) ),

			jwellery_term_link( 'handmade-collection' )

		);

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'steal_deals' ) ) && function_exists( 'jwellery_home_steal_deals' ) ) {

		jwellery_home_steal_deals();

	}



	if ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'new_collection' ) ) {

		jwellery_home_section(

			__( 'New Collection', 'jwellery-jewelry' ),

			array( 'category' => array( 'latest-collection' ) ),

			jwellery_term_link( 'latest-collection' )

		);

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'product_of_day' ) ) ) {

		jwellery_home_product_of_day();

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'follow_journey' ) ) && function_exists( 'jwellery_home_follow_journey' ) ) {

		jwellery_home_follow_journey();

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'instagram' ) ) && function_exists( 'jwellery_category_has_products' ) && jwellery_category_has_products( 'instagram-collection' ) ) {

		jwellery_home_section(

			__( 'Instagram Collection', 'jwellery-jewelry' ),

			array( 'category' => array( 'instagram-collection' ) ),

			jwellery_term_link( 'instagram-collection' )

		);

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'testimonials' ) ) ) {

		if ( function_exists( 'jwellery_home_testimonials_km' ) ) {

			jwellery_home_testimonials_km();

		} elseif ( function_exists( 'jwellery_home_testimonials' ) ) {

			jwellery_home_testimonials();

		}

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'owner' ) ) && function_exists( 'jwellery_home_owner_section' ) ) {

		jwellery_home_owner_section();

	}



	if ( ( ! function_exists( 'jwellery_home_section_enabled' ) || jwellery_home_section_enabled( 'faq' ) ) && function_exists( 'jwellery_home_faq' ) ) {

		jwellery_home_faq();

	}

} else {

	?>

	<div class="container">

		<p class="jwellery-notice">

			<?php esc_html_e( 'Install WooCommerce, activate this theme, then go to Appearance → Store Setup and import products.', 'jwellery-jewelry' ); ?></p>

	</div>

	<?php

}



get_footer();


