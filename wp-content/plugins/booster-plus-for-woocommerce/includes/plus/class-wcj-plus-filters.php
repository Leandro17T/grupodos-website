<?php
/**
 * Booster for WooCommerce - Plus - Filters
 *
 * @version 6.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Plus_Filters' ) ) :
		/**
		 * WCJ_Plus_Filters.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
	class WCJ_Plus_Filters {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'booster_message', array( $this, 'booster_get_message' ), 101 );
			add_filter( 'booster_option', array( $this, 'booster_get_option' ), 101, 2 );
		}

		/**
		 * Booster_get_option.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 * @param string $value1 Get option value.
		 * @param string $value2 Get option value.
		 */
		public function booster_get_option( $value1, $value2 ) {
			return $value2;
		}

		/**
		 * Booster_get_message.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
		public function booster_get_message() {
			return '';
		}
	}

endif;

return new WCJ_Plus_Filters();
