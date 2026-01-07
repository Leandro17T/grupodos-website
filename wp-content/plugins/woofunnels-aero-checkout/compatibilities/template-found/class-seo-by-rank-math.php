<?php

/**
 * Plugin Name:       Rank Math SEO
 * Version:           1.0.56.1
 * Plugin URI:        https://s.rankmath.com/home
 * Author:            Rank Math
 */

if ( ! class_exists( 'WFACP_Seo_By_Rank_Math' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Seo_By_Rank_Math {
		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'add_action' ] );
		}

		public function add_action() {
			WFACP_Common::remove_actions( 'rank_math/frontend/robots', 'RankMath\WooCommerce\WooCommerce', 'robots' );
			add_filter( 'rank_math/frontend/description', [ $this, 'modify_rank_math_description' ], 10, 1 );
		}

		/**
		 * Add Checkout data to the Rank Math description
		 * Return post title if empty description
		 *
		 * @param $description
		 *
		 * @return mixed|string
		 */
		public function modify_rank_math_description( $description ) {
			if ( ! empty( $description ) ) {
				return $description;
			}

			global $post;

			if ( ! ( $post instanceof WP_Post ) || ( WFACP_Common::get_post_type_slug() !== $post->post_type ) ) {
				return $description;
			}

			return $post->post_title;

		}
	}


	WFACP_Plugin_Compatibilities::register( new WFACP_Seo_By_Rank_Math(), 'wfacp-seo-by-rank-math' );
}
